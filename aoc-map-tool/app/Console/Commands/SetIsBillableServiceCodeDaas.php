<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;

class SetIsBillableServiceCodeDaas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scd:setisbillable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'set is billable field into service code daas table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try{
            DB::statement("update `service_codes_daas` set is_billable = 0");
			DB::statement("UPDATE service_codes_daas sc
							JOIN (
								SELECT DISTINCT ServiceCodeID 
								FROM mssql_schedulemaster 
								WHERE IsBillable = 1
							) AS sub ON sc.service_id = sub.ServiceCodeID
							SET sc.is_billable = 1;");
        }catch (\Exception $e){
            H::ExcelLog('Error: ' . $e->getMessage(), 'SetIntakeShiftInfo2.log');
        }
        return Command::SUCCESS;
    }
}
