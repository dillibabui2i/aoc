<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SetHasBillableVisitShiftRecruitingSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setbillableshift:recruitingschedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set has billable shift for recruiting schedule table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        try {

            $sql1 = $this->buildRecruitingScheduleUpdate(
                'mssql_providers',
                'sm.ScheduleDate = rs.appointment_date'
            );

            DB::beginTransaction();
            DB::affectingStatement($sql1);
            DB::commit();

            $this->info('mssql_providers 1 was updated');

            $sql2 = $this->buildRecruitingScheduleUpdate(
                '`mssql_providers-03212025`',
                'DATE(sm.ScheduleDate) = rs.appointment_date'
            );

            DB::beginTransaction();
            DB::affectingStatement($sql2);
            DB::commit();

            $this->info('mssql_providers-03212025 was updated');

            return Command::SUCCESS;

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            DB::rollBack();
            $this->error($exception->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * @param string $providersTable
     * @param string $dateConditionSql
     * @return string
     */
    private function buildRecruitingScheduleUpdate(string $providersTable, string $dateConditionSql): string
    {
        return "
            UPDATE `recruiting_schedule` rs
            LEFT JOIN mssql_recruitertracker rt ON rs.tracker_id = rt.id
            LEFT JOIN mssql_patients p1         ON p1.id = rs.aoc_patient_id
            LEFT JOIN {$providersTable} p2      ON p2.id = rt.aoc_provider_id
            SET rs.has_billable_visit = IF(
                ISNULL((
                    SELECT sm.ScheduleDate
                    FROM mssql_schedulemaster sm
                    WHERE sm.ProviderID       = p2.ProviderID
                      AND sm.PatientID        = p1.PatientID
                      AND sm.ScheduleStatusID IN (1,2,3)
                      AND {$dateConditionSql}
                    LIMIT 1
                )) = FALSE, 1, 0
            )
            WHERE COALESCE(rs.has_billable_visit, 0) = 0
        ";
    }
}
