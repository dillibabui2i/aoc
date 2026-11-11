<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
class ProcessMissedVisitsBucket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:missedvisitsbucket';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Missed Visits Bucket Data';
    protected $logFile = 'schedule_master_missedvisits.log';

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
		// reset any missed visits
        $previousYearFirstDate = date('Y-01-01', strtotime('first day of January last year'));
        $today = date('Y-m-d');
        $exceptLineOfBusiness = ['1003', '1004', '1006'];

        echo "Resetting\n";
        DB::table("mssql_schedulemaster")->where("is_no_provider",">",0)->update(["is_no_provider"=>0]);
        echo "Resetting Finished\n";

        // get matching records

         $bucket = DB::table("mssql_schedulemaster")->select("mssql_schedulemaster.id",
        DB::raw("IFNULL(mssql_schedulemaster.ProviderID,0) as providerId"));

            $bucket->leftJoin("mssql_patients",function($query){
                $query->on("mssql_patients.PatientID","=","mssql_schedulemaster.PatientID");
            });
            $bucket->leftJoin("service_codes_daas",function($query){
                $query->on("service_codes_daas.service_id","=","mssql_schedulemaster.ServiceCodeID");
            });
        $bucket->whereNotIn("mssql_patients.LOBID",$exceptLineOfBusiness);
            $bucket->where('mssql_schedulemaster.ScheduleStatusID','=',5);
            $bucket->whereBetween("mssql_schedulemaster.ScheduleDate",[$previousYearFirstDate, $today]);
            $bucket->where('mssql_schedulemaster.IsBillable',1);
            $bucket->where('mssql_schedulemaster.isChild','<>',1);

            $bucket->whereIn("service_codes_daas.name",[
                'CNA Group Additional',
                'CNA Group Primary',
                'CNA Long Term',
                'CNA Long Term EVV EXEMPT',
                'CNA Long Term Relative Caregiver',
                'Coverage LVN/LPN',
                'Coverage LVN/LPN MDCP',
                'Coverage LVN/LPN Specialized',
                'Coverage RN',
                'Coverage RN MDCP',
                'Coverage RN Specialized',
                'Coverage RN Specialized MDCP',
                'Coverage RN to LVN/LPN',
                'IHA CNA',
                'IHSS Health Maintenance- Outside Denver',
                'IHSS Homemaker- Outside Denver',
                'IHSS Relative Personal Care- Outside Denver',
                'LHA In Home Caregiver',
                'LPN',
                'LPN GROUP ADDITIONAL',
                'LPN GROUP PRIMARY',
                'LPN Visit Diabetic',
                'LPN Visit HNV',
                'LVN/LPN',
                'LVN/LPN MDCP',
                'LVN/LPN SPECIALIZED',
                'LVN/LPN SPECIALIZED MDCP',
                'Personal Attendant Services (Non-SPW)',
                'Personal Attendant Services (SPW)',
                'Personal Attendant Services (SPW) (CFC)',
                'Personal Care Services',
                'Personal Care Services- Behavioral',
                'Personal Care Services CFC Habilitation MDCP',
                'PNA CNA',
                'Respite Coverage RN MDCP',
                'RESPITE LVN/LPN MDCP',
                'RESPITE LVN/LPN SPECIALIZED MDCP',
                'Respite PAS',
                'Respite PAS MDCP',
                'RN',
                'RN EVV EXEMPT',
                'RN MDCP',
                'RN SPECIALIZED',
                'RN SPECIALIZED MDCP',
                'RN TO LVN SPECIALIZED',
                'RN TO LVN/LPN',
                'SV RN'
            ])->orWhere('service_codes_daas.name', 'LIKE', '%respite%');
            $records = $bucket->get();


            $dataArray = [];

            foreach($records as $record) {
                if($record->providerId) {
                    $dataArray[1][] = $record->id;
                } else {
                    $dataArray[2][] = $record->id;
                }
            }

            foreach($dataArray as $index => $data) {

                if(!empty($data)) {
                    $updateChunks = array_chunk($data, 500);
                    $count = 0;
                    foreach($updateChunks as $chunk) {
                        DB::table("mssql_schedulemaster")->whereIn("id",$chunk)->update(['is_no_provider'=>$index]);

                        echo "\n Updating.... \n";
                        H::ExcelLog("Updating....".print_r($chunk,true),$this->logFile);
                        sleep(1);
                    }
                }

            }


                        // End time
            $end_time = microtime(true);

            // Calculate execution time
            $execution_time = $end_time - $start_time;

            echo "Execution Time: " . number_format($execution_time, 6) . " seconds";

       return 0;
    }


}
