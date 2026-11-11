<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\Helpers\LD;
use App\Enums\MssqlScheduleMasterStatus;
use App\Enums\MssqlScheduleMasterEChartStatus;
use App\Enums\MssqlScheduleMasterBillingBucketEnum;
use App\ScheduleMaster;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class SetBillingBucketGroup extends Command
{
    private const BILLED_LOOKUP_WEEKS = 200;

    private const CHILD_LOOKUP_PARENTS = 500;

    private const WEEK_START_EXPRESSION = 'DATE_SUB(DATE(PlannedStart), INTERVAL WEEKDAY(PlannedStart) DAY)';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:billingbucketgroup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //Reset all to ZERO
        $this->resetAllBillingRecords();
        $this->setBillReady();
        $this->setNotBillReady();
        return Command::SUCCESS;
    }

    private function resetAllBillingRecords() {
        echo "resetAllBillingRecords started"."\n";
        H::ExcelLog('Start resetAllBillingRecords', 'resetAllBillingRecords.log');
        $this->schedules()->where('billing_bucket_type', '<>', 0)->update(['billing_bucket_type' => 0]);
        H::ExcelLog('Finish resetAllBillingRecords', 'resetAllBillingRecords.log');
        echo "resetAllBillingRecords Finished"."\n";
    }

    private function setBillReady() {
        echo "setBillReady started"."\n";
        H::ExcelLog('Start setBillReady', 'setBillReadyProcess.log');
        $billReadyBefore = LD::checkFeatureFlagLoggedOut('get-till-last-sunday-records-bill-ready-bucket')
            ? H::getMondayFromDate(date('Y-m-d'))
            : H::getMondayFromDate(date('Y-m-d'), 1);
        $weeks = $this->unbilledWeeksQuery($billReadyBefore)
            ->havingRaw("SUM(CASE WHEN " . $this->notBillReadyCondition() . " THEN 1 ELSE 0 END) = 0")
            ->havingRaw(
                "SUM(CASE WHEN ScheduleStatusID = " . MssqlScheduleMasterStatus::APPROVED->value . " " .
                "AND EChartStatusID = " . MssqlScheduleMasterEChartStatus::APPROVED->value . " THEN 1 ELSE 0 END) >= 1");
        $this->assignBucket($weeks, $billReadyBefore, MssqlScheduleMasterBillingBucketEnum::BILL_READY);

        H::ExcelLog('Finish setBillReady', 'setBillReadyProcess.log');
        echo "setBillReady Finished"."\n";
    }

    private function setNotBillReady() {
        echo "setNotBillReady started"."\n";
        H::ExcelLog('Start setNotBillReady', 'setNotBillReadyProcess.log');
        $thisMonday = H::getMondayFromDate(date('Y-m-d'));
        $weeks = $this->unbilledWeeksQuery($thisMonday)
            ->havingRaw("SUM(CASE WHEN " . $this->notBillReadyCondition() . " THEN 1 ELSE 0 END) > 0");
        $this->assignBucket($weeks, $thisMonday, MssqlScheduleMasterBillingBucketEnum::NOT_BILL_READY);

        H::ExcelLog('Finish setNotBillReady', 'setNotBillReadyProcess.log');
        echo "setNotBillReady Finished"."\n";
    }

    private function unbilledWeeksQuery(string $beforeDate): Builder
    {
        $query = $this->schedules()
            ->select(
                DB::raw("GROUP_CONCAT(mssql_schedulemaster.id SEPARATOR ',') AS ids"),
                DB::raw(
                    "GROUP_CONCAT(CASE WHEN ScheduleStatusID = " .
                    MssqlScheduleMasterStatus::APPROVED->value . " " .
                    "AND EChartStatusID = " . MssqlScheduleMasterEChartStatus::APPROVED->value . " " .
                    "THEN mssql_schedulemaster.id END SEPARATOR ',') AS approved_ids"
                ),
                'PatientID',
                DB::raw(self::WEEK_START_EXPRESSION . ' AS week_start')
            )
            ->where('mssql_schedulemaster.IsBilled', 0);

        return $this->applyScheduleConditions($query, $beforeDate)
            ->groupBy('PatientID', DB::raw(self::WEEK_START_EXPRESSION));
    }

    private function assignBucket(Builder $weeksQuery, string $beforeDate, MssqlScheduleMasterBillingBucketEnum $bucket): void
    {
        $weeksQuery->orderBy('PatientID')->orderBy('week_start')
            ->chunk(100000, function ($weeks) use ($beforeDate, $bucket) {
                foreach ($weeks->chunk(self::BILLED_LOOKUP_WEEKS) as $weeksChunk) {
                    if ($bucket === MssqlScheduleMasterBillingBucketEnum::BILL_READY) {
                        $unbilledIds = $weeksChunk->pluck('ids')
                            ->filter()
                            ->flatMap(fn ($ids) => explode(',', $ids))
                            ->filter();

                        $validIds = $this->filterBillReadyParents($unbilledIds)
                            ->map(fn ($id) => (string) $id)
                            ->flip();
                        $weeksChunk = $weeksChunk->filter(function ($week) use ($validIds) {
                            $approvedIds = explode(',', $week->approved_ids ?? '');
                            foreach ($approvedIds as $id) {
                                if ($validIds->has((string) $id)) {
                                    return true;
                                }
                            }

                            return false;
                        });

                        if ($weeksChunk->isEmpty()) {
                            continue;
                        }

                        $unbilledIds = $weeksChunk->pluck('ids')
                            ->filter()
                            ->flatMap(fn ($ids) => explode(',', $ids))
                            ->filter(fn ($id) => $validIds->has((string) $id));
                    } else {
                        $unbilledIds = $weeksChunk->pluck('ids')
                            ->filter()
                            ->flatMap(fn ($ids) => explode(',', $ids))
                            ->filter();
                    }

                    $ids = $this->billedScheduleIds($weeksChunk, $beforeDate)
                        ->merge($unbilledIds)
                        ->filter()
                        ->unique();

                    foreach ($ids->chunk(1000) as $chunk) {
                        $this->schedules()
                            ->whereIn('id', $chunk)
                            ->update(['billing_bucket_type' => $bucket->value]);
                    }
                }
            });
    }

    private function filterBillReadyParents(Collection $ids): Collection
    {
        $invalidIds = collect();

        foreach ($ids->chunk(self::CHILD_LOOKUP_PARENTS) as $idsChunk) {
            $parents = $this->schedules()
                ->select('id', 'CGTaskID', 'PlannedStart')
                ->whereIn('id', $idsChunk)
                ->where('HasChild', 1)
                ->where('IsBilled', 0)
                ->get();

            $parentsByWeek = $parents->groupBy(function ($parent) {
                $timestamp = strtotime($parent->PlannedStart);

                return date(
                    'Y-m-d',
                    strtotime('-' . (date('N', $timestamp) - 1) . ' days', $timestamp)
                );
            });

            foreach ($parentsByWeek as $weekStart => $weekParents) {
                $weekEnd = date('Y-m-d', strtotime($weekStart . ' +7 days'));

                $parentsWithUnbilledChildren = $this->schedules()
                    ->whereIn('ParentCgTaskID', $weekParents->pluck('CGTaskID')->unique())
                    ->where('IsChild', 1)
                    ->where('IsBilled', 0)
                    ->where('is_deleted', '<>', 1)
                    ->where('PlannedStart', '>=', $weekStart)
                    ->where('PlannedStart', '<', $weekEnd)
                    ->distinct()
                    ->pluck('ParentCgTaskID')
                    ->map(fn ($id) => (string) $id)
                    ->flip();

                foreach ($weekParents as $parent) {
                    if (!$parentsWithUnbilledChildren->has((string) $parent->CGTaskID)) {
                        $invalidIds->push($parent->id);
                    }
                }
            }
        }

        return $ids->diff($invalidIds)->values();
    }

    private function billedScheduleIds(Collection $weeks, string $beforeDate): Collection
    {
        $weekKeys = $weeks->mapWithKeys(fn ($week) => [$this->weekKey($week) => true]);
        $query = $this->schedules()
            ->select('id', 'PatientID', DB::raw(self::WEEK_START_EXPRESSION . ' AS week_start'))
            ->where('mssql_schedulemaster.IsBilled', 1)
            ->where(function ($query) use ($weeks) {
                foreach ($weeks as $week) {
                    $query->orWhere(function ($query) use ($week) {
                        $query->where('mssql_schedulemaster.PatientID', $week->PatientID)
                            ->whereBetween('mssql_schedulemaster.ScheduleDate', [
                                date('Y-m-d', strtotime($week->week_start . ' -2 days')),
                                date('Y-m-d', strtotime($week->week_start . ' +8 days')),
                            ]);
                    });
                }
            });

        return $this->applyScheduleConditions($query, $beforeDate)->get()
            ->filter(fn ($schedule) => $weekKeys->has($this->weekKey($schedule)))
            ->pluck('id');
    }

    private function schedules(): Builder
    {
        return ScheduleMaster::query()->toBase();
    }

    private function weekKey(object $row): string
    {
        return $row->PatientID . '|' . $row->week_start;
    }

    private function applyScheduleConditions(Builder $query, string $beforeDate): Builder
    {
        return $query->where('mssql_schedulemaster.IsBillable', 1)
            ->whereRaw('IFNULL(mssql_schedulemaster.IsChild, 0) <> 1')
            ->where('mssql_schedulemaster.PlannedStart', '<', $beforeDate)
            ->whereRaw("IFNULL(mssql_schedulemaster.PlannedStart, '0000-00-00 00:00:00') != '0000-00-00 00:00:00'")
            ->whereDate('mssql_schedulemaster.ScheduleDate', '>=', '2023-01-01')
            ->where('mssql_schedulemaster.is_deleted', '<>', 1);
    }

    private function notBillReadyCondition(): string
    {
        return "(ScheduleStatusID NOT IN (" .
            MssqlScheduleMasterStatus::APPROVED->value . ", " .
            MssqlScheduleMasterStatus::MISSED_VISIT->value . ") " .
            "OR (ScheduleStatusID = " . MssqlScheduleMasterStatus::APPROVED->value . " " .
            "AND EChartStatusID != " . MssqlScheduleMasterEChartStatus::APPROVED->value . "))";
    }
}
