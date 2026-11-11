<?php

namespace App\Console\Commands;
use App\Events\Map\MapUpdatedEvent;
use App\SyncProcesses\NotificationSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;

class ProcessNTHAlerts extends Command
{
    private bool $mapTouched = false;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:nthalerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process NTH Alerts Lists';

    
    protected $logFile = 'process_nthalerts.log';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->currentDate = date('Y-m-d');

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $terminatedProviders = [];
        
       
        $providers = DB::table("mssql_providers")
        ->select(
            "mssql_providers.id",
            "mssql_providers.ProviderID",
            "mssql_providers.ClinicianID",
            DB::raw("concat(mssql_providers.FirstName,' ',mssql_providers.LastName) as ProviderName"),
            "mssql_providers.DisciplineName",
            "mssql_providers.Mobile",
            "mssql_providers.External_Email",
            "mssql_employmentstatus.EmploymentStatus",
            "mssql_providers.Gender",
            "mssql_providers.Bilingual",
            "mssql_facilities.FacilityName",
            "mssql_providers.City",
            "mssql_providers.Address1",
            "mssql_providers.Address2",
            "mssql_providers.State",
            "mssql_providers.Zip",
            "mssql_providers.Language",
            "mssql_providers.aoc_cf_city",
            "mssql_providers.aoc_cf_state",
            "mssql_providers.aoc_cf_mile_radius",
            "mssql_providers.aoc_cf_skills",
            "mssql_providers.aoc_cf_schedule",
            "mssql_providers.aoc_cf_environment",
            "mssql_providers.aoc_cf_lifting_requirements",
            "mssql_providers.aoc_cf_language",
            'mssql_providers.CF_RemoveFromTextBlast',
            'mssql_providers.CF_NotEligibleForRehire',
            'mssql_providers.CF_DoNotContact',
            'mssql_providers.SMS_Status',
            'mssql_providers.Preference_Status',
            "maps_nthalerts.created_at"
        )
        ->leftJoin("mssql_employmentstatus","mssql_employmentstatus.EmploymentStatusID","=","mssql_providers.EmploymentStatusID")
        ->leftJoin("mssql_facilities","mssql_facilities.FacilityID","=","mssql_providers.FacilityID")
        ->leftJoin("maps_nthalerts","mssql_providers.ClinicianID","=","maps_nthalerts.ClinicianID")
        ->where("mssql_providers.is_deleted",0)
        ->whereIn("mssql_providers.DisciplineName",["RN","LVN","LPN"])
        ->where(function($q){
            $q->where("mssql_providers.SMS_Status","!=","Unsubscribed")->orWhereNull("mssql_providers.SMS_Status");
        })->orderBy("id")
        ->get();
                    
        H::ExcelLog("Total Providers : ".$providers->count(),$this->logFile);
        echo "\n Total Providers :".$providers->count()."\n";
        $insert = [];
        $update = [];
        $updateProviders = [];
        foreach($providers as $provider) {
            H::ExcelLog("\n\nProessing : ".$provider->id,$this->logFile);
            [$include,$average,$SMSstatus,$reason] = $this->allowedRules($provider);
           
        
            H::ExcelLog("Can Include : ".$include,$this->logFile);
            H::ExcelLog("Weekly Average : ".$average,$this->logFile);
            H::ExcelLog("SMS Status : ".$SMSstatus,$this->logFile);
            H::ExcelLog("Reason : ".$reason,$this->logFile);

            if(!empty($SMSstatus)) {
                $updateProviders[$SMSstatus][] = $provider->id;
                H::ExcelLog("Current Status : ".$provider->SMS_Status,$this->logFile);
                H::ExcelLog("New Status : ".$provider->SMS_Status,$this->logFile);
                if($provider->SMS_Status!=$SMSstatus){
                    $noteData = [];
                    $noteData['entity_id'] = $provider->id;
                    $noteData['entity'] = 'clinician';
                    $noteData['notes'] = $reason;
                    H::getHelper("ActivityNotes")->setActivityNotesFromConsole($noteData);
                    H::ExcelLog("Set Activity Note for Status change ",$this->logFile);
                }
            }

            var_dump($provider->ClinicianID."->".$provider->created_at."->".$include."->".$SMSstatus."->".$average);

            if(empty($provider->created_at)) {
                if($include) {
                    $insert[] = $this->createDataArray("insert",$provider,$average,$SMSstatus,1);
                    H::ExcelLog("Prepared Data for Insert",$this->logFile);
                }
                   
            } else {
                if($include) {
                    $update[] = $this->createDataArray("update",$provider,$average,$SMSstatus,1);
                    H::ExcelLog("Updating Clinician (Active)",$this->logFile);
                } else {
                    $update[] = $this->createDataArray("update",$provider,$average,$SMSstatus,0);
                    H::ExcelLog("Updating Clinician (InActive)",$this->logFile);
                }
                   

            } 
            H::ExcelLog("Finished\n\n ".$provider->id,$this->logFile);
        }

        // updating providers table sms status

        foreach($updateProviders as $index => $value) {
            DB::table("mssql_providers")->whereIn("id",$value)->update(["SMS_Status"=>$index]);
        }
       
        echo "\n\nInserting";
        echo "Total Insert : ".count($insert);
        H::ExcelLog("Total Insert....".count($insert),$this->logFile);
        H::ExcelLog("Inserting....",$this->logFile);
        if(!empty($insert)) {
            $insertChunks = array_chunk($insert, 500);

            foreach($insertChunks as $chunk) {
                DB::table("maps_nthalerts")->insert($chunk);
                echo "\n Inserting.... \n";
                sleep(1); 
            }

            $this->mapTouched = true;
        }
        H::ExcelLog("\nInsert Finished....",$this->logFile);
        H::ExcelLog("\nUpdating",$this->logFile);

        echo "\n Insert finished";
        echo "\n\nTotal Update : ".count($update);
        H::ExcelLog("Total Insert....".count($update),$this->logFile);
        echo "\nUpdating";
        foreach($update as $data) {
            DB::table("maps_nthalerts")->where("ClinicianID",$data['ClinicianID'])->update($data);
        }
        $this->mapTouched = true;

        if ($this->mapTouched) {
            event(new MapUpdatedEvent('maps_nthalerts'));
        }

        H::ExcelLog("Update Finished",$this->logFile);
    }

    private function checkMobile($mobile) {
        $mobile = str_replace("-","",$mobile);
        $twillio = DB::table("twilio_error_logs")->select("error_code")->where("phone_number","like","%".$mobile."%")->first();
        return $twillio;
    }

    private function allowedRules($provider){

        $canInclude = 0;
        $SMSstatus = 'Inactive - Case Assignment';
        $average = 0;
        $reason = 'Status: Inactive';
        $defaultNote = 'Update to NTH Alerts Subscription Status <br>';
        H::ExcelLog("Clnicain DB Status : ".$provider->EmploymentStatus,$this->logFile);
        
        if($provider->EmploymentStatus=='Active' || $provider->EmploymentStatus=='Pending Renewable') {
            $schedules = $this->getScheduledHourandService($provider->ProviderID);
            $average = $this->getScheduleCount($schedules,["average"]);
            
            if($average < 12) {
                $canInclude = 1;
                $SMSstatus = 'Active';
                $reason = 'Status: Active';
            } else {
                $canInclude = 0;
                $SMSstatus = 'Inactive - Case Assignment';
                $reason = 'Status: Inactive';
                $reason.="<br>Reason: ".$SMSstatus;
            }
            
        } else if($provider->EmploymentStatus=='Active Coverage') {
            $scheduleData = ["ServiceName"=>"%Coverage%"];
            $schedules = $this->getScheduledHourandService($provider->ProviderID,$scheduleData);
            $total = $this->getScheduleCount($schedules,["total"]);
            $average = $this->getScheduleCount($schedules,["average"]);

            if($total > 12) {
                $canInclude = 0;
                $SMSstatus = 'Inactive - Case Assignment';
                $reason= 'Status: Inactive';
                $reason.="<br>Reason: ".$SMSstatus;
            } else {
                $canInclude = 1;
                $SMSstatus = 'Active';
                $reason = 'Status: Active';
                
            }

        }  else if($provider->EmploymentStatus=='PRN Agreement' 
            || $provider->EmploymentStatus=='Standby' 
            || $provider->EmploymentStatus=='Inactive'
            ||  $provider->EmploymentStatus=='Terminated' ) {
            
                $schedules = $this->getScheduledHourandService($provider->ProviderID);
                
                $average = $this->getScheduleCount($schedules,["average"]);
                $canInclude = 1;
                $SMSstatus = 'Active';
                $reason = 'Status: Active';

        } 

        if($SMSstatus=='Active') {
            if($provider->CF_RemoveFromTextBlast==1 
                || $provider->CF_NotEligibleForRehire==1 
                || $provider->CF_DoNotContact==1) {
                    $canInclude = 0;
                    $SMSstatus = 'Inactive - Advocacy';

                    $reason = 'Status: Inactive';
                    $reason.="<br>Reason: ".$SMSstatus;
                    
            } else if($this->checkMobile($provider->Mobile)) {
                $canInclude = 0;
                $SMSstatus = 'Inactive - Bad phone number';
                $reason = 'Status: Inactive';
                $reason.="<br>Reason: ".$SMSstatus;
            }
        }
        return [$canInclude,$average,$SMSstatus,$defaultNote.$reason];

    }


   

    private function getScheduleCount($schedules,$op = []){
        $operation = $op[0];
        $value = 0;
        $sum = 0;
        
        if($operation=='average') {
            foreach($schedules as $schedule) {
                $sum+=$schedule->Hours;
            }
            
            if($sum)
                return $sum/4;
        }

        if($operation=='total') {
            foreach($schedules as $schedule) {
                $sum+=$schedule->Hours;
            }
            if($sum)
                return $sum;
        }
        return 0;
        
    }

    private function getScheduledHourandService($providerId,$data = []) {
        [$lastWeek,$nextWeek] = $this->getDateRange();
        //var_dump($lastWeek);
        //var_dump($nextWeek);
        $schedules = DB::table("mssql_schedulemaster")
        ->select("ServiceName","Hours")
        ->leftJoin("service_codes_daas","mssql_schedulemaster.ServiceCodeID","=","service_codes_daas.service_id")
        ->where("mssql_schedulemaster.IsBillable",1)
        ->where("ProviderID",$providerId)
        ->whereIn("mssql_schedulemaster.ScheduleStatusID",[1,2,3,5])
        ->whereDate("mssql_schedulemaster.ScheduleDate",">",$lastWeek)
        ->whereDate("mssql_schedulemaster.ScheduleDate","<=",$nextWeek);
        
        foreach($data as $index => $value){
            if($index=='ServiceName') {
                $schedules->where("service_codes_daas.name",'not like',$value);
            }
        }
        $scheduleData = $schedules->get();

        return $scheduleData;
        
    }

    private function createDataArray($mode,$provider,$average,$SMSstatus,$isActive=1) {

        $pref = $provider->Preference_Status;

        if(empty($provider->aoc_cf_city) && empty($provider->aoc_cf_state) && empty($provider->aoc_cf_mile_radius)){
            $pref = "0";
        }

        $data = [
            'ClinicianID' => $provider->ClinicianID,
            'ProviderName' => $provider->ProviderName,
            'Discipline' => $provider->DisciplineName,
            'MobilePhone' => $provider->Mobile,
            'ExternaleEmail' => $provider->External_Email,
            'ProviderStatus' => $provider->EmploymentStatus,
            'Gender' => $provider->Gender,
            'WeeklyAverage' => $average,
            'IsBilingual' => $provider->Bilingual,
            'HomeBranch' => $provider->FacilityName,
            'Address1' => $provider->Address1,
            'Address2' => $provider->Address2,
            'City' => $provider->City,
            'State' => $provider->State,
            'Zip' => $provider->Zip,
            'Language' => $provider->Language,
            'City_Preference' => $provider->aoc_cf_city,
            'State_Preference'=>$provider->aoc_cf_state,
            'Mile_Preference' => $provider->aoc_cf_mile_radius,
            'Schedule_Preference' => $provider->aoc_cf_skills,
            'Skill_Preference' => $provider->aoc_cf_schedule,
            'Environment_Preference' => $provider->aoc_cf_environment,
            'Lifting_Preference' => $provider->aoc_cf_lifting_requirements,
            'Language_Preference' => $provider->aoc_cf_language,
            'Preference_Status' => $pref,
            'SMS_Status' => $SMSstatus,
            'is_active'=>$isActive,
        ];

        if($mode=='insert') {
            $data['created_at']=date("Y-m-d H:i:s");
            $data['updated_at']=date("Y-m-d H:i:s");
        } else {
            $data['updated_at']=date("Y-m-d H:i:s");
        }

        return $data;
    }

   
        

    private function getDateRange(){
        $lastWeek  = date('Y-m-d', strtotime("sunday -3 week"));
        $nextWeek  = date('Y-m-d', strtotime("sunday 1 week"));

        return [$lastWeek,$nextWeek];
    }



}
