<?php

namespace App\Console\Commands;

use App\Helpers\H;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetHasBillableShiftCoverageTracker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setbillableshift:coveragetracker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set has billable shift for coverage tracker table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try{
            DB::statement("update `coverage_tracker` ct
                            left join mssql_patients mp on ct.aoc_patient_id = mp.id
                            left join mssql_providers p2 on ct.aoc_provider_id = p2.id
                            set ct.has_billable_visit = IF(isnull(
                                (
                                    select sm.ScheduleDate from mssql_schedulemaster sm where
                                    sm.ProviderID = p2.ProviderID and sm.PatientID = mp.PatientID and sm.ScheduleStatusID in (1,2,3)
                                    and sm.isBillable = 1 and sm.ScheduleDate = ct.shift_date limit 1)
                                ) = false, 1, 0)
                            WHERE COALESCE(ct.has_billable_visit,0) = 0");
        }catch (\Exception $e){
            H::ExcelLog('Error: ' . $e->getMessage(), 'setbillableshift_coveragetracker.log');
        }
        return Command::SUCCESS;
    }
}
