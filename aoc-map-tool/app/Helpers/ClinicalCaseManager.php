<?php
namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\MssqlPatients;
use App\RecruitingSchedule as RecruitingSchedule;
use App\Helpers\H;
use App\Helpers\LD;

class ClinicalCaseManager
{
    public $buckets = [
        "text_call_client_schedule_regulars"=>["heading"=>"Text and Call Client Schedule Regulars"],
        "text_call_record_clinicians"=>["heading"=>"Text and Call Record Clinicians"],
        "text_call_prn_clinicians_from_local_prn_schedule"=>["heading"=>"Text and Call PRN Clinicians from Local PRN Schedule"],
        "text_blast_180_mile_radius_from_text_blast_map"=>["heading"=>"Text Blast for 180-Mile Radius from Text Blast Map"],
        "call_nurses_from_text_blast_map"=>["heading"=>"Call Nurses from the Text Blast Map"],
        "check_cover_nurses_availability"=>["heading"=>"Check Cover Nurses Availability"]
    ];


    public function getBuckets(){
        return $this->buckets;
    }

    public function getBucketArray() {
        return [
            "text_call_client_schedule_regulars" => "Text and Call Client Schedule Regulars",
            "text_call_record_clinicians" => "Text and Call Record Clinicians",
            "text_call_prn_clinicians_from_local_prn_schedule" => "Text and Call PRN Clinicians from Local PRN Schedule",
            "text_blast_180_mile_radius_from_text_blast_map" => "Text Blast for 180-Mile Radius from Text Blast Map",
            "call_nurses_from_text_blast_map" => "Call Nurses from the Text Blast Map",
            "check_cover_nurses_availability" => "Check Cover Nurses Availability"
        ];
    }
    public function getBucketTitle($type) {
        return $this->buckets[$type]['heading'];
    }

    public function getDataTableHtml($type, $form = '', $globalFilterFields = []): Renderable
    {
        $dataTable = [];
        switch($type) {
            case 'clinical_competency_reviews':

                    $dataTable = ['select'=>'select','ccm_id'=>'CCM',
                    'Candidate'=>'Candidate','patient_name'=>'Client',
                    'client_specialized'=>'Specialized','requested_by' => 'Requested By',
                    'scheduled_date'=>'Request Date / Time','shift_type'=>'Shift Type',
                    'candidate_mobile'=>'Mobile','proposed_next_step'=>'Proposed Next Step',
                    'proposed_next_step_date'=>'Due Date','outcome'=>'Status'];

                if(LD::checkFeatureFlag('add-branch-to-ccrs-on-ccm-dashboard')){
                    $new = ['FacilityName'=>'Branch'];
                    $dataTable =  array_merge( array_slice( $dataTable, 0, 2 ), $new, array_slice( $dataTable, 2 ) );
                }
                break;
            case 'clinical_exception_bucket':
                $dataTable = ['patient_name'=>'Client','FacilityName'=>'Branch','PatientStatus'=>'Status','created_at'=>'Created'];
                break;
            case 'ccr_today_ipea':
                $dataTable = ['Candidate'=>'Nurse','appointment_date'=>'IPEA Date','appointment_time'=>'Time','branch'=>'Branch','oc'=>'OC','Candidate_Current_Status'=>'Status','ipea_status'=>'Last Step Completed','NS'=>'Next Step','Candidate_Notes'=>'Recruiter Notes','branch_notes'=>'Branch Notes'];
                break;
            case 'ccm_scheduled_visits':
                if(LD::checkFeatureFlag('scheduled-visits-filter')) {
                    $dataTable = ['ccm_id'=>'CCM','patient_name'=>'Client','FacilityName'=>'Branch','ServiceName' => 'Service Name','ScheduleDate' => 'Date'];
                } else {
                    $dataTable = ['ccm_id'=>'CCM','patient_name'=>'Client','FacilityName'=>'Branch','ScheduleDate' => 'Date'];
                }
                break;
            default:
                $dataTable = ['select'=>'select','patient_name'=>'Client Name','IsPriority'=>'Priority','shift_date'=>'Date','Schedule_Time'=>'Schedule','Hours'=>"Hours",'Service'=>"Service",'Discipline'=>"Discipline",'Payer'=>"Payer",'payer_group'=>"Payer Group"];
                break;
        }

        return view('ccr.clinical_case_datatable',[
            'type' => $type,
            'dataTable' => $dataTable,
            'form' => $form,
            "globalFilterFields" => $globalFilterFields,
        ]);
    }

    public function getClinicalCase($type, $request = '',$showCount=0) {
        $ccm_query = DB::raw("users.name AS ccm_id");
        if(LD::checkFeatureFlag('change-assign-ccr-process')){
            $ccm_query = DB::raw("IFNULL(users.name, 'Unassigned') AS ccm_id");
        }
        $bucket = DB::table('recruiting_schedule');
                $bucket->select(
                    'recruiting_schedule.outcome',
                    'recruiting_schedule.created_at as scheduled_date',
                    "recruiting_schedule.tracker_id",
                    DB::raw(
                        'CASE WHEN
                            (ifnull(recruiting_schedule.aoc_provider_id,0) = 0 AND
                            ifnull(recruiting_schedule.aoc_candidate_id,0) = 0 AND
                            ifnull(recruiting_schedule.coverage_id,0) != 0)
                        THEN
                            coverage_tracker.new_clinician_name
                        ELSE
                            CASE WHEN
                                (ifnull(recruiting_schedule.aoc_provider_id,0) != 0 AND
                                ifnull(recruiting_schedule.aoc_candidate_id,0) = 0)
                            THEN
                                mssql_providers.fullName
                            ELSE
                                mssql_recruitertracker.Candidate
                            END
                        END as Candidate'
                    ),
                    'mssql_recruitertracker.ccr_acknowledged',
                    'recruiting_schedule.id',
                    'mssql_patients.PatientAccount',
                    $ccm_query,
                    DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"),
                    DB::raw("(SELECT name from users where id = recruiting_schedule.created_by) AS requested_by"),
                    "mssql_patients.CF_Specialized as client_specialized",
                    DB::raw("CASE WHEN ifnull(recruiting_schedule.aoc_candidate_id,0) > 0 THEN candidates.homePhone
                        WHEN ifnull(recruiting_schedule.aoc_provider_id,0) > 0 THEN mssql_providers.Phone
                        END as display_phone"
                    ),
                    DB::raw("CASE WHEN ifnull(recruiting_schedule.aoc_candidate_id,0) > 0 THEN candidates.mobile
                        WHEN ifnull(recruiting_schedule.aoc_provider_id,0) > 0 THEN mssql_providers.Mobile
                        END as candidate_mobile"
                    ),
                    'recruiting_schedule.appointment_date',
                    'mssql_recruitertracker.created_at',
                    'mssql_recruitertracker.proposed_next_step',
                    'mssql_recruitertracker.Candidate_Start_Date',
                    'mssql_facilities.FacilityName as FacilityName',
                    'mssql_recruitertracker.Candidate_Current_Status',
                    DB::raw('CASE WHEN
                        ifnull(recruiting_schedule.coverage_id,0) != 0
                        THEN \'Coverage\'
                        ELSE \'Training\'
                        END as shift_type'
                    ),
					(
						(LD::checkFeatureFlag('fixed-ccr-coverage-shift-issue') && $type == 'clinical_competency_reviews') ?
						DB::raw('CASE WHEN
							ifnull(recruiting_schedule.coverage_id,0) != 0
							THEN DATE_FORMAT(CONCAT(FirstShiftCoverage.shift_date,\' \',
							SUBSTRING_INDEX(FirstShiftCoverage.Schedule_Time,\' - \',1)), \'%m/%d/%Y %h:%i %p\')
							ELSE DATE_FORMAT(STR_TO_DATE(CONCAT(mssql_recruitertracker.proposed_ns_date,\' \',
							mssql_recruitertracker.proposed_ns_time),\'%Y-%m-%d %H:%i:%s\'), \'%m/%d/%Y %h:%i %p\')
							END as proposed_next_step_date'
						) :
						DB::raw('CASE WHEN
							ifnull(recruiting_schedule.coverage_id,0) != 0
							THEN DATE_FORMAT(CONCAT(coverage_tracker.shift_date,\' \',
							SUBSTRING_INDEX(coverage_tracker.Schedule_Time,\' - \',1)), \'%m/%d/%Y %h:%i %p\')
							ELSE DATE_FORMAT(STR_TO_DATE(CONCAT(mssql_recruitertracker.proposed_ns_date,\' \',
							mssql_recruitertracker.proposed_ns_time),\'%Y-%m-%d %H:%i:%s\'), \'%m/%d/%Y %h:%i %p\')
							END as proposed_next_step_date'
						)
					),
										(
						(LD::checkFeatureFlag('fixed-ccr-coverage-shift-issue') && $type == 'clinical_competency_reviews') ?
						DB::raw("CASE
								WHEN IFNULL(recruiting_schedule.coverage_id, 0) != 0
								THEN
									STR_TO_DATE(
										CONCAT(FirstShiftCoverage.shift_date, ' ', SUBSTRING_INDEX(FirstShiftCoverage.Schedule_Time, ' - ', 1)),
										'%Y-%m-%d %h:%i %p'
									)
								ELSE
									STR_TO_DATE(
										CONCAT(mssql_recruitertracker.proposed_ns_date, ' ', mssql_recruitertracker.proposed_ns_time),
										'%Y-%m-%d %H:%i:%s'
									)
							END AS proposed_next_step_date_raw") :
						DB::raw("CASE
								WHEN IFNULL(recruiting_schedule.coverage_id, 0) != 0
								THEN
									STR_TO_DATE(
										CONCAT(coverage_tracker.shift_date, ' ', SUBSTRING_INDEX(coverage_tracker.Schedule_Time, ' - ', 1)),
										'%Y-%m-%d %h:%i %p'
									)
								ELSE
									STR_TO_DATE(
										CONCAT(mssql_recruitertracker.proposed_ns_date, ' ', mssql_recruitertracker.proposed_ns_time),
										'%Y-%m-%d %H:%i:%s'
									)
							END AS proposed_next_step_date_raw")
					),
                    'recruiting_schedule.aoc_patient_id',
                    (
                        LD::checkFeatureFlag('fixed-ccr-nextstep-date-issue') && $type == 'clinical_competency_reviews'
                    ) ? 'FirstShiftCoverage.coverage_id_new as coverage_id' : 'recruiting_schedule.coverage_id'
                );
            $bucket->leftJoin("mssql_patients",function($query){
                $query->on("mssql_patients.id","=","recruiting_schedule.aoc_patient_id");
            });
            $bucket->leftJoin("mssql_recruitertracker",function($query){
                $query->on("mssql_recruitertracker.id","=","recruiting_schedule.tracker_id");
            });
            $bucket->leftJoin("coverage_tracker",function($query){
                $query->on("coverage_tracker.id","=","recruiting_schedule.coverage_id");
            });
            $bucket->leftJoin("mssql_providers",function($query){
                $query->on("mssql_providers.id","=","recruiting_schedule.aoc_provider_id");
            });
            $bucket->leftJoin("candidates",function($query){
                $query->on("candidates.id","=","recruiting_schedule.aoc_candidate_id");
            });
            if(LD::checkFeatureFlag('assign_ccm_to_coverage_tracker')){
                $bucket->leftJoin("users", function($query) {
                    $query->on("users.id", "=", DB::raw("
                        CASE
                            WHEN mssql_recruitertracker.assigned_ccr <> '' THEN mssql_recruitertracker.assigned_ccr
                            WHEN recruiting_schedule.ccm_id IS NOT NULL AND recruiting_schedule.ccm_id <> 0 THEN recruiting_schedule.ccm_id
                            ELSE mssql_patients.ccm_id
                        END
                    "));
                });
            } else {
                $bucket->leftJoin("users",function($query){
                    $query->on("users.id","=",DB::raw("IF(mssql_recruitertracker.assigned_ccr <> '' , mssql_recruitertracker.assigned_ccr, mssql_patients.ccm_id)"));
                });
            }
            $bucket->leftJoin("mssql_facilities",function($query){
                $query->on("mssql_facilities.id","=","mssql_patients.aoc_facility_id");
            });

            if (LD::checkFeatureFlag('fixed-ccr-coverage-shift-issue')) {
                if (LD::checkFeatureFlag('fixed-ccr-nextstep-date-issue') && $type == 'clinical_competency_reviews') {
                    $bucket->leftJoin(DB::raw('
                        (SELECT
                            covtrac1.id as coverage_id_new,
                            covtrac1.aoc_patient_id,
                            covtrac1.aoc_provider_id,
                            covtrac1.shift_date,
                            covtrac1.Schedule_Time
                        FROM coverage_tracker AS covtrac1
                        WHERE covtrac1.id = (
                            SELECT MAX(id)
                            FROM coverage_tracker AS covtrac
                            WHERE covtrac.aoc_patient_id = covtrac1.aoc_patient_id
                            AND covtrac.aoc_provider_id = covtrac1.aoc_provider_id
                        )) AS FirstShiftCoverage
                    '), function ($join) {
                        $join->on('FirstShiftCoverage.aoc_patient_id', '=', 'coverage_tracker.aoc_patient_id')
                            ->on('FirstShiftCoverage.aoc_provider_id', '=', 'coverage_tracker.aoc_provider_id');
                    });
                } else {
                    $bucket->leftJoin(DB::raw('
                        (SELECT
                            covtrac1.aoc_patient_id,
                            covtrac1.aoc_provider_id,
                            covtrac1.shift_date,
                            covtrac1.Schedule_Time
                        FROM coverage_tracker AS covtrac1
                        WHERE covtrac1.id = (
                            SELECT MAX(id)
                            FROM coverage_tracker AS covtrac
                            WHERE covtrac.aoc_patient_id = covtrac1.aoc_patient_id
                            AND covtrac.aoc_provider_id = covtrac1.aoc_provider_id
                        )) AS FirstShiftCoverage
                    '), function ($join) {
                        $join->on('FirstShiftCoverage.aoc_patient_id', '=', 'coverage_tracker.aoc_patient_id')
                            ->on('FirstShiftCoverage.aoc_provider_id', '=', 'coverage_tracker.aoc_provider_id');
                    });
                }
            }

            if(LD::checkFeatureFlag("ccm-ccr-exclude-null-patient")) {
                $bucket->whereRaw('CASE WHEN
                    coverage_id is null
                    THEN `mssql_recruitertracker`.`aoc_patient_id`
                    ELSE coverage_tracker.aoc_patient_id
                    END is not null AND
                    CASE WHEN coverage_id is null
                    THEN mssql_recruitertracker.aoc_patient_id
                    ELSE coverage_tracker.aoc_patient_id end != 0');
            }
        if(LD::checkFeatureFlag('client-match-added-condition-for-nurse-not-working-aoc')) {
            $bucket->where(function($q){
                $q->where("mssql_recruitertracker.inactive_reason","!=","Nurse decided not to work for AOC")
                ->orWhereNull("mssql_recruitertracker.inactive_reason");
            });
        }
        $branch = [];
        $state = [];
        $users = [];
        $region = [];
        $payer = [];
        $payer_group = [];
        $created_by = [];
        $isMeMode = 0;
        $shiftType = [];
        if(isset($request['filters'])) {
            foreach($request['filters'] as $key => $value){
                if($value['name']=='ccrstate') {
                    $state[] = $value['value'];
                }
                if($value['name']=='ccrbranch') {
                    $branch[] = $value['value'];
                }
                if($value['name']=='ccrclinicalcaremanager'){
                    $users[] = $value['value'];
                }
                if($value['name']=='ccrmemode') {
                    $isMeMode = 1;
                }
                if($value['name'] == 'region'){
                    $region[] = $value['value'];
                }
                if($value['name'] == 'Payer'){
                    $payer[] = $value['value'];
                }
                if($value['name'] == 'payer_group'){
                    $payer_group[] = $value['value'];
                }
                if($value['name'] == 'created_by'){
                    $created_by[] = $value['value'];
                }
                if($value['name'] == 'shift_type'){
                    $shiftType[] = $value['value'];
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
        if(count($payer)>0) {
            $bucket->where(function($q) use ($payer){
                foreach($payer as $b) {
                    $q->orWhere("mssql_patients.Payer","=",$b);
                }
            });
        }
        if(count($payer_group)>0) {
            $bucket->where(function($q) use ($payer_group){
                foreach($payer_group as $b) {
                    $q->orWhere("mssql_patients.payer_group","=",$b);
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
        if(count($created_by)>0) {
            $bucket->where(function($q) use ($created_by){
                foreach($created_by as $b) {
                    $q->orWhere("recruiting_schedule.created_by","=",$b);
                }
            });
        }
        if(!empty($users)){
            $bucket->where(function($q) use ($users){
                $q->orWhereIn("mssql_patients.ccm_id",$users);
                $q->orWhereIn("mssql_recruitertracker.assigned_ccr",$users);
            });
        }
        if($isMeMode){
            $bucket->where(function($q) {
                $q->where("mssql_patients.ccm_id",'=', Auth::user()->id);
                $q->orWhere("mssql_recruitertracker.assigned_ccr",'=', Auth::user()->id);
            });
        }

        $bucket->where("recruiting_schedule.type_id",'=', 2);
        $bucket->where("recruiting_schedule.outcome",'<>','Unable to contact');
        $bucket->where("recruiting_schedule.outcome",'<>','Completed');
        $bucket->whereRaw("ifnull(mssql_recruitertracker.is_deleted,0) = 0");
        $bucket->whereRaw("ifnull(recruiting_schedule.is_deleted, 0) = 0");
        if(LD::checkFeatureFlag('exclude-cancelled-ccr')) {
            $bucket->where("recruiting_schedule.outcome",'<>','Cancelled');
        }

        if(LD::checkFeatureFlag('ccm-shifttype-new-filters')) {
            if(!empty($shiftType)) {
                $bucket->whereRaw("(CASE WHEN ifnull(recruiting_schedule.coverage_id,0) != 0  THEN 'Coverage' ELSE 'Training'  END) in ('".implode("','",$shiftType)."')");
            }

        }

            $bucket->whereRaw("CASE WHEN
            ifnull(recruiting_schedule.coverage_id,0) = 0
            THEN mssql_recruitertracker.Candidate_Current_Status
            ELSE ''
            END <> 'Inactive'");
        $bucket->where('recruiting_schedule.created_at','>','2023-03-27');

        H::ExcelLog($bucket->toSql(),'getClinicalCase.log');
        if($showCount) {
            if(LD::checkFeatureFlag('dashboard-past-due-count')) {
                $data2 = clone $bucket;
                $filtered_data = $data2->get()->filter(function ($data) {
                    return round((strtotime(date('Y-m-d H:i:s')) - strtotime($data->appointment_date)) / (60 * 60 * 24), 1) > 1;
                });
                return '<span>('.$bucket->count().' | '.$filtered_data->count().' Past Due)</span>';
            } else {
                return '('.$bucket->count().')';
            }
        }
        H::ExcelLog(print_r($bucket->get(), true),'getClinicalCase.log');
        return $bucket->get();
    }

    public function getClinicalExceptionBucket($type, $request = '',$showCount=0) {
        $exceptLineOfBusiness = ['PT','OT','ST'];
        $bucket = MssqlPatients::select('mssql_patients.*',
                'mssql_facilities.FacilityName as FacilityName',
                'mssql_patientstatus.PatientStatus as PatientStatus',
                DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"),
                'mssql_patients.created_at')
            ->leftJoin('mssql_patientstatus', 'mssql_patients.Patient_CurrentStatusID', '=', 'mssql_patientstatus.id')
            ->leftJoin('mssql_facilities', 'mssql_patients.aoc_facility_id', '=', 'mssql_facilities.id');
        $bucket->leftJoin("lob",function($query){
            $query->on("lob.LOBID","=","mssql_patients.LOBID");
        });
        $bucket->whereNotIn("lob.LOBName",$exceptLineOfBusiness);
        if(LD::checkFeatureFlag('ccmdashboard_show_exception')){
            $bucket->whereRaw("mssql_patients.Search_Name not like '%Test%'");
            $bucket->whereRaw("mssql_patients.Search_Name not like '%PRN%'");
            $bucket->whereIn('mssql_patientstatus.PatientStatus',['Active','Intake','On Hold','Pending SOC','Transferred']);
            $bucket->where('mssql_facilities.FacilityName','not like','FL - All Kids Care%');
        }
		if (LD::checkFeatureFlag('client-new-changes')) {
            $bucket->where('mssql_patients.IsLatestAdmit', 1);
        }
        $branch = [];
        $state = [];
        $region = [];
        $patient_status = [];
        $isMeMode = 0;
        if(isset($request['filters'])) {
            foreach($request['filters'] as $key => $value){
                if($value['name']=='ccmstate') {
                    $state[] = $value['value'];
                }
                if($value['name']=='ccmbranch') {
                    $branch[] = $value['value'];
                }
                if($value['name']=='ccmmemode') {
                    $isMeMode = 1;
                }
                if($value['name'] == 'ccmregion'){
                    $region[] = $value['value'];
                }
                if($value['name'] == 'ccmcurrentstatus'){
                    $patient_status[] = $value['value'];
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
        if(count($patient_status)>0) {
            $bucket->where(function($q) use ($patient_status){
                foreach($patient_status as $status) {
                    $q->orWhere("mssql_patients.Patient_CurrentStatusID",'=',$status);
                }
            });
        }else {
            $bucket->whereIn('mssql_patients.Patient_CurrentStatusID', ['1','4','6','7','8']);
        }
        if($isMeMode){
            $bucket->where(function($q) {
                $q->where("mssql_patients.ccm_id",'=', Auth::user()->id);
            });
        }else {
            $bucket->where(function($q){
                $q->orWhere('mssql_patients.ccm_id', '=', '0');
                $q->orWhereNull('mssql_patients.ccm_id');
                $q->orWhere('mssql_patients.ccm_id', '=', '');
            });
        }
        H::ExcelLog($bucket->toSql(), 'getClinicalExceptionBucket.log');
        if($showCount) {
            return '('.$bucket->count().')';
        }
        return $bucket->get();
    }
    public function getClinicalTodayIpea($type, $request = '',$showCount=0) {

        $mytime = Carbon::now();
        $today = $mytime->toDateString();
        $loggedInUser = H::getLoggedInUser();
        if(LD::checkFeatureFlag('multiple-roles-to-user')){
            $bucket = RecruitingSchedule::select('recruiting_schedule.*','linked_IPEA','mssql_recruitertracker.Candidate','mssql_recruitertracker.id as rtid','mssql_recruitertracker.Branch_Location','branch_locations.branch','mssql_recruitertracker.Candidate_Current_Status','mssql_recruitertracker.Candidate_Notes', DB::raw("(SELECT CONCAT(type,': ',DATE_FORMAT(appointment_date, '" . config('constant')['mysqlsearchdateformat'] . "'),' ',TIME_FORMAT(appointment_time, '" . config('constant')['mysqlsearchtimeformat'] . "')) FROM recruiting_schedule as rsinner left join schedule_types on rsinner.type_id = schedule_types.id WHERE rsinner.type_id <> 1 AND rsinner.appointment_date >= CURDATE()  AND rsinner.tracker_id = recruiting_schedule.tracker_id order by appointment_date asc, appointment_time desc limit 1) as NS"), DB::raw("(SELECT users.name from users left join users_has_roles on users.id = users_has_roles.user_id left join roles on users_has_roles.role_id = roles.id where roles.name='Orientation Coordinator' and users.status='1' and find_in_set(recruiting_schedule.ipea_location_id, users.branchid ) limit 1) as oc"));
        }else{
            $bucket = RecruitingSchedule::select('recruiting_schedule.*','linked_IPEA','mssql_recruitertracker.Candidate','mssql_recruitertracker.id as rtid','mssql_recruitertracker.Branch_Location','branch_locations.branch','mssql_recruitertracker.Candidate_Current_Status','mssql_recruitertracker.Candidate_Notes', DB::raw("(SELECT CONCAT(type,': ',DATE_FORMAT(appointment_date, '" . config('constant')['mysqlsearchdateformat'] . "'),' ',TIME_FORMAT(appointment_time, '" . config('constant')['mysqlsearchtimeformat'] . "')) FROM recruiting_schedule as rsinner left join schedule_types on rsinner.type_id = schedule_types.id WHERE rsinner.type_id <> 1 AND rsinner.appointment_date >= CURDATE()  AND rsinner.tracker_id = recruiting_schedule.tracker_id order by appointment_date asc, appointment_time desc limit 1) as NS"), DB::raw("(SELECT users.name from users left join roles on users.role=roles.id where roles.name='Orientation Coordinator' and users.status='1' and find_in_set(recruiting_schedule.ipea_location_id, users.branchid ) limit 1) as oc"));
        }

        $bucket->leftJoin('mssql_recruitertracker', function($join) {
            $join->on('recruiting_schedule.tracker_id', '=', 'mssql_recruitertracker.id');
        });
        $bucket->leftJoin('branch_locations', function($join) {
            $join->on('recruiting_schedule.ipea_location_id', '=', 'branch_locations.id');
        });

        $bucket->leftJoin("mssql_patients",function($query){
            $query->on("mssql_patients.id","=","recruiting_schedule.aoc_patient_id");
        });
        $bucket->leftJoin("mssql_facilities", function($query) {
            $query->on("mssql_facilities.id", "=", "mssql_patients.aoc_facility_id");
        });
        $bucket->leftJoin("users",function($query){
            $query->on("users.id","=","mssql_patients.ccm_id");
        });
        $bucket->where("recruiting_schedule.type_id",1);
        $bucket->where('appointment_date', $today);
        $branch = [];
        $state = [];
        $region = [];
        $isMeMode = 0;
        $users = [];
        if(isset($request['filters'])) {
            foreach($request['filters'] as $key => $value){
                if($value['name']=='ipeastate') {
                    $state[] = $value['value'];
                }
                if($value['name']=='ipeabranch') {
                    $branch[] = $value['value'];
                }
                if($value['name']=='ipearegion') {
                    $region[] = $value['value'];
                }
                if($value['name']=='ipeamemode') {
                    $isMeMode = 1;
                }
                if($value['name']=='ipeaclinicalcaremanager'){
                    $users[] = $value['value'];
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
            $bucket->whereIn("mssql_patients.ccm_id",$users);
        } else if($isMeMode) {
            $bucket->where("mssql_patients.ccm_id","=",Auth::user()->id);
        }

        if($showCount) {
            return '('.$bucket->count().')';
        }
        return $bucket->get();
    }

    public function getClinicalScheduledVisits($request = '',$showCount=0) {
        $providerID = H::getHelper('ConstantHelper')->getProviderIdByUserId(Auth::user()->id);

        $mytime = Carbon::now();
         $today = $mytime->toDateString();

        $bucket = DB::table('mssql_schedulemaster');
        $bucket->select('mssql_patients.PatientAccount','mssql_patients.id as aoc_patient_id',DB::raw("users.name AS ccm_id"),'mssql_facilities.FacilityName as FacilityName',
        DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"),DB::raw("DATE_FORMAT(mssql_schedulemaster.ScheduleDate, '" . config('constant')['mysqlsearchdateformat'] . "') as ScheduleDate"),'service_codes_daas.name');
        $bucket->leftJoin("mssql_providers",function($query){
            $query->on("mssql_providers.ProviderID","=","mssql_schedulemaster.ProviderID");
        });
        $bucket->leftJoin("mssql_patients",function($query){
            $query->on("mssql_patients.PatientID","=","mssql_schedulemaster.PatientID");
        });
        $bucket->leftJoin("mssql_facilities",function($query){
            $query->on("mssql_facilities.id","=","mssql_patients.aoc_facility_id");
        });
        $bucket->leftJoin("users",function($query){
            $query->on("users.email","=","mssql_providers.AOC_Email");
        });
        $bucket->leftJoin("service_codes_daas",function($query){
            $query->on("service_codes_daas.service_id","=","mssql_schedulemaster.ServiceCodeID");
        });
        $bucket->where("mssql_schedulemaster.ScheduleStatusID",5);

        $bucket->whereRaw("ifnull(users.id,0)<>0");
        $bucket->whereRaw("ifnull((select role_id from users_has_roles left join roles on users_has_roles.role_id = roles.id where users_has_roles.user_id = users.id and roles.name = 'Clinical Care Manager' limit 1),0) <> 0");
        $branch = $state = $users = $region = [];
        $isMeMode = 0;
        $startDate = '';
        $endDate = '';
        if(isset($request['filters'])) {


            foreach($request['filters'] as $key => $value){
                if($value['name']=='ccrsvstate') {
                    $state[] = $value['value'];
                }
                if($value['name']=='ccrbranch') {
                    $branch[] = $value['value'];
                }
                if($value['name']=='ccrclinicalcaremanager'){
                    $users[] = $value['value'];
                }
                if($value['name']=='ccrmemode') {
                    $isMeMode = 1;
                }
                if($value['name']=='region') {
                    if(!empty($value['value'])) {
                        $region[] = $value['value'];
                    }
                }
                if($value['name']=='fromschedulevisit_date') {
                    if(!empty($value['value'])) {
                        $startDate = $value['value'];
                    }
                }
                if($value['name']=='toschedulevisit_date') {
                    if(!empty($value['value'])) {
                        $endDate = $value['value'];
                    }
                }
            }
        }
        //if date range filter,  set where based on  date range, otherwise use the below as default
        if(!empty($startDate) || !empty($endDate)) {

            if(!empty($startDate)) {
                $startDate = date("Y-m-d",strtotime($startDate));
                $bucket->where("mssql_schedulemaster.ScheduleDate",">=",$startDate);
            }
            if(!empty($endDate)) {
                $endDate = date("Y-m-d",strtotime($endDate));
                $bucket->where("mssql_schedulemaster.ScheduleDate","<=",$endDate);
            }
        } else {
            $bucket->where("mssql_schedulemaster.ScheduleDate","=",$today);
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
                foreach($region as $r) {
                    $q->orWhere("mssql_facilities.region","=",$r);
                }
            });
        }

        if(!empty($users)){
            $bucket->whereIn("mssql_patients.ccm_id",$users);
        }
        if($isMeMode){
            $bucket->where(function($q) use ($providerID) {
                $q->where("mssql_schedulemaster.ProviderID",$providerID);
            });
        }

        if($showCount) {
            return '('.$bucket->count().')';
        }
        return $bucket->get();
    }
}
