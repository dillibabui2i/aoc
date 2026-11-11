<?php

namespace App\Console\Commands;
use App\SyncProcesses\NotificationSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;

class ProcessMissingRenewables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:missing_renewables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inserting the missing renewables with checking renewables rules with clinicians';

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
        // Fetching all the rules
        $rules = DB::table('renewable_rules')->get();
        foreach ($rules as $key => $rule) {
            $insertArray = [];

            //Fetching renewables_type_id from the renewables_types based on the ID
            $renewableTypeId = DB::table('renewables_types')->where('id',$rule->renewable_type_id)->first();
            $renewableTypeId = $renewableTypeId->renewable_type_id;

            // Fetching the Clinician based on the rules
            $clinicianQuery = DB::table('mssql_providers')->select('id','BillingProviderID')
            ->whereIn('mssql_providers.EmploymentStatusID',
            config('constant')['renewableClinicianStatusIds'])
            ->where('mssql_providers.is_deleted',0);
            if($rule->isClinician == 1 && $rule->isOfficeStaff == 1){
                $clinicianQuery->where(function ($q){
                    $q->orWhere('IsClinician',1)->orWhere('IsOfficeStaff',1);
                });
            }else if($rule->isClinician == 1){
                $clinicianQuery->where('IsClinician',1);
            }else if($rule->isOfficeStaff == 1){
                $clinicianQuery->where('IsOfficeStaff',1);
            }
            if($rule->states != ''){
                $states = explode(',',$rule->states);
                $clinicianQuery->whereIn('State',$states);
            }
            if($rule->discipline != '' && $rule->discipline != 'ALL STAFF'){
                $discipline = explode(',',$rule->discipline);
                $clinicianQuery->whereIn('DisciplineName',$discipline);
            }

            //Checking the if the rules has "client is over 21"

            if($rule->has_client_over21 == 1){
                $clinicianQuery->whereExists(function ($query) {
                    $query->selectRaw(1)->from('mssql_schedulemaster as sm')
                    ->leftJoin('mssql_patients as p', 'sm.PatientID', '=', 'p.PatientID')
                    ->whereColumn('sm.ProviderID', 'mssql_providers.ProviderID')
                    ->where('p.Patient_DOB', '<', DB::raw('DATE_SUB(NOW(), INTERVAL 21 YEAR)'))
                    ->where('sm.ScheduleDate', '>=', DB::raw('CURDATE()'))
                    ->where('sm.ScheduleStatusID', '=', 5)
                    ->limit(1);
                });
            }
            //P1-6028 Remove student clinicians from sync
            $clinicianQuery->where('Designation',"!=", 'Student Therapist');
            $clinicianData = $clinicianQuery->get();
            $clinicianData = $allClinician = json_decode(json_encode($clinicianData), true);

            // Checking those clinician into the renewable table if those are exist or not
            if(!empty($clinicianData)){
                $clinicianData = array_column($clinicianData,'id');
				H::ExcelLog('Clinician Data:'.print_r($clinicianData, true), 'renewable.log');
                $availableRenewable = DB::table('renewables')->select('aoc_provider_id')->where('renewables_type',$renewableTypeId)->get();
                $availableRenewable = json_decode(json_encode($availableRenewable), true);
                $availableRenewable = array_column($availableRenewable,'aoc_provider_id');
				H::ExcelLog('Available Renewable Data:'.print_r($availableRenewable, true), 'renewable.log');
                $missingRenewable = array_diff($clinicianData,$availableRenewable);
				H::ExcelLog('missingRenewable Data:'.print_r($missingRenewable, true), 'renewable.log');
                // Preparing the array for inserting the missing renewables
                $insertChunks = array_chunk($missingRenewable, 500);
				
                foreach ($insertChunks as $insertChunk) {
                    $batchInsertArray = [];
                    foreach ($insertChunk as $v) {
                        $key = array_search($v, array_column($allClinician, 'id'));
                        $providerId = $allClinician[$key]['BillingProviderID'];
                        $batchInsertArray[] = [
                            'renewables_type' => $renewableTypeId,
                            'aoc_provider_id' => $v,
                            'isMissing' => 1,
                            'ProviderID'=>$providerId,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }
                    DB::table('renewables')->insert($batchInsertArray);
					H::ExcelLog('Missing renewable inserted of rules ID:'.$rule->id.print_r($batchInsertArray, true), 'renewable.log');
                }

                //Updating the current renewable to isMissing = 1 which has Expiration Date = '0000-00-00' and isMissing = 0 when the rule contain the Expiring = 1.

                //Commenting out this condition because of they want all renewables as missing
                //if the expiring date is 0000-00-00 as per TICKET-2167

                // if ($rule->Expiring == 1){
                    DB::table('renewables')->where('renewables_type',$renewableTypeId)->where(function ($q) {
                        $q->where('expiration_date',0)->orWhere('expiration_date','0000-00-00');
                    })->where('isMissing',0)->update(['isMissing'=>1]);

                    H::ExcelLog('Updating the current renewable to isMissing = 1 which has Expiration Date = "0000-00-00" and isMissing = 0 when the rule contain the Expiring = 1 rule ID:'.$rule->id, 'renewable.log');
                // }

            }else{
                H::ExcelLog('Clinician Data not found for this rule ID:'.$rule->id, 'renewable.log');
            }
        }
        return 0;
    }
}
