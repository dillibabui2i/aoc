<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateClientFollowupCalls extends Command
{
    protected $signature = 'ccc:migrate-followup-calls {--chunk=500 : Chunk size}';
    protected $description = 'Migrates existing client follow-up call records to the new frequency rules';

    public function handle(): int
    {
        $chunkSize = (int)$this->option('chunk') ?: 500;
        $totalAffectedOld = 0;
        $totalUpsertsNew = 0;
        $totalSkippedNever = 0;

        $baseQuery = DB::table('ccc_followup_calls as c')
            ->select(
                'c.id',
                'c.aoc_patient_id',
                'c.type',
                'c.week_month_start',
                'c.due_date',
                'c.outcome',
                'c.attempt_count',
                'p.ccc_id',
                'p.ccc_client_call_frequency'
            )
            ->join('mssql_patients as p', 'p.id', '=', 'c.aoc_patient_id')
            ->where('c.type', '=', 'client')
            ->where(function ($q) {
                $q->whereNotIn('c.outcome', ['Completed', 'Not Complete'])
                    ->orWhereNull('c.outcome');
            });

        $baseQuery->orderBy('c.id');

        $baseQuery->chunkById($chunkSize, function ($rows) use (
            &$totalAffectedOld,
            &$totalUpsertsNew,
            &$totalSkippedNever
        ) {
            $now = Carbon::now();
            $today = Carbon::today();

            foreach ($rows as $row) {
                $freq = (int)($row->ccc_client_call_frequency ?? 0);

                if ($freq === 0) {
                    $totalSkippedNever++;
                    $totalAffectedOld++;

                    continue;
                }

                $anchor = $this->resolveAnchorDate($row->aoc_patient_id, $today, $row->due_date);

                $nextDue = $this->calculateNextDueDateByNewRules($freq, $anchor);
                $weekMonthStart = Carbon::parse($nextDue)->startOfMonth()->toDateString();
                $baseDate = Carbon::parse($nextDue)->startOfWeek(Carbon::MONDAY)->toDateString();

                DB::table('ccc_followup_calls')
                    ->where('id', $row->id)
                    ->update([
                        'outcome' => 'Completed',
                        'updated_at' => $now->toDateTimeString(),
                        'updated_by' => 1,
                    ]);
                $totalAffectedOld++;

                $payload = [
                    'aoc_patient_id' => $row->aoc_patient_id,
                    'aoc_nthaccount_id' => 0,
                    'start_date' => $weekMonthStart,
                    'base_date' => $baseDate,
                    'ccc_id' => $row->ccc_id,
                    'due_date' => $nextDue,
                    'type' => 'client',
                    'week_month_start' => $weekMonthStart,
                    'attempt_count' => null,
                    'outcome' => null,
                    'updated_at' => $now->toDateTimeString(),
                    'updated_by' => 1,
                ];

                $alreadyExists = DB::table('ccc_followup_calls')
                    ->where('type', 'client')
                    ->where('aoc_patient_id', $row->aoc_patient_id)
                    ->where('week_month_start', $weekMonthStart)
                    ->whereNull('outcome')
                    ->exists();

                if ($alreadyExists) {
                    $totalSkippedNever++;
                    continue;
                }

                DB::table('ccc_followup_calls')->updateOrInsert(
                    [
                        'type' => 'client',
                        'aoc_patient_id' => $row->aoc_patient_id,
                        'week_month_start' => $weekMonthStart,
                    ],
                    array_merge($payload, [
                        'created_at' => $now->toDateTimeString(),
                        'created_by' => 1,
                    ])
                );

                $totalUpsertsNew++;
            }
        }, 'c.id', 'id');

        return 0;
    }

    private function resolveAnchorDate(int $patientId, Carbon $fallbackToday, ?string $currentDueDate): Carbon
    {
        $lastCompleted = DB::table('ccc_followup_calls')
            ->where('type', 'client')
            ->where('aoc_patient_id', $patientId)
            ->where('outcome', 'Completed')
            ->where('due_date', '<=', $fallbackToday->toDateString())
            ->orderByDesc('due_date')
            ->value('due_date');

        if ($lastCompleted) {
            return Carbon::parse($lastCompleted);
        }

        if (!empty($currentDueDate)) {
            return Carbon::parse($currentDueDate);
        }

        return $fallbackToday->copy();
    }

    private function calculateNextDueDateByNewRules(int $freq, Carbon $anchor): string
    {
        switch ($freq) {
            case 1:
                return $this->fridayOfWeek($anchor->copy()->addWeek());

            case 2:
                return $this->fridayOfWeek($anchor->copy()->addWeeks(2));

            case 3:
                return $this->lastWorkingDayOfMonth($anchor->copy()->addMonthsNoOverflow(1));

            case 4:
                return $this->lastWorkingDayOfMonth($anchor->copy()->addMonthsNoOverflow(2));

            case 5:
            default:
                return $this->lastWorkingDayOfMonth($anchor->copy()->addMonthsNoOverflow(3));
        }
    }

    private function fridayOfWeek(Carbon $d): string
    {
        return $d->startOfWeek(Carbon::MONDAY)->addDays(4)->toDateString();
    }

    private function lastWorkingDayOfMonth(Carbon $d): string
    {
        $eom = $d->endOfMonth();
        if ($eom->dayOfWeek === Carbon::SATURDAY) {
            $eom->subDay();
        }
        if ($eom->dayOfWeek === Carbon::SUNDAY) {
            $eom->subDays(2);
        }
        return $eom->toDateString();
    }
}
