<?php
namespace App\Helpers;

use App\Helpers\LD;
use App\Helpers\H;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecruitingException
{
    public $buckets = [
            "nth_clients_with_no_activity"=>["heading"=>"NTH CLIENTS WITH NO APPOINTMENTS WITHIN 14 DAYS"],
            "nth_clients_with_no_recruiter"=>["heading"=>"NTH Clients With No Recruiter"]
        ];


    public function getBuckets(){
        if (LD::checkFeatureFlag('disabled-recruiter-exceptions')) {
            if (H::canPermissions('dashboard.active.tracker.with.inactive.recruiter')) {
                $this->buckets["nth_active_tracker_disabled_recruiter"] = ["heading"=>"Active Tracker with Inactive Recruiter"];
            }
        }
        $this->buckets["mismatched_first_shift_confirmed"] = ["heading"=>"Mismatched First Shifts Confirmed"];
        return $this->buckets;
    }

    
    public function getBucketTitle($type) {
        return $this->buckets[$type]['heading'];
    }

    public function getDataTableHtml($type)
    {
        
        $dataTable = [];
        switch($type) {
            case 'nth_clients_with_no_recruiter':
                $dataTable = ['patient_name'=>'Client','PatientAccount'=>'Account','FacilityName'=>'Location','Client_Coordinator'=>'Client Care Coordinator','CreatedOn'=>"CreatedOn",'days_since_created'=>"Days Since Created"];
                break;
            case 'nth_active_tracker_disabled_recruiter':
                $dataTable = ['Candidate'=>'Candidate','Candidate_Current_Status'=>'Status','FacilityName'=>'Branch','name'=>'Recruiter'];
                break;
            case 'mismatched_first_shift_confirmed':
                $dataTable = ['name'=>'Recruiter','FacilityName'=>'Branch Location','Candidate'=>'Candidate','patient_name'=>'Client','Candidate_Current_Status'=>'Candidate Current Status','Candidate_Start_Date'=>'Candidate Start Date','Candidate_Notes'=>'Candidate Notes'];
                break;
            default:
                $dataTable = ['patient_name'=>'Patient Name','ClientID'=>'ClientID','Recruiter'=>'Recruiter','CurrentStep'=>'NTH Step','lastActivity'=>'Last Activity'];
                break;
        }
      
        return view('recruitingexception.datatable',["type"=>$type,'dataTable'=>$dataTable]);

    }

    public function getRecruitingException($type, $request = '',$showCount=0) {
        $request = $request->all();
        $states = [];
        $branch = [];
        if($type=='mismatched_first_shift_confirmed') {
            $bucket = DB::table("mssql_recruitertracker");
            $bucket->leftJoin("mssql_facilities",function($query){
                $query->on("mssql_recruitertracker.aoc_facility_id","=","mssql_facilities.id");
            });
            $bucket->leftJoin("users",function($query){
                $query->on("users.id","=","mssql_recruitertracker.user_id");
            });
            $candidateStartDateLimit = '2023-06-04';
            $bucket->leftJoin("mssql_providers","mssql_recruitertracker.aoc_provider_id", "=", "mssql_providers.id");

            $bucket->leftJoin("mssql_patients",function($query){
                $query->on("mssql_patients.id","=","mssql_recruitertracker.aoc_patient_id");
            });
            $bucket->select("mssql_recruitertracker.id","mssql_recruitertracker.Candidate_Notes",DB::raw("DATE_FORMAT(mssql_recruitertracker.Candidate_Start_Date, '%m/%d/%Y') as Candidate_Start_Date"),"mssql_recruitertracker.Candidate","mssql_recruitertracker.Candidate_Current_Status","mssql_facilities.FacilityName","users.name",DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"));
            $bucket->where('users.status', 2);
            $bucket->where('mssql_recruitertracker.is_active', 1);
            
           $bucket->whereDate('mssql_recruitertracker.Candidate_Start_Date', ">",$candidateStartDateLimit);
            $bucket->where('mssql_recruitertracker.Candidate_Current_Status','=','First Shift Confirmed');
            $bucket->where('mssql_recruitertracker.Bonus_Status','=','Pending Hours');
            $bucket->whereRaw("isnull((select sm.ScheduleDate from mssql_schedulemaster sm where sm.ProviderID = mssql_providers.ProviderID and  sm.PatientID = mssql_patients.PatientID and sm.ScheduleStatusID in (1,2,3) and sm.ScheduleDate = mssql_recruitertracker.Candidate_Start_Date limit 1)) = true");

            if (LD::checkFeatureFlag('client-not-kantime-exclude-from-first-mismatch-bucket')) {
                $bucket->where("mssql_patients.not_in_kantime", 0);
            }
           
           if(isset($request['filters'])) {
                    $region = [];
                    foreach($request['filters'] as $key => $value){
                        if(LD::checkFeatureFlag('make-exception-bucket-filter-multiselect')){
                            if($value['name']=='recruiter-state') {
                                if(!empty($value['value'])) {
                                    $states[] = $value['value'];
                                }
                            }
                            if($value['name']=='recruiter-aocfacility_id'){
                                if(!empty($value['value'])) {
                                    $branch[] = $value['value'];
                                }
                            }
                        }else{
                            if($value['name']=='recruiter-state') {
                                if(!empty($value['value'])) {
                                    $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($value['value']);
                                    $bucket->where(function($q) use ($facilities){
                                        foreach($facilities as $facility) {
                                            
                                            $q->orWhere("mssql_recruitertracker.aoc_facility_id","=",$facility->id);
                                        }
                                    });
                                }
                            }
                            if($value['name']=='recruiter-aoc_facility_id') {
                                if(!empty($value['value'])) {
                                    $bucket->where("mssql_recruitertracker.aoc_facility_id",'=',$value['value']);
                                }
                            }
                        }
                        if($value['name']=='recruitingregion') {
                            if(!empty($value['value'])) {
                                $region[] = $value['value'];
                            }
                        }
                    }
               
                    if(LD::checkFeatureFlag("region-filter-for-all-dashboard")) {
                        if(!empty($region)){
                            $bucket->where(function($query) use ($region) {
                                foreach($region as $val) {
                                    $query->orWhere("mssql_facilities.region",'=',$val);
                                }
                            });
                        }
                    }
                    if(LD::checkFeatureFlag('make-exception-bucket-filter-multiselect')){
                        if(!empty($branch)){
                            $bucket->where(function($query) use ($branch) {
                                foreach($branch as $val) {
                                    $query->orWhere("mssql_recruitertracker.aoc_facility_id",'=',$val);
                                }
                            });
                        }
                        $fid = [];
                        if(!empty($states)){
                            foreach($states as $state){
                                $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
                                foreach($facilities as $facility){
                                    $fid[] = $facility->id;
                                }
                            }
                            $bucket->whereIn("mssql_recruitertracker.aoc_facility_id", $fid);
                        }
                    }
               
            }

        }else if($type=='nth_active_tracker_disabled_recruiter') {
            $bucket = DB::table("mssql_recruitertracker");
            $bucket->leftJoin("mssql_facilities",function($query){
                $query->on("mssql_recruitertracker.aoc_facility_id","=","mssql_facilities.id");
            });
            $bucket->leftJoin("users",function($query){
                $query->on("users.id","=","mssql_recruitertracker.user_id");
            });
            $bucket->select("mssql_recruitertracker.id","mssql_recruitertracker.Candidate","mssql_recruitertracker.Candidate_Current_Status","mssql_facilities.FacilityName","users.name");
            $bucket->where('users.status', 2);
            $bucket->where('mssql_recruitertracker.is_active', 1);
            $bucket->where('mssql_recruitertracker.Candidate_Current_Status','<>','First Shift Confirmed');
            $bucket->where('mssql_recruitertracker.Candidate_Current_Status','<>','Inactive');
            $bucket->where('mssql_recruitertracker.Candidate_Current_Status','<>','Bonus Paid');

            if(isset($request['filters'])) {


                $region = [];
                foreach($request['filters'] as $key => $value){
                    if(LD::checkFeatureFlag('make-exception-bucket-filter-multiselect')){
                        if($value['name']=='recruiter-state') {
                            if(!empty($value['value'])) {
                                $states[] = $value['value'];
                            }
                        }
                        if($value['name']=='recruiter-aocfacility_id'){
                            if(!empty($value['value'])) {
                                $branch[] = $value['value'];
                            }
                        }
                    }else{
                        if($value['name']=='recruiter-state') {
                            if(!empty($value['value'])) {
                                $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($value['value']);
                                
                                $bucket->where(function($q) use ($facilities){
                                    foreach($facilities as $facility) {
                                        
                                        $q->orWhere("mssql_recruitertracker.aoc_facility_id","=",$facility->id);
                                    }
                                });
                            }
                        }
                        if($value['name']=='recruiter-aoc_facility_id') {
                            if(!empty($value['value'])) {
                                $bucket->where("mssql_recruitertracker.aoc_facility_id",'=',$value['value']);
                            }
                        }
                    }
                    if($value['name']=='recruitingregion') {
                        if(!empty($value['value'])) {
                            $region[] = $value['value'];
                        }
                    }
                  
                   
                }
                if(LD::checkFeatureFlag("region-filter-for-all-dashboard")) {
                    if(!empty($region)){
                        $bucket->where(function($query) use ($region) {
                            foreach($region as $val) {
                                $query->orWhere("mssql_facilities.region",'=',$val);
                            }
                        });
                    }
                }
                if(LD::checkFeatureFlag('make-exception-bucket-filter-multiselect')){
                    if(!empty($branch)){
                        $bucket->where(function($query) use ($branch) {
                            foreach($branch as $val) {
                                $query->orWhere("mssql_recruitertracker.aoc_facility_id",'=',$val);
                            }
                        });
                    }
                    $fid = [];
                    if(!empty($states)){
                        foreach($states as $state){
                            $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
                            foreach($facilities as $facility){
                                $fid[] = $facility->id;
                            }
                        }
                        $bucket->whereIn("mssql_recruitertracker.aoc_facility_id", $fid);
                    }
                }
            }

        } else {
            $bucket = DB::table("NeedToHire_Accounts");
            $bucket->select('NeedToHire_Accounts.*','NeedToHire_Checklist.CurrentStep',
            DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"),
            DB::raw("(select max(recruiting_schedule.scheduled_date) from recruiting_schedule where recruiting_schedule.aoc_patient_id = NeedToHire_Accounts.aoc_patient_id) as lastActivity"));
        
            $bucket->leftJoin("mssql_patients",function($query){
                $query->on("mssql_patients.id","=","NeedToHire_Accounts.aoc_patient_id");
            });
            
            $bucket->leftJoin("NeedToHire_Checklist",function($query){
                $query->on("NeedToHire_Accounts.id","=","NeedToHire_Checklist.aoc_nthaccounts_id");
            });
    
            $bucket->where("NeedToHire_Accounts.IsActive",'=',"1");

            if($type=='nth_clients_with_no_recruiter') {

                $bucket->leftJoin("mssql_facilities",function($query){
                    $query->on("mssql_patients.aoc_facility_id","=","mssql_facilities.id");
                });

                $bucket->leftJoin("users",function($query){
                    $query->on("users.id","=","mssql_patients.ccc_id");
                });

                $bucket->addSelect("mssql_facilities.FacilityName",DB::raw("DATEDIFF(now(),NeedToHire_Accounts.CreatedOn) AS days_since_created"),'mssql_patients.PatientAccount',DB::raw("users.name AS Client_Coordinator"));

                $bucket->where(function($query) {
                    $query->where("NeedToHire_Accounts.user_id",'=',0)->orWhereNull('NeedToHire_Accounts.user_id');
                });

                if(isset($request['filters'])) {

                    $region = [];
                    foreach($request['filters'] as $key => $value){
                        if(LD::checkFeatureFlag('make-exception-bucket-filter-multiselect')){
                            if($value['name']=='recruiter-state') {
                                if(!empty($value['value'])) {
                                    $states[] = $value['value'];
                                }
                            }
                            if($value['name']=='recruiter-aocfacility_id'){
                                if(!empty($value['value'])) {
                                    $branch[] = $value['value'];
                                }
                            }
                        }else{
                            if($value['name']=='recruiter-state') {
                                if(!empty($value['value'])) {
                                    $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($value['value']);
                                    
                                    $bucket->where(function($q) use ($facilities){
                                        foreach($facilities as $facility) {
                                            
                                            $q->orWhere("mssql_patients.aoc_facility_id","=",$facility->id);
                                        }
                                    });
                                }
                            }
                            if($value['name']=='recruiter-aoc_facility_id') {
                                if(!empty($value['value'])) {
                                    $bucket->where("mssql_patients.aoc_facility_id",'=',$value['value']);
                                }
                            }
                        }
                        if($value['name']=='recruitingregion') {
                            if(!empty($value['value'])) {
                                $region[] = $value['value'];
                            }
                        }
                      
                       
                    }
                    if(LD::checkFeatureFlag("region-filter-for-all-dashboard")) {
                        if(!empty($region)){
                            $bucket->where(function($query) use ($region) {
                                foreach($region as $val) {
                                    $query->orWhere("mssql_facilities.region",'=',$val);
                                }
                            });
                        }
                    }
                    if(LD::checkFeatureFlag('make-exception-bucket-filter-multiselect')){
                        if(!empty($branch)){
                            $bucket->where(function($query) use ($branch) {
                                foreach($branch as $val) {
                                    $query->orWhere("mssql_patients.aoc_facility_id",'=',$val);
                                }
                            });
                        }
                        $fid = [];
                        if(!empty($states)){
                            foreach($states as $state){
                                $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
                                foreach($facilities as $facility){
                                    $fid[] = $facility->id;
                                }
                            }
                            $bucket->whereIn("mssql_patients.aoc_facility_id", $fid);
                        }
                    }
                }
            } else {

            
                $bucket->having("lastActivity","<",Carbon::now()->subDays(14));
                $bucket->havingRaw("isnull(lastActivity)=0");
                $bucket->leftJoin("mssql_facilities",function($query){
                    $query->on("mssql_patients.aoc_facility_id","=","mssql_facilities.id");
                });
                $recruitername = [];

                if(isset($request['filters'])) {
                        $region = [];
                        foreach($request['filters'] as $key => $value){
                            if(LD::checkFeatureFlag('make-exception-bucket-filter-multiselect')){
                                if($value['name']=='recruiter-state') {
                                    if(!empty($value['value'])) {
                                        $states[] = $value['value'];
                                    }
                                }
                                if($value['name']=='recruiter-aocfacility_id'){
                                    if(!empty($value['value'])) {
                                        $branch[] = $value['value'];
                                    }
                                }
                                if($value['name']=='recruiter-Recruiter'){
                                    if(!empty($value['value'])) {
                                        $recruitername[] = $value['value'];
                                    }
                                }
                            }else{
                                if($value['name']=='recruiter-state') {
                                    if(!empty($value['value'])) {
                                        $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($value['value']);
                                        
                                        $bucket->where(function($q) use ($facilities){
                                            foreach($facilities as $facility) {
                                                
                                                $q->orWhere("mssql_patients.aoc_facility_id","=",$facility->id);
                                            }
                                        });
                                    }
                                }
                                if($value['name']=='recruiter-aoc_facility_id') {
                                    if(!empty($value['value'])) {
                                        $bucket->where("mssql_patients.aoc_facility_id",'=',$value['value']);
                                    }
                                }
                                if($value['name']=='recruiter-Recruiter') {
                                    if(!empty($value['value'])) {
                                        $bucket->where("NeedToHire_Accounts.Recruiter",'=',$value['value']);
                                    }
                                }
                            }
                            if($value['name']=='recruitingregion') {
                                if(!empty($value['value'])) {
                                    $region[] = $value['value'];
                                }
                            }
                           
                        }
                        if(LD::checkFeatureFlag("region-filter-for-all-dashboard")) {
                            if(!empty($region)){
                                $bucket->where(function($query) use ($region) {
                                    foreach($region as $val) {
                                        $query->orWhere("mssql_facilities.region",'=',$val);
                                    }
                                });
                            }
                        }
                        if(LD::checkFeatureFlag('make-exception-bucket-filter-multiselect')){
                            if(!empty($branch)){
                                $bucket->where(function($query) use ($branch) {
                                    foreach($branch as $val) {
                                        $query->orWhere("mssql_patients.aoc_facility_id",'=',$val);
                                    }
                                });
                            }
                            $fid = [];
                            if(!empty($states)){
                                foreach($states as $state){
                                    $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
                                    foreach($facilities as $facility){
                                        $fid[] = $facility->id;
                                    }
                                }
                                $bucket->whereIn("mssql_patients.aoc_facility_id", $fid);
                            }
                            if(!empty($recruitername)){
                                $bucket->where(function($query) use ($recruitername) {
                                    foreach($recruitername as $val) {
                                        $query->orWhere("NeedToHire_Accounts.Recruiter",'=',$val);
                                    }
                                });
                            }
                        }
                }
            }
        }

        if($showCount) {
            return '('.$bucket->count().')';
        }

        return $bucket->get();

    }
}
