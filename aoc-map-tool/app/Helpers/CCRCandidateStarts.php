<?php
namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\MssqlRecruitertracker;
use App\RecruitingSchedule;
use App\Helpers\LD;

class CCRCandidateStarts
{
   

    public function getDataTableHtml($type)
    {
        $dataTable = [];

        $caseManager = 'Case Manager';
        $candidateStartDate = 'Candidate Start Date';
        $phoneNumber = 'Phone Number';
        $mobilePhoneNumber = 'Mobile Phone Number';

        switch($type) {
            case 'first_shift_follow_up':
                if(LD::checkFeatureFlag('add-branch-candidate-start-followups')){
                    $dataTable = [
                        'select'=>'select',
                        'case_manager'=>$caseManager,
                        'FacilityName'=>'Branch',
                        'Clinician_Candidate'=>"Candidate",
                        'appointment_date'=>$candidateStartDate,
                        'patient_name'=>'Client',
                        'candidates_homePhone'=>$phoneNumber,
                        'candidates_mobile'=>$mobilePhoneNumber,
                        'first_followup_outcome'=>'Outcome',
                    ];
                } else {
                    $dataTable = [
                        'select'=>'select',
                        'case_manager'=>$caseManager,
                        'Clinician_Candidate'=>"Candidate",
                        'appointment_date'=>$candidateStartDate,
                        'patient_name'=>'Client',
                        'candidates_homePhone'=>$phoneNumber,
                        'candidates_mobile'=>$mobilePhoneNumber,
                        'first_followup_outcome'=>'Outcome',
                    ];
                }
                break;
            case 'seven_day_follow_up':
            case 'thirty_day_follow_up':
                if(LD::checkFeatureFlag('add-branch-candidate-start-followups')){
                    $dataTable = [
                        'select'=>'select',
                        'case_manager'=>$caseManager,
                        'FacilityName'=>'Branch',
                        'Clinician_Candidate'=>"Candidate",
                        'appointment_date'=>$candidateStartDate,
                        'patient_name'=>'Client',
                        'candidates_homePhone'=>$phoneNumber,
                        'candidates_mobile'=>$mobilePhoneNumber
                    ];
                } else {
                    $dataTable = [
                        'select'=>'select',
                        'case_manager'=>$caseManager,
                        'Clinician_Candidate'=>"Candidate",
                        'appointment_date'=>$candidateStartDate,
                        'patient_name'=>'Client',
                        'candidates_homePhone'=>$phoneNumber,
                        'candidates_mobile'=>$mobilePhoneNumber
                    ];
                }
                break;
            default:
                break;
        }
        
        return view('ccr.candidatestarts.datatable',["type"=>$type,'dataTable'=>$dataTable]);
    }

    public function getCandidateStartData($type, $request = '',$showCount=0) {
        
        $request = $request->all();
            $ignoreOutcomeList = ['No Longer on Case','Candidate already established with client','Completed','Unable to contact'];
            $today = date('Y-m-d');
            if(LD::checkFeatureFlag("candidate_start_reassign_calls")) {

                $bucket = RecruitingSchedule::select("recruiting_schedule.*",DB::raw("ifnull(canu.name, users.name) as case_manager"),DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"),
                DB::raw("CONCAT(mssql_providers.FirstName,' ',mssql_providers.LastName) AS Clinician"),DB::raw("CONCAT(candidates.firstName,' ',candidates.lastName) AS Clinician_Candidate"),DB::raw("candidates.homePhone AS candidates_homePhone"),DB::raw("candidates.mobile AS candidates_mobile"),DB::raw("mssql_providers.Phone AS provider_homephone"),DB::raw("mssql_providers.Mobile AS provider_mobile"));
            } else {
                $bucket = RecruitingSchedule::select("recruiting_schedule.*", DB::raw("users.name AS case_manager"),DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"),
                DB::raw("CONCAT(mssql_providers.FirstName,' ',mssql_providers.LastName) AS Clinician"),DB::raw("CONCAT(candidates.firstName,' ',candidates.lastName) AS Clinician_Candidate"),DB::raw("candidates.homePhone AS candidates_homePhone"),DB::raw("candidates.mobile AS candidates_mobile"),DB::raw("mssql_providers.Phone AS provider_homephone"),DB::raw("mssql_providers.Mobile AS provider_mobile"));

            }
            if(LD::checkFeatureFlag('add-branch-candidate-start-followups')){
                $bucket->addSelect('mssql_facilities.FacilityName');
            }
            $bucket->leftJoin("mssql_patients",function($query){
                $query->on("mssql_patients.id","=","recruiting_schedule.aoc_patient_id");
            });
    
            $bucket->leftJoin("mssql_recruitertracker",function($query){
                $query->on("mssql_recruitertracker.id","=","recruiting_schedule.tracker_id");
            });

            $bucket->leftJoin("mssql_facilities",function($query){
                $query->on("mssql_facilities.id","=","mssql_patients.aoc_facility_id");
            });

            $bucket->leftJoin("mssql_providers",function($query){
                $query->on("mssql_providers.id","=","recruiting_schedule.aoc_provider_id");
            });

            $bucket->leftJoin("candidates",function($query){
                $query->on("candidates.id","=","recruiting_schedule.aoc_candidate_id");
            });
            $bucket->leftJoin("users",function($query){
                $query->on("users.id","=","mssql_patients.ccm_id");
            });
            if(LD::checkFeatureFlag("candidate_start_reassign_calls")) {
                $bucket->leftJoin("users as canu",function($query){
                    $query->on("canu.id","=","mssql_recruitertracker.assigned_candidatestart");
                });
            }
            
            $bucket->where("recruiting_schedule.appointment_date","<",date("Y-m-d"));
            $bucket->where("recruiting_schedule.type_id","=",6);

            if(LD::checkFeatureFlag('ccm-ccr-exclude-null-patient')) {
                $bucket->where(function($q) {
                    $q->where("mssql_recruitertracker.aoc_patient_id", '<>', '0');
                    $q->WhereNotNull("mssql_recruitertracker.aoc_patient_id");
                });
            }
            if(LD::checkFeatureFlag('client-match-added-condition-for-nurse-not-working-aoc')) {
                $bucket->where(function($q){
                    $q->where("mssql_recruitertracker.inactive_reason","!=","Nurse decided not to work for AOC")
                    ->orWhereNull("mssql_recruitertracker.inactive_reason");
                });
            }
            
            $bucket->where("mssql_patients.Search_Name", "not like" , "%PRN%");
            
            $branch = [];
            $state = [];
            $users = [];
            $payer = [];
            $payer_group = [];
            $startDate = '';
            $endDate = '';
            $isMeMode = 0;
            $region = [];

            if(isset($request['filters'])) {
                
                foreach($request['filters'] as $key => $value){
                    
                    if($value['name']=='ccmcandidatestate') {
                        $state[] = $value['value'];
                    }
                    if($value['name']=='ccmcandidatebranch') {
                        $branch[] = $value['value'];
                    }
                    if($value['name']=='ccmcandidateregion') {
                        $region[] = $value['value'];
                    }
                    if($value['name']=='candidateccm'){
                        $users[] = $value['value'];
                    }
                    if($value['name']=='ccmcandidatedefault'){
                        $isMeMode =1;
                    }

                    if($value['name']=='fromCandidateStartDate') {
                        if(!empty($value['value'])) {
                            $startDate = $value['value'];
                        }
                    }
                    if($value['name']=='toCandidateStartDate') {
                        if(!empty($value['value'])) {
                            $endDate = $value['value'];
                        }
                    }
                    if(LD::checkFeatureFlag('payer-filters-on-ccm-candidate-start')){
                        if($value['name']=='Payer') {
                            if(!empty($value['value'])) {
                                $payer[] = $value['value'];
                            }
                        }
                        if($value['name']=='payer_group') {
                            if(!empty($value['value'])) {
                                $payer_group[] = $value['value'];
                            }
                        }
                    }

                }
            }

            if(count($state)>0) {
                $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
                $bucket->where(function($q) use ($facilities){
                    foreach($facilities as $facility) {
                        $q->orWhere("mssql_patients.aoc_facility_id","=",$facility->id);
                    }
                });
            }
            if(count($branch)>0) {
                $bucket->where(function($q) use ($branch){
                    foreach($branch as $b) {
                        $q->orWhere("mssql_patients.aoc_facility_id","=",$b);
                    }
                });
            }

            if(count($region)>0) {
                $bucket->where(function($q) use ($region){
                    foreach($region as $b) {
                        $q->orWhere("mssql_facilities.region","=",$b);
                    }
                });
            }

            if(count($payer)){
                    $bucket->where(function($q) use($payer){
                        foreach($payer as $p) {
                            $q->orWhere("mssql_patients.payer", '=', $p);
                        }
                    });
            }
            if(count($payer_group)){
                $bucket->where(function($q) use($payer_group){
                    foreach($payer_group as $p) {
                        $q->orWhere("mssql_patients.payer_group", '=', $p);
                    }
                });
            }

            if(!empty($startDate)) {
                $convertedStartDate = date('Y-m-d',strtotime($startDate));
                $bucket->whereDate("recruiting_schedule.appointment_date", ">=",$convertedStartDate);
            }

            if(!empty($endDate)) {
                $convertedEndDate = date('Y-m-d',strtotime($endDate));
                $bucket->whereDate("recruiting_schedule.appointment_date", "<=",$convertedEndDate);
            }
            if(LD::checkFeatureFlag("candidate_start_reassign_calls")) {
                if(!empty($users) && !$isMeMode){
                    $bucket->whereIn(DB::raw("ifnull(mssql_recruitertracker.assigned_candidatestart, mssql_patients.ccm_id)"),$users);
                } else if($isMeMode) {
                    $bucket->where(DB::raw("ifnull(mssql_recruitertracker.assigned_candidatestart, mssql_patients.ccm_id)"),"=",Auth::user()->id);
                }
            } else {
                if(!empty($users) && !$isMeMode){
                    $bucket->whereIn("mssql_patients.ccm_id",$users);
                } else if($isMeMode) {
                    $bucket->where("mssql_patients.ccm_id","=",Auth::user()->id);
                }
            }
           $bucket->where("mssql_recruitertracker.Candidate_Current_Status","=","First Shift Confirmed");

           if(LD::checkFeatureFlag('first-shift-exclude-rs-outcome')){
                $rsOutcome = ['Client/caregiver declined services'];
                $bucket->whereNotIn("recruiting_schedule.outcome",$rsOutcome);
           }

            if($type=='first_shift_follow_up') {
                $appDate = date("Y-m-d");
               $bucket->where(function($q) use($ignoreOutcomeList) {
                    $q->whereNull("first_followup_outcome")->orWhere(function($qq) use($ignoreOutcomeList) {
                        $qq->whereNotIn("first_followup_outcome",$ignoreOutcomeList);
                    });
                });
                $bucket->where(function($q){
                    $q->whereDate("recruiting_schedule.followed_up_at", "<",date("Y-m-d"))->orWhereNull("recruiting_schedule.followed_up_at");
                })->whereDate("recruiting_schedule.appointment_date",">",'2023-03-24');
            } else if($type=='seven_day_follow_up') {
                $startAppDate = date("Y-m-d",strtotime("-6 days"));
                $endAppDate = date("Y-m-d",strtotime("-30 days"));
                
                $bucket->where(function($q) use($ignoreOutcomeList) {
                    $q->whereNull("sevenday_followup_outcome")->orWhere(function($qq) use($ignoreOutcomeList) {
                        $qq->whereNotIn("sevenday_followup_outcome",$ignoreOutcomeList);
                    });
                });
                if(LD::checkFeatureFlag('ccm-no-longer-on-case')) {
                    $bucket->whereNotIn("first_followup_outcome",['No Longer on Case']);
                }
                

                $bucket->where(function($q){
                    $q->whereDate("recruiting_schedule.followed_up_at", "<",date("Y-m-d"))->orWhereNull("recruiting_schedule.followed_up_at");
                });
                
                $bucket->whereDate("recruiting_schedule.appointment_date", "<",$startAppDate)
                        ->whereDate("recruiting_schedule.appointment_date", ">",'2023-03-17');
            } else if($type=='thirty_day_follow_up') {
                $endAppDate = date("Y-m-d",strtotime("-29 days"));
                
                $bucket->where(function($q) use($ignoreOutcomeList) {
                    $q->whereNull("thirtyday_followup_outcome")->orWhere(function($qq) use($ignoreOutcomeList) {
                        $qq->whereNotIn("thirtyday_followup_outcome",$ignoreOutcomeList);
                    });
                });
                if(LD::checkFeatureFlag('ccm-no-longer-on-case')) {
                    $bucket->whereNotIn("sevenday_followup_outcome",['No Longer on Case']);
                    $bucket->whereNotIn("first_followup_outcome",['No Longer on Case']);
                }
                
                $bucket->where(function($q){
                    $q->whereDate("recruiting_schedule.followed_up_at", "<",date("Y-m-d"))->orWhereNull("recruiting_schedule.followed_up_at");
                });
                $bucket->whereDate("recruiting_schedule.appointment_date", "<",$endAppDate)
                        ->whereDate("recruiting_schedule.appointment_date", ">",'2023-02-23');
               
            }
            H::ExcelLog($bucket->toSql(),'dblynn.log');
            if($showCount) {
                return '('.$bucket->count().')';
            }

            return $bucket->get();
    }
}
