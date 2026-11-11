<?php

namespace App\Console\Commands;

use App\Helpers\H;
use App\Kantime\Common\BigQueryConnection;
use App\MssqlRecruitertracker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class ProcessTrackerSchedulesBuilt extends Command
{
    protected $signature = 'process:trackerschedulesbuilt';

    protected $description = 'Validating all tracker client matches when schedules are built';

    protected string $logFile = 'client_match_schedules_built.log';

    protected string $syncType = 'tracker_schedules_built';

    private const SCHEDULE_TABLE = 'daas_dw_schedulemaster_vw';

    public function __construct(
        protected MssqlRecruitertracker $recruiterTracker,
        protected BigQueryConnection $bigQuery,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        [$lastUpdatedId, $limit] = $this->initBatch();

        $recordCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $pendingCount = 0;

        foreach ($this->getTrackers($lastUpdatedId, $limit) as $row) {
            $recordCount++;
            $trackerId = $row->id;
            $providerId = $row->BillingProviderID;
            $patientId = $row->BillingInternalPatientID;
            $startDate = $row->Candidate_Start_Date;
            $totalHours = $row->Total_Hours;

            H::ExcelLog('Validating ' . $row->Candidate . ' (' . $trackerId . ') starting ' .
                $startDate . ' for ' . $totalHours . ' hours', $this->logFile);

            if ($totalHours > 0 && trim($providerId) != '' && trim($patientId) != ''
                && $startDate != '0000-00-00 00:00:00' && trim($startDate) != '') {
                $scheduleData = $this->queryScheduledHoursByWeek($patientId, $providerId, $startDate, $totalHours);

                H::ExcelLog('Hours by week: ' . print_r($scheduleData, true), $this->logFile);

                if (!empty($scheduleData)) {
                    echo 'Set schedulesBuilt for ' . $row->Candidate . ' - ' . $scheduleData[0]->WEEKSTART . "\n";
                    DB::table('mssql_recruitertracker')->where('id', $trackerId)->limit(1)->update(['schedules_built' => 1]);
                    H::ExcelLog('Update schedules_built for ' . $row->Candidate, $this->logFile);
                    $updatedCount++;
                } else {
                    echo 'Schedules have NOT been built for ' . $row->Candidate . "\n";
                    H::ExcelLog('Schedules have NOT been built for ' . $row->Candidate, $this->logFile);
                    $pendingCount++;
                }
            } else {
                echo 'Skip because total hours is empty' . "\n";
                $skippedCount++;
            }

            $lastUpdatedId = $trackerId;
            H::getHelper('Sync')->update_last_updated($this->syncType, date('Y-m-d H:i:s'), $lastUpdatedId);
        }

        echo 'processed: ' . $recordCount . "\n";
        echo 'Updated:   ' . $updatedCount . "\n";
        echo 'Skipped:   ' . $skippedCount . "\n";
        echo 'Pending:   ' . $pendingCount . "\n";
        echo 'finished:  ' . date('Y-m-d H:i:s') . "\n";

        H::getHelper('Sync')->stopBatch($this->syncType);

        return self::SUCCESS;
    }

    private function initBatch(): array
    {
        $batchStatus = H::getHelper('Sync')->startBatch($this->syncType);
        echo $batchStatus . "\n";

        if ($batchStatus == -1) {
            echo 'stop process';
            exit;
        }

        $batchInfo = H::getHelper('Sync')->get_batch_information($this->syncType);
        $lastUpdatedId = $batchInfo['last_updated_id'] ?: 0;
        $lastUpdatedDate = $batchInfo['last_updated_date'];

        if (date('Y-m-d') > $lastUpdatedDate) {
            echo 'Start over!';
            $lastUpdatedId = 0;
        }

        echo 'last_updated_id: ' . $lastUpdatedId . "\n";

        return [$lastUpdatedId, $batchInfo['batch_size']];
    }

    private function getTrackers(int $lastUpdatedId, int $limit): LazyCollection
    {
        return $this->recruiterTracker
            ->select(
                'mssql_recruitertracker.id',
                'mssql_recruitertracker.aoc_provider_id',
                'mssql_providers.BillingProviderID',
                'mssql_patients.BillingInternalPatientID',
                'mssql_recruitertracker.Candidate_Start_Date',
                'mssql_recruitertracker.Total_Hours',
                'mssql_recruitertracker.Candidate'
            )
            ->leftJoin('mssql_patients', 'mssql_patients.id', '=', 'mssql_recruitertracker.aoc_patient_id')
            ->leftJoin('mssql_providers', 'mssql_providers.id', '=', 'mssql_recruitertracker.aoc_provider_id')
            ->where(function ($query) {
                $query
                    ->where('Client_Match_Date', '!=', '0000-00-00 00:00:00')
                    ->whereNotNull('Client_Match_Date');
            })
            ->where('Client_Match_Date', '>', '2023-02-26')
            ->where('mssql_patients.Patient_CurrentStatusID', '!=', '4')
            ->where('mssql_recruitertracker.schedules_built', 0)
            ->where('mssql_recruitertracker.id', '>', $lastUpdatedId)
            ->limit($limit)
            ->cursor();
    }

    private function queryScheduledHoursByWeek(string $patientId, string $providerId, string $startDate, float $totalHours): array
    {
        // |AOC-4040 <-AOC-3434|  >= (not >) intentional: Candidate_Start_Date often falls on a Monday (week start),
        // so > would exclude that day's shifts and the week would never reach Total_Hours
        $tableRef = $this->bigQuery->tableRef(self::SCHEDULE_TABLE);

        // BigQuery syntax: DATE_TRUNC(date, part) — part is a keyword, not a string.
        // DATE(SCHEDULE_DATE) normalises DATE/DATETIME/TIMESTAMP; WEEK(MONDAY) matches the
        // Monday week start the old Snowflake DATE_TRUNC('WEEK', ...) relied on.
        $query = <<<SQL
            SELECT
                CAST(DATE_TRUNC(DATE(SCHEDULE_DATE), WEEK(MONDAY)) AS STRING) AS WEEKSTART,
                SUM(S_HOURS) AS HOURS
            FROM `$tableRef`
            WHERE S_CLIENT_ID = @patientId
              AND S_CAREGIVER_ID = @providerId
              AND DATE(SCHEDULE_DATE) >= PARSE_DATE('%F', @startDate)
            GROUP BY WEEKSTART
            HAVING SUM(S_HOURS) >= @totalHours
            ORDER BY WEEKSTART DESC
            SQL;

        $params = [
            'patientId'  => (int) $patientId,
            'providerId' => (int) $providerId,
            'startDate'  => substr($startDate, 0, 10),
            'totalHours' => $totalHours,
        ];

        H::ExcelLog($query . ' | params: ' . json_encode($params), $this->logFile);

        $rows = [];
        foreach ($this->bigQuery->cursor($query, $params) as $row) {
            $rows[] = (object) $row;
        }

        return $rows;
    }
}
