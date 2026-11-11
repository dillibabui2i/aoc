<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
class ProcessPatientProviderSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:patientproviderschedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Provider Patient max schedule to provider_patient table';
    protected $logFile = 'patient_provider_schedule.log';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $start_time = microtime(true);
        echo "Processing\n";
		// truncate provider_patient table data 
        echo "Truncate provider_patient table data\n";
        DB::statement("truncate table provider_patient");
        echo "Truncated the data\n";

        // get matching records

        $bucket = DB::select("select sm.ProviderID, sm.PatientID, max(sm.ScheduleDate) as ScheduleDate, mp.id as aoc_patient_id, pr.id as aoc_provider_id
								FROM mssql_schedulemaster sm
								LEFT JOIN mssql_patients mp on sm.PatientID = mp.PatientID
								LEFT JOIN mssql_providers pr on sm.ProviderID = pr.ProviderID
								WHERE ScheduleDate >= NOW()
								AND ifnull(mp.PatientID,0) <> 0
								AND ifnull(pr.ProviderID,0) <> 0
								GROUP BY mp.PatientID, pr.ProviderID
								");
        foreach($bucket as $record) {
			echo "Inserting data into provider_patient for Provider: ".$record->ProviderID." and Patient: ".$record->PatientID." and scheduleDate: ".$record->ScheduleDate."\n";
			H::ExcelLog("Inserting data into provider_patient for Provider: ".$record->ProviderID." and Patient: ".$record->PatientID." and scheduleDate: ".$record->ScheduleDate,$this->logFile);
			DB::statement("INSERT INTO provider_patient
							(aoc_provider_id, aoc_patient_id, last_schedule_date, ProviderID, PatientID)
							VALUES
							('".$record->aoc_provider_id."','".$record->aoc_patient_id."','".$record->ScheduleDate."','".$record->ProviderID."','".$record->PatientID."')");
		}
		echo "Processing Finished\n";
		H::ExcelLog("Processing Finished",$this->logFile);
		// End time
        $end_time = microtime(true);
		// Calculate execution time
		$execution_time = $end_time - $start_time;

		echo "Execution Time: " . number_format($execution_time, 6) . " seconds";
    }
}
