<?php
namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\H;
use App\Helpers\LD;

class IntakeCoordinatorException
{
    public $buckets = [
        "intake_not_meeting_nurses"=>["heading"=>"Not Meeting Nurses"],
        "intake_the_sale_255_characters"=>["heading"=>"The Sale < 255 Characters"],
        "intake_gender_preference_mismatch_in_kantime"=>["heading"=>"Gender Preference Mismatch in Kantime"],
        "intake_no_match_in_kantime"=>["heading"=>"No Match in Kantime"],
        "intake_no_hours"=>["heading"=>"No Hours"],
        "intake_no_payor"=>["heading"=>"No Payor"],
        "intake_no_ccc"=>["heading"=>"No CCC"],
        "intake_respite_no_respite_payor"=>["heading"=>"Respite w/No Respite Payor"],
    ];


    public function getBuckets(){
        if (LD::checkFeatureFlag('ccc-no-services-needed-widget-exception-bucket')) {
            $this->buckets['intake_no_services_needed'] = ["heading" => "No Services Needed"];
        }
        if (LD::checkFeatureFlag('add-acceptscoverage-to-nth-schedule-page')) {
            $this->buckets['intake_coverage_shift_covered_by_perm_nurse'] =
            ["heading" => "Intake Coverage Shift Covered by PERM Nurse"];
            $this->buckets['intake_coverage_shift_before_goal_soc_date'] =
            ["heading" => "Intake Coverage Shift Before Goal SOC Date"];
        }
        
       
            $this->buckets['intake_unassigned_care_team'] =
            ["heading" => "Unassigned Care Team Members"];
        return $this->buckets;
    }


    public function getBucketTitle($type) {
        return $this->buckets[$type]['heading'];
    }

    public function getDataTableHtml($type)
    {
        $constIntakeCoordinator = 'Intake Coordinator';
        $dataTable = [];
        switch($type) {
            case 'intake_staffed_coverage_shifts_not_updated_in_kantime':
                    $dataTable = [
                        'Client_Coordinator' => 'CCC',
                        'patient_name' => 'Client',
                        'PatientStatus'=>'Status',
                        'shift_date' => 'Visit Date',
                        'Schedule_Time' => 'Visit Time',
                        'Payer' => 'Payer',
                        'Clinician' => 'Clinician',
                        'escalated_pay_rate' => 'Pay Rate',
                    ];
                break;
            case 'intake_coverage_shift_covered_by_perm_nurse':
            case 'intake_coverage_shift_before_goal_soc_date':
                $dataTable = [
                    'Client_Coordinator' => $constIntakeCoordinator,
                    'patient_name' => 'Client',
                    'CF_GoalSOCDate' => 'Goal SOC Date',
                    'shift_date' => 'Visit Date',
                    'Schedule_Time' => 'Visit Time',
                    'Payer' => "Payer",
                    'Clinician' => "Coverage Clinician",
                ];
            break;
            case 'intake_unassigned_care_team':
                $dataTable = [
                    'Client_Coordinator' => $constIntakeCoordinator,
                    'patient_name' => 'Client',
                    'PatientAccount' => 'Client ID',
                    'FacilityName'=>'Branch',
                    'intake_message'=>'Message'
                ];
            break;
            default:
                $dataTable = ['patient_name'=>'Client','PatientAccount'=>'Client ID','PatientStatus'=>'Status','FacilityName'=>'Location','Client_Coordinator'=>'Client Care Coordinator','CreatedOn'=>"Date Added to NTH",'days_since_created'=>"Days on NTH"];
                break;
        }
        return view('intake.datatable',["type"=>$type,'dataTable'=>$dataTable]);
    }

    public function getIntakeException($type, $request = '',$showCount=0) {
        $request = $request->all();
        if ($type == 'intake_staffed_coverage_shifts_not_updated_in_kantime') {
            $bucket = DB::table("coverage_tracker")->select("mssql_patients.id as PID",'mssql_patientstatus.PatientStatus', DB::raw("users.name AS Client_Coordinator"), DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"), 'coverage_tracker.shift_date', 'coverage_tracker.Schedule_Time', 'mssql_patients.Payer', DB::raw("CONCAT(mssql_providers.FirstName,' ',mssql_providers.LastName) AS Clinician"), 'coverage_tracker.escalated_pay_rate', DB::raw('(SELECT m.id FROM `maps_coverageneeds` m WHERE m.Patient_ID = coverage_tracker.Patient_ID and m.Schedule_Time = coverage_tracker.Schedule_Time and m.shift_date = coverage_tracker.shift_date limit 1) as MID'));
            $bucket->leftJoin("mssql_patients", function($query) {
                $query->on("coverage_tracker.aoc_patient_id", "=", "mssql_patients.id");
            });
            $bucket->leftJoin("mssql_facilities", function($query) {
                $query->on("mssql_facilities.id", "=", "mssql_patients.aoc_facility_id");
            });
            $bucket->leftJoin("users",function($query){
                $query->on("users.id", "=", "mssql_patients.ccc_id");
            });
            $bucket->leftJoin("mssql_providers",function($query){
                $query->on("mssql_providers.id","=","coverage_tracker.aoc_provider_id");
            });
            $bucket->leftJoin("mssql_patientstatus", function($query){
                $query->on("mssql_patients.Patient_CurrentStatusID","=","mssql_patientstatus.PatientStatusID");
            });
            if(isset($request['filters'])) {
                $branch = [];
                $state = [];
                $region = [];
                $users = [];
                $payer = [];
                $payerGroup=[];
                $isMeMode = 0;


                if(isset($request['filters'])) {

                    foreach($request['filters'] as $key => $value){
                        if($value['name']=='intakeexceptionstate') {
                            $state[] = $value['value'];
                        }
                        if($value['name']=='intakeexceptionbranch') {
                            $branch[] = $value['value'];
                        }
                        if($value['name']=='intakeexceptionregion') {
                            $region[] = $value['value'];
                        }
                        if($value['name']=='intakeexceptioncare'){
                            $users[] = $value['value'];
                        }
                        if( $value['name']=='intakeexceptionPayer'){
                            $payer[] = $value['value'];
                        }
                        if( $value['name']=='intakeexceptionspecialpayergroup'  && $value['value'] != ''){
                            $payerGroup[] = $value['value'];
                        }

                        if($value['name']=='intakeexceptiondefault') {
                            $isMeMode = 1;
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

                if(!empty($users) && !$isMeMode){
                    $bucket->whereIn("mssql_patients.ic_id",$users);
                } else if($isMeMode) {
                    $bucket->where("mssql_patients.ic_id","=",Auth::user()->id);
                }

                if(!empty($payer)){
                    $bucket->where(function($q) use ($payer){
                        foreach($payer as $t) {
                            $q->orWhere("mssql_patients.Payer","=",$t);
                        }
                    });
                }

                if(!empty($payerGroup)){
                    $bucket->where(function($q) use ($payerGroup){
                        foreach($payerGroup as $t) {
                            $q->orWhere("mssql_patients.payer_group","=",$t);
                        }
                    });
                }
            }
            $bucket->whereRaw('isnull((SELECT m.id FROM `maps_coverageneeds` m WHERE m.Patient_ID = coverage_tracker.Patient_ID and m.Schedule_Time = coverage_tracker.Schedule_Time and m.shift_date = coverage_tracker.shift_date limit 1)) = 0');
            H::ExcelLog($bucket->toSql(), 'IntakeClient.log');
            if($showCount) {
                return '('.$bucket->count().')';
            }
            return $bucket->get();
        } else {

                $bucket = DB::table("NeedToHire_Accounts");
                if (LD::checkFeatureFlag('add-color-highlights-ccc-exception-bucket')) {
                    $bucket->select('NeedToHire_Accounts.*','mssql_patients.PatientAccount','mssql_patients.exception_addressed_date','mssql_facilities.FacilityName','mssql_patientstatus.PatientStatus',DB::raw("users.name AS Client_Coordinator"),
                    DB::raw("DATEDIFF(now(),NeedToHire_Accounts.CreatedOn) AS days_since_created"),
                    DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"),
                    DB::raw("(select sum(ShiftHours) from NeedToHire_Schedules where NeedToHire_Accounts.id = NeedToHire_Schedules.aoc_nthaccounts_id and NeedToHire_Schedules.IsActive=1) as totalhours"));
                } else {
                    $bucket->select('NeedToHire_Accounts.*','mssql_patients.PatientAccount','mssql_facilities.FacilityName','mssql_patientstatus.PatientStatus',DB::raw("users.name AS Client_Coordinator"),
                    DB::raw("DATEDIFF(now(),NeedToHire_Accounts.CreatedOn) AS days_since_created"),
                    DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"),
                    DB::raw("(select sum(ShiftHours) from NeedToHire_Schedules where NeedToHire_Accounts.id = NeedToHire_Schedules.aoc_nthaccounts_id and NeedToHire_Schedules.IsActive=1) as totalhours"));
                }

                $bucket->leftJoin("mssql_patients",function($query){
                    $query->on("mssql_patients.id","=","NeedToHire_Accounts.aoc_patient_id");
                });

                $bucket->leftJoin("mssql_facilities",function($query){
                    $query->on("mssql_patients.aoc_facility_id","=","mssql_facilities.id");
                });

                $bucket->leftJoin("mssql_patientstatus", function($query){
                    $query->on("mssql_patients.Patient_CurrentStatusID","=","mssql_patientstatus.PatientStatusID");
                });

                $branch = [];
                $state = [];
                $region = [];
                $users = [];
                $payer = [];
                $payerGroup=[];
                $isMeMode = 0;


                if(isset($request['filters'])) {

                    foreach($request['filters'] as $key => $value){
                        if($value['name']=='intakeexceptionstate') {
                            $state[] = $value['value'];
                        }
                        if($value['name']=='intakeexceptionbranch') {
                            $branch[] = $value['value'];
                        }
                        if($value['name']=='intakeexceptionregion') {
                            $region[] = $value['value'];
                        }
                        if($value['name']=='intakeexceptioncare'){
                            $users[] = $value['value'];
                        }
                        if( $value['name']=='intakeexceptionPayer'){
                            $payer[] = $value['value'];
                        }
                        if( $value['name']=='intakeexceptionspecialpayergroup'  && $value['value'] != ''){
                            $payerGroup[] = $value['value'];
                        }

                        if($value['name']=='intakeexceptiondefault') {
                            $isMeMode = 1;
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

                if(!empty($users) && !$isMeMode){
                    $bucket->whereIn("mssql_patients.ic_id",$users);
                } else if($isMeMode) {
                    $bucket->where("mssql_patients.ic_id","=",Auth::user()->id);
                }

                if(!empty($payer)){
                    $bucket->where(function($q) use ($payer){
                        foreach($payer as $t) {
                            $q->orWhere("mssql_patients.Payer","=",$t);
                        }
                    });
                }

                if(!empty($payerGroup)){
                    $bucket->where(function($q) use ($payerGroup){
                        foreach($payerGroup as $t) {
                            $q->orWhere("mssql_patients.payer_group","=",$t);
                        }
                    });
                }

                $bucket->leftJoin("users",function($query){
                    $query->on("users.id","=","mssql_patients.ic_id");
                });

                $bucket->where("NeedToHire_Accounts.isActive",'1');
                $bucket->where('mssql_patients.Patient_CurrentStatusID',4);
				$bucket->where('mssql_patients.IsLatestAdmit',1);

                $bucket->addSelect("mssql_patients.Payer","mssql_patients.payer_group","mssql_patients.CF_ServicesNeeded","mssql_patients.last_monthly_followup");

                if($type=='intake_not_meeting_nurses') {
                    $bucket->where("mssql_patients.CF_MeetingNurses",'like', 'No%');
                } else if($type=='intake_gender_preference_mismatch_in_kantime') {
                    $bucket->where("mssql_patients.PreferenceList",'like', '%Male Nurse Accepted%')->where("mssql_patients.PreferenceList",'like', '%Does NOT Accept Female Nurses%');
                } else if($type=='intake_the_sale_255_characters') {
                    $bucket->whereRaw("LENGTH(CF_TheSale) < 255");
                } else if($type=='intake_no_match_in_kantime') {
                    $bucket->where(function($q){
                        $q->whereNull("PatientID")->orWhere("PatientID",'=','');
                    });
                    $bucket->where(function($q){
                        $q->whereNull("IntakeID")->orWhere("IntakeID",'=','');
                    });
                } else if($type=='intake_no_hours') {
                    $bucket->havingRaw("totalhours < 0.01 or isnull(totalhours)=1");
                } else if($type=='intake_no_payor') {
                    $bucket->where(function ($q) {
                        $q->where(function ($q) {
                            $q->whereNull('Payer')->orWhere('Payer', '');
                        });

                        if (LD::checkFeatureFlag("add-condition-intake-exception-bucket-for-no-payer-and-respite-bucket")) {
                            $q->whereRaw("NOT (
                                NeedToHire_Accounts.ServicesNeeded IN (?, ?, ?)
                                AND mssql_patients.RespitePayer IS NOT NULL
                                AND mssql_patients.RespitePayer != ''
                            )", ['Respite', 'PDN/Respite', 'CNA/Respite']);
                        }
                    });
                } else if($type=='intake_no_intake_coordinator') {
                    $bucket->where(function($q){
                        $q->whereNull("ic_id")->orWhere("ic_id",'=','');
                    });
                } else if($type=='intake_no_ccc') {
                    $bucket->where(function($q){
                        $q->whereNull("ccc_id")->orWhere("ccc_id",'=','');
                    });

                } else if($type=='intake_respite_no_respite_payor') {
                    if (LD::checkFeatureFlag("add-condition-intake-exception-bucket-for-no-payer-and-respite-bucket")) {
                        $bucket->whereNull("RespitePayer")
                        ->whereIn("NeedToHire_Accounts.ServicesNeeded", ['Respite', 'PDN/Respite', 'CNA/Respite']);
                    }else{
                        $bucket->whereNull("RespitePayer")->where("NeedToHire_Accounts.ServicesNeeded",'=',"Respite");
                    }
                }else if($type=='intake_no_services_needed') {
                    $bucket->where("mssql_patients.CF_ServicesNeeded",'=','');
                } else if($type=='intake_unassigned_care_team') {
                    $bucket->addSelect("mssql_patients.ccc_id","mssql_patients.ccm_id");
                    $bucket->where(function($q){
                        $q->where(function($qq){
                            $qq->whereNull("ccc_id")->orWhere("ccc_id",'=','');
                        })->orWhere(function($qq){
                            $qq->whereNull("ccm_id")->orWhere("ccm_id",'=','');
                        });
                        
                    });
                    

                    $bucket->where(function($q) {
                        $q->whereDate("marked_careteam","<", date("Y-m-d"))->orWhereNull("marked_careteam");
                    });
                }
                
                if($showCount) {
                    return '('.$bucket->count().')';
                }
                H::ExcelLog('IntakeException: ' . $bucket->toSql(),'IntakeException.log');
                return $bucket->get();

        }

    }

   
}
