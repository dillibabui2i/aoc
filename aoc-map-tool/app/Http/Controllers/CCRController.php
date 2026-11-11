<?php
namespace App\Http\Controllers;

use App\Enums\CCR\OutcomeStatusEnum;
use App\Helpers\H;
use App\Helpers\LD;
use App\MssqlProviders;
use App\Services\LogSq;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use DataTables;
use App\MssqlRecruitertracker;
use App\RecruitingSchedule;
use App\MssqlFacilities;
use App\MssqlPatients;
use App\Candidates;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Notifications;
use Exception;

class CCRController extends Controller
{
    public function getCandidateStartOutcomeForm(Request $request) {

        $attemptCount = 0;
        $rsid = $request->id;
        $type = $request->type;
        $typeTitle = '';
        $recruiterDetails = [];
        $providerDetails = [];
        $patientsDetails = [];
        $candidateDetails = [];
        $recruitingSchedule = [];

        if(!empty($rsid)) {
            $recruitingSchedule = RecruitingSchedule::find($rsid);
            if($recruitingSchedule?->attempt_count) {
                $attemptCount = $recruitingSchedule->attempt_count;
            }
            $recruiterDetails = MssqlRecruitertracker::find($recruitingSchedule?->tracker_id);

            if($recruitingSchedule?->aoc_provider_id != '') {
                $providerDetails = MssqlProviders::find($recruitingSchedule?->aoc_provider_id);
            }

            $aoc_patient_id = $recruitingSchedule?->aoc_patient_id;
            if(!empty($aoc_patient_id)) {
                $patientsDetails = MssqlPatients::find($aoc_patient_id);
            }
        }

        if($type=='first_shift_follow_up') {
            $typeTitle = 'First Shift Follow Up';
        } else if($type=='seven_day_follow_up') {
            $typeTitle = '7 Day Follow Up';
        } else if($type=='thirty_day_follow_up') {
            $typeTitle = '30 Day Follow Up';
        }

        return view('ccr.candidatestarts.outcomeform', ["rsid"=>$rsid,"attemptCount"=>$attemptCount,"type"=>$type,"typeTitle"=>$typeTitle,"recruitingSchedule"=>$recruitingSchedule,'candidateDetails'=>$candidateDetails, 'providerDetails'=>$providerDetails,'patientsDetails'=>$patientsDetails, 'recruiterDetails'=>$recruiterDetails]);
    }

    public function candidateStartOutcomeSave(Request $request){

        $reqData = $request->all();

        $rsid = $request->rsid;
        $type = $request->type;

        $text = '';
        $title = "";

        if($type=='first_shift_follow_up') {
            $title = "First Shift Follow Up";
        } else if($type=='seven_day_follow_up') {
            $title = "Seven Day Follow Up";
        } else if($type=='thirty_day_follow_up') {
            $title = "30 Day Follow Up";
        }

        if($rsid) {
            $recruitingSchedule = RecruitingSchedule::find($rsid);

            if ($reqData['outcome'] == 'Attempt 1' || $reqData['outcome'] == 'Attempt 2' || $reqData['outcome'] == 'Attempt 3') {

                $rsData['attempt_count'] = ($recruitingSchedule->attempt_count + 1);
                $text = Auth::user()->name . '  attempted to contact candidate for '.$title.'. (' . $reqData['outcome'] . ') <br> Comment :'.$reqData['contact_comment'].'';

            } else if($reqData['outcome']=='Completed') {
                $text = $title.": Marked Completed";
                if (isset($reqData['all_supplies_needed_present_in_the_home']) && !empty($reqData['all_supplies_needed_present_in_the_home'])) {
                    $text .= "All supplies needed present in the home:" . $reqData['all_supplies_needed_present_in_the_home'] . '<br>';
                }
                if (isset($reqData['additional_training_needed']) && !empty($reqData['additional_training_needed'])) {
                    $text .= "Additional training needed:" . $reqData['additional_training_needed'] . '<br>';
                }
                if (isset($reqData['any_questions_about_the_plan_of_care']) && !empty($reqData['any_questions_about_the_plan_of_care'])) {
                    $text .= "Any questions about the plan of care:" . $reqData['any_questions_about_the_plan_of_care'] . '<br>';
                }
                if (isset($reqData['kantime_charting']) && !empty($reqData['kantime_charting'])) {
                    $text .= "KanTime charting:" . $reqData['kantime_charting'] . '<br>';
                }
                if (isset($reqData['aware_of_contact_info_for_ccm_ccc_afterhours']) && !empty($reqData['aware_of_contact_info_for_ccm_ccc_afterhours'])) {
                    $text .= "Aware of contact info for CCM, CCC, Afterhours:" . $reqData['aware_of_contact_info_for_ccm_ccc_afterhours'] . '<br>';
                }

                if (isset($reqData['copy_clipboard_text']) && !empty($reqData['copy_clipboard_text'])) {
                    $text .= "Comment:" . $reqData['copy_clipboard_text'] . '<br>';
                }
                $rsData['attempt_count'] = 0;

                if(LD::checkFeatureFlag('ccr-auto-move-to-need-to-review-new-client')) {
                    $rsData[$type.'_que_one'] = $reqData['all_supplies_needed_present_in_the_home'];
                    $rsData[$type.'_que_two'] = $reqData['additional_training_needed'];
                    $rsData[$type.'_que_three'] = $reqData['any_questions_about_the_plan_of_care'];
                    $rsData[$type.'_que_four'] = $reqData['kantime_charting'];
                    $rsData[$type.'_que_five'] = $reqData['aware_of_contact_info_for_ccm_ccc_afterhours'];
                }



            } else if($reqData['outcome']=='No Longer on Case') {
                if(isset($reqData['no_longer_reason'])) {
                    $text = $title.": No Longer on Case:".$reqData['no_longer_reason'];
                    if(isset($reqData['nurse_left_agency_reason'])) {
                        $text.=" - ".$reqData['nurse_left_agency_reason'];
                    }
                } else {
                    $text = $title.": No Longer on Case";
                }



            } else if($reqData['outcome']=='Candidate already established with client') {
                $text = $title.": Candidate already established with client". '<br>';

                if (isset($reqData['copy_clipboard_text']) && !empty($reqData['copy_clipboard_text'])) {
                    $text .= "Comment:" . $reqData['copy_clipboard_text'] . '<br>';
                }
                $rsData['attempt_count'] = 0;
            }

            if($reqData['outcome']=='Attempt 3') {
                $rsData['attempt_count'] = 0;
                $reqData['outcome']='Unable to contact';
            }



            if($type=='first_shift_follow_up') {
                $rsData['first_followup_outcome'] = $reqData['outcome'];
            } else if($type=='seven_day_follow_up') {
                $rsData['sevenday_followup_outcome'] = $reqData['outcome'];
            } else if($type=='thirty_day_follow_up') {
                $rsData['thirtyday_followup_outcome'] = $reqData['outcome'];
            }

            $rsData['followed_up_at'] = date('Y-m-d H:i:s');
            $rsData['followed_up_by'] = Auth::user()->id;

            $noteData['entity'] = 'tracker-activity';
            $noteData['entity_id'] = $recruitingSchedule->tracker_id;
            $noteData['notes'] = $text;

            H::getHelper("ActivityNotes")->setActivityNotes($noteData);

            RecruitingSchedule::where('id', $rsid)->update($rsData);

            return response()->json(['success' => 1, 'message' => 'Infomation saved successfully', 'type' => $type]);
        } else {
            return response()->json(['success' => 0, 'message' => 'Something went wrong please try after some time.']);
        }
    }

    public function getCCMCandidateStarts(Request $request) {
        $data = H::getHelper("CCRCandidateStarts")->getCandidateStartData($request->input("type"),$request);
        return Datatables::of($data)
        ->addColumn('Action', function ($row) use($request){
            $btn = ' <a href="javascript:void(0);" onclick="showCoverageTrackerStepForm(\'' . $request->input("type") . '\',\'' . $row->id . '\', this)"><i class="fa fa-vcard" aria-hidden="true"></i></a>';
            return $btn;
        })->addColumn('select', function ($row) use($request){
            $checkbox = '';
            if(LD::checkFeatureFlag("candidate_start_reassign_calls")) {
                $checkbox = '<input type="checkbox" name="checkbox-'.$request->input("type").'[]" class="form-input td select-all-' . str_replace(' ', '_', $request->input("type")) . '" value="' . $row->tracker_id . '" />';
            }
            return $checkbox;
        })->editColumn('Clinician_Candidate', function ($row) {
            if(empty($row->Clinician_Candidate)) {
                return $row->Clinician;
            } else {
                return $row->Clinician_Candidate;
            }
        })->editColumn('candidates_homePhone', function ($row) {
            if(empty($row->candidates_homePhone)) {
                return $row->provider_homephone;
            } else {
                return $row->candidates_homePhone;
            }
        })->editColumn('candidates_mobile', function ($row) {
            if(empty($row->candidates_mobile)) {
                return $row->provider_mobile;
            } else {
                return $row->candidates_mobile;
            }
        })->addColumn('candidate_date_diff', function ($row) use($request) {
            $type = $request->input("type");
            $appDate = date_create($row->appointment_date);
            $currentDate = date_create(date("Y-m-d"));
            $diff=date_diff($appDate,$currentDate)->format("%a");
            if($type=='first_shift_follow_up') {
                if($diff>1) {
                    return 1;
                }
            } else if($type=='seven_day_follow_up') {
                $diff=date_diff($appDate,$currentDate)->format("%a");
                if($diff>7) {
                    return 1;
                }
            } else if($type=='thirty_day_follow_up') {
                if($diff>30) {
                    return 1;
                }
            }
        })->editColumn('appointment_date', function ($row) {
            return H::getFormatedDate($row->appointment_date);
        })->escapeColumns('select')->make(true);
    }
    public function reloadCCMCandidateStarts(Request $request) {
        return json_encode(["counter"=>H::getHelper('CCRCandidateStarts')->getCandidateStartData($request->input("type"),$request,1),"type"=>$request->input("type")]);
    }

    public function performCCRData($request,$columns,$mode="showAll") {

            $filters = $request->all();

            $search = $request->input('search.value');
            $limit = $request->input('length');
            $start = $request->input('start');
            $order = isset($columns[$request->input('order.0.column')]) ? $columns[$request->input('order.0.column')]['name'] : '';
            $dir = $request->input('order.0.dir');
            $ccm_query = DB::raw("users.name AS ccm");
            if(LD::checkFeatureFlag('change-assign-ccr-process')){
                $ccm_query = DB::raw("IFNULL(users.name, 'Unassigned') AS ccm");
            }
            $q = DB::table('recruiting_schedule')
                ->leftJoin('mssql_patients', 'mssql_patients.id', '=', 'recruiting_schedule.aoc_patient_id')
                ->leftJoin('mssql_providers', 'mssql_providers.id', '=', 'recruiting_schedule.aoc_provider_id')
                ->leftJoin('candidates', 'candidates.id', '=', 'recruiting_schedule.aoc_candidate_id')
                //->leftJoin('users', 'mssql_patients.ccm_id', '=', 'users.id')
                ->select("recruiting_schedule.*","users.name",$ccm_query
                ,DB::raw("(CASE WHEN recruiting_schedule.aoc_candidate_id IS NOT NULL
                THEN CONCAT(candidates.firstName,' ',candidates.lastName)
                WHEN recruiting_schedule.aoc_provider_id IS NOT NULL
                THEN CONCAT(mssql_providers.FirstName,' ',mssql_providers.LastName)  END) AS candidate_name")
                ,DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS client_name"),
                DB::raw("mssql_providers.Phone AS clinician_phone"),DB::raw(" mssql_providers.Mobile AS clinician_mobile")
                ,DB::raw("candidates.mobile AS candidatetbl_mobile")
                ,DB::raw("candidates.homePhone AS candidatetbl_phone")
                ,DB::raw('CASE WHEN
            ifnull(recruiting_schedule.coverage_id,0) != 0
            THEN \'Coverage\'
            ELSE \'Training\'
            END as shift_type')
            );

            $q->leftJoin("mssql_recruitertracker",function($query){
                $query->on("mssql_recruitertracker.id","=","recruiting_schedule.tracker_id");
            });
            if(LD::checkFeatureFlag('assign_ccm_to_coverage_tracker')){
                $q->leftJoin("users", function($query) {
                    $query->on("users.id", "=", DB::raw("
                        CASE
                            WHEN mssql_recruitertracker.assigned_ccr <> '' THEN mssql_recruitertracker.assigned_ccr
                            WHEN recruiting_schedule.ccm_id IS NOT NULL AND recruiting_schedule.ccm_id <> 0 THEN recruiting_schedule.ccm_id
                            ELSE mssql_patients.ccm_id
                        END
                    "));
                });
            } else {
                $q->leftJoin("users",function($query){
                    $query->on("users.id","=",DB::raw("IF(mssql_recruitertracker.assigned_ccr <> '' , mssql_recruitertracker.assigned_ccr, mssql_patients.ccm_id)"));
                });
            }


            $q->where('recruiting_schedule.type_id', 2);
            if (LD::checkFeatureFlag('limit-ccr-show-all-page-to-curent-date')) {
                $q->whereDate("recruiting_schedule.appointment_date", '>=', '2023-03-27');
            }

            if(LD::checkFeatureFlag("fix-ccr-show-all-page-issue")) {
                $q->whereRaw('IF(recruiting_schedule.aoc_provider_id = \'\' OR
                recruiting_schedule.aoc_provider_id is NULL,0 = 0,mssql_providers.is_deleted = 0)');
            } else {
                $q->where('mssql_providers.is_deleted',0);
            }

            $totalData = $q->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $q->whereNested(function($q) use ($search) {
                    $q->where('mssql_providers.firstName', 'LIKE', "%{$search}%")
                    ->orWhere('mssql_providers.lastName', 'LIKE', "%{$search}%")
                    ->orWhere('mssql_patients.Patient_FirstName', 'LIKE', "%{$search}%")
                    ->orWhere('mssql_patients.Patient_LastName', 'LIKE', "%{$search}%")

                    ->orWhere('mssql_providers.Phone', 'LIKE', "%{$search}%")
                    ->orWhere('mssql_providers.Mobile', 'LIKE', "%{$search}%")

                    ->orWhere('recruiting_schedule.outcome', 'LIKE', "%{$search}%")
                    ->orWhere('candidates.firstName', 'LIKE', "%{$search}%")
                    ->orWhere('candidates.lastName', 'LIKE', "%{$search}%")
                    ->orWhere('candidates.mobile', 'LIKE', "%{$search}%")
                    ->orWhere('users.name', 'LIKE', "%{$search}%")
                    ->orWhere(DB::raw("CONCAT(mssql_providers.firstName, ' ',mssql_providers.lastName)"), 'LIKE', "%{$search}%")
                    ->orWhere('candidates.homePhone', 'LIKE', "%{$search}%");
                });
                $totalFiltered = $q->count();
            }
            if (!empty($filters['filters'])) {


                $branch = [];
                $state = [];
                $users = [];
                $startDate = '';
                $endDate = '';

                foreach($request['filters'] as $key => $value){

                    if($value['name']=='State') {
                        $state[] = $value['value'];
                    }
                    if($value['name']=='Branch') {
                        $branch[] = $value['value'];
                    }
                    if($value['name']=='clinical_care_manager'){
                        $users[] = $value['value'];
                    }
                    if($value['name']=='fromAppointmentDate') {
                        if(!empty($value['value'])) {
                            $startDate = $value['value'];
                        }
                    }
                    if($value['name']=='toAppointmentDate') {
                        if(!empty($value['value'])) {
                            $endDate = $value['value'];
                        }
                    }
                }


                if(count($state)>0) {
                    $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
                    $q->where(function($bucket) use ($facilities){
                        foreach($facilities as $facility) {
                            $bucket->orWhere("mssql_patients.aoc_facility_id","=",$facility->id);
                        }
                    });
                }
                if(count($branch)>0) {
                    $q->where(function($bucket) use ($branch){
                        foreach($branch as $b) {
                            $bucket->orWhere("mssql_patients.aoc_facility_id","=",$b);
                        }
                    });
                }
                if(!empty($users)){
                    $q->whereIn("mssql_patients.ccm_id",$users);
                }

                if(!empty($startDate)) {
                    $convertedStartDate = date('Y-m-d',strtotime($startDate));
                    $q->whereDate("recruiting_schedule.appointment_date", ">=",$convertedStartDate);
                }

                if(!empty($endDate)) {
                    $convertedEndDate = date('Y-m-d',strtotime($endDate));
                    $q->whereDate("recruiting_schedule.appointment_date", "<=",$convertedEndDate);
                }

                $totalFiltered = $q->count();
            }

            if($mode != 'export'){
                $data = $q->offset($start)->limit($limit)->orderBy($order, $dir)->get();
            }else{
                $data = $q->get();
            }
            return ['data'=>$data,'totalData'=>$totalData,'totalFiltered'=>$totalFiltered];

    }


    public function performCCMCandidateStartData($request,$columns,$mode="showAll") {

        DB::enableQueryLog();

        $filters = $request->all();
        $appDate = date("Y-m-d");
        $startAppDate = date("Y-m-d",strtotime("-6 days"));
        $endAppDate = date("Y-m-d",strtotime("-30 days"));
        $thendAppDate = date("Y-m-d",strtotime("-29 days"));
        $search = $request->input('search.value');
        $limit = $request->input('length');
        $start = $request->input('start');
        $order = isset($columns[$request->input('order.0.column')]) ? $columns[$request->input('order.0.column')]['name'] : '';
        $dir = $request->input('order.0.dir');

        if(LD::checkFeatureFlag('add-client-branch-column-in-candidatestarts-export')){
            $q = DB::table('recruiting_schedule')
            ->leftJoin('mssql_patients', 'mssql_patients.id', '=', 'recruiting_schedule.aoc_patient_id')
            ->leftJoin('mssql_providers', 'mssql_providers.id', '=', 'recruiting_schedule.aoc_provider_id')
            ->leftJoin('candidates', 'candidates.id', '=', 'recruiting_schedule.aoc_candidate_id')
            ->leftJoin('users', 'mssql_patients.ccm_id', '=', 'users.id')
            ->leftJoin('mssql_facilities', 'mssql_patients.FacilityID', '=', 'mssql_facilities.FacilityID')
            ->select("recruiting_schedule.*","users.name",DB::raw("users.name as ccm"),
            DB::raw("CONCAT(mssql_providers.firstName,' ',mssql_providers.lastName) AS candidate_name"),
            DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS client_name"),
            DB::raw("mssql_providers.Phone AS clinician_phone"),DB::raw(" mssql_providers.Mobile AS clinician_mobile")
            ,DB::raw("CONCAT(candidates.firstName,' ',candidates.lastName) AS candidatetbl_name")
            ,DB::raw("candidates.mobile AS candidatetbl_mobile")
            ,DB::raw("candidates.homePhone AS candidatetbl_phone")
            ,DB::raw("(CASE WHEN recruiting_schedule.first_followup_outcome IS NOT NULL
            THEN recruiting_schedule.first_followup_outcome
            WHEN recruiting_schedule.sevenday_followup_outcome IS NOT NULL
            THEN recruiting_schedule.sevenday_followup_outcome
            WHEN recruiting_schedule.thirtyday_followup_outcome IS NOT NULL
            THEN recruiting_schedule.thirtyday_followup_outcome END) AS outcome_ff"),
           DB::raw("(
                CASE
                WHEN ifnull(first_followup_outcome,'Attempt') like 'Attempt%' then 'first_shift_follow_up'
                WHEN ifnull(sevenday_followup_outcome,'Attempt') like 'Attempt%' then 'seven_day_follow_up'
                ELSE 'thirty_day_follow_up'
                END
           ) as ff_type"),
           "mssql_facilities.FacilityName",
           "recruiting_schedule.first_followup_outcome as firstFollowupOutcome",
           "recruiting_schedule.sevenday_followup_outcome as sevendayFollowupOutcome",
           "recruiting_schedule.thirtyday_followup_outcome as thirtydayFollowupOutcome");
        }else{
            $q = DB::table('recruiting_schedule')
            ->leftJoin('mssql_patients', 'mssql_patients.id', '=', 'recruiting_schedule.aoc_patient_id')
            ->leftJoin('mssql_providers', 'mssql_providers.id', '=', 'recruiting_schedule.aoc_provider_id')
            ->leftJoin('candidates', 'candidates.id', '=', 'recruiting_schedule.aoc_candidate_id')
            ->leftJoin('users', 'mssql_patients.ccm_id', '=', 'users.id')
            ->select("recruiting_schedule.*","users.name",DB::raw("users.name as ccm"),
            DB::raw("CONCAT(mssql_providers.firstName,' ',mssql_providers.lastName) AS candidate_name"),
            DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS client_name"),
            DB::raw("mssql_providers.Phone AS clinician_phone"),DB::raw(" mssql_providers.Mobile AS clinician_mobile")
            ,DB::raw("CONCAT(candidates.firstName,' ',candidates.lastName) AS candidatetbl_name")
            ,DB::raw("candidates.mobile AS candidatetbl_mobile")
            ,DB::raw("candidates.homePhone AS candidatetbl_phone")
            ,DB::raw("(CASE WHEN recruiting_schedule.first_followup_outcome IS NOT NULL
            THEN recruiting_schedule.first_followup_outcome
            WHEN recruiting_schedule.sevenday_followup_outcome IS NOT NULL
            THEN recruiting_schedule.sevenday_followup_outcome
            WHEN recruiting_schedule.thirtyday_followup_outcome IS NOT NULL
            THEN recruiting_schedule.thirtyday_followup_outcome END) AS outcome_ff"),
           DB::raw("(
                CASE
                WHEN ifnull(first_followup_outcome,'Attempt') like 'Attempt%' then 'first_shift_follow_up'
                WHEN ifnull(sevenday_followup_outcome,'Attempt') like 'Attempt%' then 'seven_day_follow_up'
                ELSE 'thirty_day_follow_up'
                END
           ) as ff_type"));
        }
        $q->where("recruiting_schedule.type_id",6);
        $q->where('mssql_providers.is_deleted',0);
        $q->where("mssql_patients.Search_Name", "not like" , "%PRN%");
        $rsOutcome = ['Client/caregiver declined services'];
        $q->whereNotIn("recruiting_schedule.outcome",$rsOutcome);

        $totalData = $q->count();
        $totalFiltered = $totalData;


        if (!empty($search)) {
            $q->whereNested(function($q) use ($search) {
                $q->where('mssql_providers.firstName', 'LIKE', "%{$search}%")
                ->orWhere('mssql_providers.lastName', 'LIKE', "%{$search}%")
                ->orWhere('mssql_patients.Patient_FirstName', 'LIKE', "%{$search}%")
                ->orWhere('mssql_patients.Patient_LastName', 'LIKE', "%{$search}%")

                ->orWhere('mssql_providers.Phone', 'LIKE', "%{$search}%")
                ->orWhere('mssql_providers.Mobile', 'LIKE', "%{$search}%")

                ->orWhere('recruiting_schedule.outcome', 'LIKE', "%{$search}%")
                ->orWhere('candidates.firstName', 'LIKE', "%{$search}%")
                ->orWhere('candidates.lastName', 'LIKE', "%{$search}%")
                ->orWhere(DB::raw("CONCAT(mssql_providers.firstName, ' ',mssql_providers.lastName)"), 'LIKE', "%{$search}%")
                ->orWhere('candidates.mobile', 'LIKE', "%{$search}%")
                ->orWhere('users.name', 'LIKE', "%{$search}%")
                ->orWhere('recruiting_schedule.first_followup_outcome', 'LIKE', "%{$search}%")
                ->orWhere('recruiting_schedule.sevenday_followup_outcome', 'LIKE', "%{$search}%")
                ->orWhere('recruiting_schedule.thirtyday_followup_outcome', 'LIKE', "%{$search}%")
                ->orWhere('candidates.homePhone', 'LIKE', "%{$search}%");

            });
            $totalFiltered = $q->count();
        }
        if (!empty($filters['filters'])) {


            $branch = [];
            $state = [];
            $users = [];
            $payer = [];
            $payer_group = [];
            $startDate = '';
            $endDate = '';

            foreach($request['filters'] as $key => $value){

                if($value['name']=='State') {
                    $state[] = $value['value'];
                }
                if($value['name']=='Branch') {
                    $branch[] = $value['value'];
                }
                if($value['name']=='clinical_care_manager'){
                    $users[] = $value['value'];
                }
                if($value['name']=='fromAppointmentDate') {
                    if(!empty($value['value'])) {
                        $startDate = $value['value'];
                    }
                }
                if($value['name']=='toAppointmentDate') {
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


            if(count($state)>0) {
                $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
                $q->where(function($bucket) use ($facilities){
                    foreach($facilities as $facility) {
                        $bucket->orWhere("mssql_patients.aoc_facility_id","=",$facility->id);
                    }
                });
            }
            if(count($branch)>0) {
                $q->where(function($bucket) use ($branch){
                    foreach($branch as $b) {
                        $bucket->orWhere("mssql_patients.aoc_facility_id","=",$b);
                    }
                });
            }
            if(!empty($users)){
                $q->whereIn("mssql_patients.ccm_id",$users);
            }

            if(!empty($startDate)) {
                $convertedStartDate = date('Y-m-d',strtotime($startDate));
                $q->whereDate("recruiting_schedule.appointment_date", ">=",$convertedStartDate);
            }

            if(!empty($endDate)) {
                $convertedEndDate = date('Y-m-d',strtotime($endDate));
                $q->whereDate("recruiting_schedule.appointment_date", "<=",$convertedEndDate);
            }

            if(count($payer)>0) {
                $q->where(function($bucket) use ($payer){
                    foreach($payer as $p) {
                        $bucket->orWhere("mssql_patients.Payer","=",$p);
                    }
                });
            }

            if(count($payer_group)>0) {
                $q->where(function($bucket) use ($payer_group){
                    foreach($payer_group as $pg) {
                        $bucket->orWhere("mssql_patients.payer_group","=",$pg);
                    }
                });
            }

            $totalFiltered = $q->count();
        }

        if($mode != 'export'){
            $data = $q->offset($start)->limit($limit)->orderBy($order, $dir)->get();
        }else{
            $data = $q->get();
        }

        LogSq::processSqlLogs('CCRController-performCCMCandidateStartData');

        return ['data'=>$data,'totalData'=>$totalData,'totalFiltered'=>$totalFiltered];

    }


    public function getCCMCandidateStartsListing(Request $request) {
        DB::enableQueryLog();

        $columns = [
            ['name' => 'ccm','label' => 'Case Manager', 'width' => '0'],
            ['name' => 'candidate_name', 'label' => 'Candidate', 'width' => '20%'],
            ['name' => 'appointment_date', 'label' => 'Candidate Start Date', 'width' => '10%'],
            ['name' => 'client_name', 'label' => 'Client', 'width' => '15%'],
            ['name' => 'clinician_phone', 'label' => 'Phone Number', 'width' => '10%'],
            ['name' => 'clinician_mobile', 'label' => 'Mobile Phone Number', 'width' => '10%'],
            ['name' => 'outcome_ff', 'label' => 'Outcome', 'width' => '10%']

        ];
        if ($request->ajax()) {
            $res = $this->performCCMCandidateStartData($request,$columns);

            LogSq::processSqlLogs('CCRController-getCCMCandidateStartsListing');

            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($res['totalData']),
                'recordsFiltered' => intval($res['totalFiltered']),
                'data'            => $this->DataProcess($res['data']),
            ]);
        }
        return view('ccr.candidatestarts.grid', ['columns' => $columns]);
    }



    public function getCCRListing(Request $request) {
        DB::enableQueryLog();

        $columns = [
            ['name' => 'ccm','label' => 'CCM', 'width' => '0'],
            ['name' => 'candidate_name', 'label' => 'Candidate', 'width' => '20%'],
            ['name' => 'client_name', 'label' => 'Client', 'width' => '10%'],
            ['name' => 'appointment_date', 'label' => 'CCR Request Date', 'width' => '15%'],
            ['name' => 'clinician_phone', 'label' => 'Phone Number', 'width' => '10%'],
            ['name' => 'clinician_mobile', 'label' => 'Mobile Phone Number', 'width' => '10%'],
            ['name' => 'outcome','label' => 'Outcome', 'width' => '10%']

        ];
        if ($request->ajax()) {
            $res = $this->performCCRData($request,$columns);

            LogSq::processSqlLogs('CCRController-getCCRListing');

            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($res['totalData']),
                'recordsFiltered' => intval($res['totalFiltered']),
                'data'            => $this->DataProcess($res['data']),
            ]);
        }
        return view('ccr.grid', ['columns' => $columns]);
    }

    private function DataProcess($data) {
        foreach ($data as $key => $value) {

            if(empty($value->candidate_name) && isset($value->candidatetbl_name)) {
                $value->candidate_name = $value->candidatetbl_name;
            }

            if(empty($value->clinician_phone)) {
                $value->clinician_phone = $value->candidatetbl_phone;
            }

            if(empty($value->clinician_mobile)) {
                $value->clinician_mobile = $value->candidatetbl_mobile;
            }

            $value->appointment_date = H::getFormatedDate($value->appointment_date);
        }
        return $data;
    }

    public function exportCCMCandidateStart(Request $request) {

        $columns = [
            ['name' => 'ccm','label' => 'CCM', 'width' => '0'],
            ['name' => 'candidate_name', 'label' => 'Candidate', 'width' => '20%'],
            ['name' => 'appointment_date', 'label' => 'Candidate Start Date', 'width' => '10%'],
            ['name' => 'client_name', 'label' => 'Client', 'width' => '15%'],
            ['name' => 'clinician_phone', 'label' => 'Phone Number', 'width' => '10%'],
            ['name' => 'clinician_mobile', 'label' => 'Mobile Phone Number', 'width' => '10%']

        ];
        if(LD::checkFeatureFlag('ccr-auto-move-to-need-to-review-new-client')) {
            $dataTableFields = [
                "ccm" => 'CCM',
                'candidate_name' => 'Candidate',
                'appointment_date'=>'Candidate Start Date',
                'client_name'=>'Client',
                'clinician_phone'=>'Phone Number',
                'clinician_mobile'=>'Mobile Phone Number',
                'first_shift_follow_up_que_one'=>'1st Shift Question 1',
                'first_shift_follow_up_que_two'=>'1st Shift Question 2',
                'first_shift_follow_up_que_three'=>'1st Shift Question 3',
                'first_shift_follow_up_que_four'=>'1st Shift Question 4',
                'first_shift_follow_up_que_five'=>'1st Shift Question 5',
                'seven_day_follow_up_que_one'=>'7 Day Question 1',
                'seven_day_follow_up_que_two'=>'7 Day Question 2',
                'seven_day_follow_up_que_three'=>'7 Day Question 3',
                'seven_day_follow_up_que_four'=>'7 Day Question 4',
                'seven_day_follow_up_que_five'=>'7 Day Question 5',
                'thirty_day_follow_up_que_one'=>'30 Day Question 1',
                'thirty_day_follow_up_que_two'=>'30 Day Question 2',
                'thirty_day_follow_up_que_three'=>'30 Day Question 3',
                'thirty_day_follow_up_que_four'=>'30 Day Question 4',
                'thirty_day_follow_up_que_five'=>'30 Day Question 5'
            ];
        } else {
            $dataTableFields = [
                "ccm" => 'CCM',
                'candidate_name' => 'Candidate',
                'appointment_date'=>'Candidate Start Date',
                'client_name'=>'Client',
                'clinician_phone'=>'Phone Number',
                'clinician_mobile'=>'Mobile Phone Number'
            ];
        }
        if(LD::checkFeatureFlag('add-client-branch-column-in-candidatestarts-export')){
            $newKey = 'FacilityName';
            $newValue = 'Client Branch';
            $firstNewKey = 'firstFollowupOutcome';
            $firstNewValue = '1st shift call outcome';
            $sevenNewKey = 'sevendayFollowupOutcome';
            $sevenNewValue = '7 Day call outcome';
            $thirtydayNewKey = 'thirtydayFollowupOutcome';
            $thirtydayNewValue = '30 Day call outcome';
            $newDataTableFields = [];
            foreach ($dataTableFields as $key => $value) {
                $newDataTableFields[$key] = $value;
                if ($key === 'client_name') {
                    $newDataTableFields[$newKey] = $newValue;
                }
                if ($key === 'clinician_mobile') {
                    $newDataTableFields[$firstNewKey] = $firstNewValue;
                }
                if($key === 'first_shift_follow_up_que_five'){
                    $newDataTableFields[$sevenNewKey] = $sevenNewValue;
                }
                if($key === 'seven_day_follow_up_que_five'){
                    $newDataTableFields[$thirtydayNewKey] = $thirtydayNewValue;
                }
            }
            $dataTableFields = $newDataTableFields;
        }
        $res = $this->performCCMCandidateStartData($request,[],'export');
        $data = json_decode(json_encode($res['data']->toArray()), true);
        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=ccmcandidatestart.csv",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];
        $callback = function () use ($data, $dataTableFields) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_map(function ($value) {return $value;},$dataTableFields));
            foreach ($data as $tableData) {
                $cntr = 0;
                $csvData = [];
                foreach ($dataTableFields as $fields => $val) {
                    $csvData[] = $tableData[$fields];
                    $cntr++;
                }
                fputcsv($file, $csvData);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function exportCCRData(Request $request){


        $dataTableFields = [
            "ccm" => 'CCM',
            'candidate_name' => 'Candidate',
            'client_name'=>'Client',
            'appointment_date'=>'CCR Request Date',
            'clinician_phone'=>'Phone Number',
            'clinician_mobile'=>'Mobile Phone Number',
            'outcome'=>'Outcome'
        ];
        $res = $this->performCCRData($request,[],'export');
        $data = json_decode(json_encode($res['data']->toArray()), true);
        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=ccrexport.csv",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];
        $callback = function () use ($data, $dataTableFields) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_map(function ($value) {return $value;},$dataTableFields));
            foreach ($data as $tableData) {
                $cntr = 0;
                $csvData = [];
                foreach ($dataTableFields as $fields => $val) {
                    $csvData[] = $tableData[$fields];
                    $cntr++;
                }
                fputcsv($file, $csvData);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);

    }


    public function showSNSForm(Request $request){
        $recruiterDetails = [];
        $tracker_id = 0;
        $attemptCount = 0;
        $providerDetails = [];
        $patientsDetails = [];
        $candidateDetails = [];
        $recruitingScheduleId = $request->id;
        $recruitingSchedule = [];
        if(LD::checkFeatureFlag('coverage-tracker-view-action')){
            if(!empty($request->id)) {
                if($request->type == 'coverage'){
                    $recruitingSchedule = RecruitingSchedule::find($request->id);

                    if($recruitingSchedule->attempt_count) {
                        $attemptCount = $recruitingSchedule->attempt_count;
                    }
                    if($recruitingSchedule->aoc_provider_id != '') {
                        $providerDetails = MssqlProviders::find($recruitingSchedule->aoc_provider_id);
                    }

                    $aoc_patient_id = $recruitingSchedule->aoc_patient_id;
                    if($aoc_patient_id != '' && $aoc_patient_id !=0) {
                        $patientsDetails = MssqlPatients::find($recruitingSchedule->aoc_patient_id);
                    }
                    if($recruitingSchedule->coverage_id != '' && $recruitingSchedule->coverage_id !=0) {
                        $coverageDetails = DB::table('coverage_tracker')->
                        where('id',$recruitingSchedule->coverage_id)
                        ->first();
                    }
                    return view('ccr.nextstepNew',['attemptCount'=>$attemptCount,"recruitingSchedule"=>$recruitingSchedule,"recruitingScheduleId"=>$recruitingScheduleId,'providerDetails'=>$providerDetails,'patientsDetails'=>$patientsDetails,'coverageDetails'=>$coverageDetails,'type'=>$request->type]);
                }else{
                    $recruitingSchedule = RecruitingSchedule::find($request->id);
                    $tracker_id = $recruitingSchedule?->tracker_id;

                    if($recruitingSchedule?->attempt_count) {
                        $attemptCount = $recruitingSchedule->attempt_count;
                    }

                    $recruiterDetails = $tracker_id
                        ? MssqlRecruitertracker::find($recruitingSchedule->tracker_id)
                        : null;

                    if(!empty($recruiterDetails->aoc_provider_id)) {
                        $providerDetails = MssqlProviders::find($recruiterDetails->aoc_provider_id);
                    }

                    $aoc_patient_id = $recruitingSchedule?->aoc_patient_id;
                    if(!empty($aoc_patient_id)) {
                        $patientsDetails = MssqlPatients::find($aoc_patient_id);
                    }

                    if(!empty($recruiterDetails->aoc_candidate_id)) {
                        $candidateDetails = Candidates::find($recruiterDetails->aoc_candidate_id);
                    }

                    return view('ccr.nextstepNew',['tracker_id'=>$tracker_id, 'attemptCount'=>$attemptCount,"recruitingSchedule"=>$recruitingSchedule,"recruitingScheduleId"=>$recruitingScheduleId,'candidateDetails'=>$candidateDetails, 'providerDetails'=>$providerDetails,'patientsDetails'=>$patientsDetails, 'recruiterDetails'=>$recruiterDetails,'type'=>$request->type]);
                }
            }
        }else{
            if(!empty($request->id)) {
                $recruitingSchedule = RecruitingSchedule::find($request->id);
                $tracker_id = $recruitingSchedule->tracker_id;
                if($recruitingSchedule->attempt_count) {
                    $attemptCount = $recruitingSchedule->attempt_count;
                }
                $recruiterDetails = MssqlRecruitertracker::find($recruitingSchedule->tracker_id);

                if($recruiterDetails->aoc_provider_id != '') {
                    $providerDetails = MssqlProviders::find($recruiterDetails->aoc_provider_id);
                }

                $aoc_patient_id = $recruiterDetails->aoc_patient_id;
                if($aoc_patient_id != '' && $aoc_patient_id !=0) {
                    $patientsDetails = MssqlPatients::find($recruiterDetails->aoc_patient_id);
                }

                if($recruiterDetails->aoc_candidate_id != '') {
                    $candidateDetails = Candidates::find($recruiterDetails->aoc_candidate_id);
                }
            }

            return view('ccr.nextstep',['tracker_id'=>$tracker_id, 'attemptCount'=>$attemptCount,"recruitingSchedule"=>$recruitingSchedule,"recruitingScheduleId"=>$recruitingScheduleId,'candidateDetails'=>$candidateDetails, 'providerDetails'=>$providerDetails,'patientsDetails'=>$patientsDetails, 'recruiterDetails'=>$recruiterDetails]);
        }

    }

    public function saveCCRScheduleNext(Request $request)
    {

        $reqData = $request->all();
        $rsData = [];
        $upRecruiterTracker = [];
        $noteData = [];
        if(LD::checkFeatureFlag('coverage-tracker-view-action')){
            $isCoverageType = false;
            $coverageDetails = [];
            if(isset($reqData['coverage_id']) && $reqData['coverage_id'] != ''){
                $isCoverageType = true;
                $coverageDetails = DB::table('coverage_tracker')->where('id', $reqData['coverage_id'])->first();
            }
        }
        if (!empty($reqData['recruitingScheduleId'])) {

            $recruitingSchedule = RecruitingSchedule::find($reqData['recruitingScheduleId']);

            if ($reqData['outcome'] == 'Attempt 1' || $reqData['outcome'] == 'Attempt 2' || $reqData['outcome'] == 'Attempt 3') {

                $rsData['attempt_count'] = (($recruitingSchedule?->attempt_count ?? 0) + 1);
                if($reqData['outcome']=='Attempt 3') {
                    $rsData['outcome'] = "Unable to contact";
                    $notificationIds = [];
                    $candidateName = '';
                    $patientName = '';
                    $facililtyId = 0;
                    $facilityUsers = [];

                    if(!empty($recruitingSchedule?->created_by)) {
                        $notificationIds[] = $recruitingSchedule->created_by;
                    }

                    if(!empty($reqData['patient_id'])) {
                        $patient = MssqlPatients::select(DB::raw("CONCAT(Patient_FirstName,' ', Patient_LastName) as patient_name"),"aoc_facility_id",'ccc_id')->find($reqData['patient_id']);
                        if(isset($patient->patient_name)) {
                            $patientName = $patient->patient_name;
                        }
                        if(isset($patient->aoc_facility_id)) {
                            $facililtyId = $patient->aoc_facility_id;
                        }
                    }

                    $roles = ['State VP', 'Regional Director of Operations', 'DBO'];
                    if(is_array($roles)) {
                        $userIds= H::getHelper("ConstantHelper")->getUserIdsByRolesAndFacility($roles,$facililtyId);
                    }


                    if(!empty($reqData['provider_id'])) {
                        $provider = MssqlProviders::select(DB::raw("CONCAT(FirstName,' ', LastName) as provider_name"))->find($reqData['provider_id']);
                        if(isset($provider->provider_name)) {
                            $candidateName = $provider->provider_name;
                        }
                    }
                    if(empty($candidateName)) {
                        if(!empty($reqData['candidate_id'])) {
                            $candidates = Candidates::select(DB::raw("CONCAT(firstName,' ', lastName) as candidate_name"))->find($reqData['candidate_id']);
                            if(isset($candidates->candidate_name)) {
                                $candidateName = $candidates->candidate_name;
                            }
                        }
                    }
                    if(LD::checkFeatureFlag('coverage-tracker-view-action')){
                        if($isCoverageType){
                            $notificationIds = [$coverageDetails->created_by,$patient->ccc_id];
                            foreach($notificationIds as $user) {
                                $notificationData = [];
                                $notificationData['user_id'] = $user;
                                $notificationData['title'] = 'Third and final CCR attempt was made for '.$candidateName.' with '.$patientName;
                                $notificationData['type'] = 'coverage';
                                $notificationData['related_item'] = $reqData['coverage_id'];
                                $notificationData['notes'] = '';
                                Notifications::addNotification($notificationData);
                            }
                            $noteData['entity'] = 'client';
                            $noteData['type'] = 'coverage';
                            $noteData['entity_id'] = Arr::get($reqData, 'patient_id');
                            $noteData['notes'] = 'Third and final CCR attempt was made for '.$candidateName.' with '.$patientName;
                            $noteData['provider_id'] = Arr::get($reqData, 'provider_id');
                            $noteData['patient_id'] = Arr::get($reqData, 'patient_id');
                            $noteData['misc1'] = 'CCR';
                            $noteData['misc2'] = $reqData['outcome'];
                            $noteData['misc4'] = $reqData['note'];
                            H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                        }else{
                            $notificationIds = array_merge($notificationIds,$userIds);
                            foreach($notificationIds as $user) {
                                $notificationData = [];
                                $notificationData['user_id'] = $user;
                                $notificationData['title'] = 'Third and final CCR attempt was made for '.$candidateName.' with '.$patientName;
                                $notificationData['type'] = 'trackerDetails';
                                $notificationData['related_item'] = Arr::get($reqData, 'tracker_id');
                                $notificationData['notes'] = '';
                                Notifications::addNotification($notificationData);
                            }

                            $noteData['entity'] = 'tracker-activity';
                            $noteData['entity_id'] = Arr::get($reqData, 'tracker_id');
                            $noteData['notes'] = 'Third and final CCR attempt was made for '.$candidateName.' with '.$patientName;
                            $noteData['provider_id'] = Arr::get($reqData, 'provider_id');
                            $noteData['candidate_id'] = Arr::get($reqData, 'candidate_id');
                            $noteData['patient_id'] = Arr::get($reqData, 'patient_id');
                            $noteData['misc1'] = 'CCR';
                            $noteData['misc2'] = $reqData['outcome'];
                            $noteData['misc4'] = $reqData['note'];

                            H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                        }
                    }else{
                        $notificationIds = array_merge($notificationIds,$userIds);
                        foreach($notificationIds as $user) {
                            $notificationData = [];
                            $notificationData['user_id'] = $user;
                            $notificationData['title'] = 'Third and final CCR attempt was made for '.$candidateName.' with '.$patientName;
                            $notificationData['type'] = 'trackerDetails';
                            $notificationData['related_item'] = Arr::get($reqData, 'tracker_id');
                            $notificationData['notes'] = '';
                            Notifications::addNotification($notificationData);
                        }

                        $noteData['entity'] = 'tracker-activity';
                        $noteData['entity_id'] = Arr::get($reqData, 'tracker_id');
                        $noteData['notes'] = 'Third and final CCR attempt was made for '.$candidateName.' with '.$patientName;
                        $noteData['provider_id'] = Arr::get($reqData, 'provider_id');
                        $noteData['candidate_id'] = Arr::get($reqData, 'candidate_id');
                        $noteData['patient_id'] = Arr::get($reqData, 'patient_id');
                        $noteData['misc1'] = 'CCR';
                        $noteData['misc2'] = $reqData['outcome'];
                        $noteData['misc4'] = $reqData['note'];

                        H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                    }

                } else {
                    $rsData['outcome'] = $reqData['outcome'];
                    $rsData['updated_at'] = date('Y-m-d H:i:s');
                    $rsData['updated_by'] = Auth::user()->id;
                    if(LD::checkFeatureFlag('coverage-tracker-view-action')){
                        if($isCoverageType){
                            $noteData['entity'] = 'client';
                            $noteData['type'] = 'coverage';
                            $noteData['entity_id'] = $reqData['patient_id'];
                            $noteData['provider_id'] = Arr::get($reqData, 'provider_id', '');
                            $noteData['patient_id'] = $reqData['patient_id'];
                            $noteData['notes'] = Auth::user()->name . ' attempted to contact candidate for CCR. ('
                            . $reqData['outcome'] . ') Comment:' . $reqData['note'];
                            $noteData['misc1'] = 'CCR';
                            $noteData['misc2'] = $reqData['outcome'];
                            $noteData['misc4'] = $reqData['note'];
                        }else{
                            $noteData['entity'] = 'tracker-activity';
                            $noteData['entity_id'] = $reqData['tracker_id'];
                            $noteData['provider_id'] = Arr::get($reqData, 'provider_id', '');
                            $noteData['candidate_id'] = $reqData['candidate_id'];
                            $noteData['patient_id'] = $reqData['patient_id'];
                            $noteData['notes'] = Auth::user()->name . '  attempted to contact candidate for CCR. (' . $reqData['outcome'] . ') Comment:' . $reqData['note'];
                            $noteData['misc1'] = 'CCR';
                            $noteData['misc2'] = $reqData['outcome'];
                            $noteData['misc4'] = $reqData['note'];
                        }
                        H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                    }else{
                        $noteData['entity'] = 'tracker-activity';
                        $noteData['entity_id'] = $reqData['tracker_id'];
                        $noteData['provider_id'] = Arr::get($reqData, 'provider_id', '');
                        $noteData['candidate_id'] = $reqData['candidate_id'];
                        $noteData['patient_id'] = $reqData['patient_id'];
                        $noteData['notes'] = Auth::user()->name . '  attempted to contact candidate for CCR. (' . $reqData['outcome'] . ') Comment:' . $reqData['note'];
                        $noteData['misc1'] = 'CCR';
                        $noteData['misc2'] = $reqData['outcome'];
                        $noteData['misc4'] = $reqData['note'];
                        H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                    }
                }


            } else if ($reqData['outcome'] == 'Candidate already established with client') {
                $rsData['outcome'] = "Completed";
                $rsData['updated_at'] = date('Y-m-d H:i:s');
                $rsData['updated_by'] = Auth::user()->id;
                if(LD::checkFeatureFlag('coverage-tracker-view-action')){
                    if($isCoverageType){
                        $noteData['entity'] = 'client';
                        $noteData['type'] = 'coverage';
                        $noteData['entity_id'] = $reqData['patient_id'];
                        $noteData['provider_id'] = Arr::get($reqData, 'provider_id', '');
                        $noteData['patient_id'] = $reqData['patient_id'];
                        $noteData['notes'] = "CCR marked complete because candidate
                        is already established with client.";
                        $noteData['misc1'] = 'CCR';
                        $noteData['misc2'] = $reqData['outcome'];
                        $noteData['misc4'] = $reqData['note'];
                        H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                    }else{
                        $upRecruiterTracker['Clinical_Competency_Review'] = date('Y-m-d H:i:s');
                        $noteData['entity'] = 'tracker-activity';
                        $noteData['entity_id'] = $reqData['tracker_id'];
                        $noteData['provider_id'] = Arr::get($reqData, 'provider_id', '');
                        $noteData['candidate_id'] = $reqData['candidate_id'];
                        $noteData['patient_id'] = $reqData['patient_id'];
                        $noteData['notes'] = "CCR marked complete because candidate is already established with client.";
                        $noteData['misc1'] = 'CCR';
                        $noteData['misc2'] = $reqData['outcome'];
                        $noteData['misc4'] = $reqData['note'];
                        H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                    }
                }else{
                    $upRecruiterTracker['Clinical_Competency_Review'] = date('Y-m-d H:i:s');
                    $noteData['entity'] = 'tracker-activity';
                    $noteData['entity_id'] = $reqData['tracker_id'];
                    $noteData['provider_id'] = Arr::get($reqData, 'provider_id', '');
                    $noteData['candidate_id'] = $reqData['candidate_id'];
                    $noteData['patient_id'] = $reqData['patient_id'];
                    $noteData['notes'] = "CCR marked complete because candidate is already established with client.";
                    $noteData['misc1'] = 'CCR';
                    $noteData['misc2'] = $reqData['outcome'];
                    $noteData['misc4'] = $reqData['note'];
                    H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                }
            } else if ($reqData['outcome'] == 'Completed') {

                $recruiterDetails = $recruitingSchedule?->tracker_id
                    ? MssqlRecruitertracker::find($recruitingSchedule->tracker_id)
                    : null;

                $rsData['outcome'] = "Completed";
                $rsData['updated_at'] = date('Y-m-d H:i:s');
                $rsData['updated_by'] = Auth::user()->id;

                if(LD::checkFeatureFlag('ccr-complete-add-clientname-communication-log')){
                    $patientName = '';
                    if(!empty($reqData['patient_id'])) {
                        $patient = MssqlPatients::select(DB::raw("CONCAT(Patient_FirstName,' ', Patient_LastName) as patient_name"))->find($reqData['patient_id']);
                        if(isset($patient->patient_name)) {
                            $patientName = $patient->patient_name;
                        }
                    }

                    if(LD::checkFeatureFlag('coverage-tracker-view-action')){
                        if($isCoverageType){
                            $candidateName = '';
                            if(!empty($reqData['provider_id'])) {
                                $provider = MssqlProviders::select(DB::raw("CONCAT(FirstName,' ', LastName) as provider_name"))->find($reqData['provider_id']);
                                if(isset($provider->provider_name)) {
                                    $candidateName = $provider->provider_name;
                                }
                            }
                            $text = Auth::user()->name . " completed the CCR with " . $candidateName . " for ".$patientName.".<br>";
                        }else{

                            $upRecruiterTracker['Clinical_Competency_Review'] = date('Y-m-d H:i:s');
                            $text = Auth::user()->name . " completed the CCR with " . ($recruiterDetails?->Candidate ?? '') . " for ".$patientName.".<br>";
                        }
                    }else{
                        $upRecruiterTracker['Clinical_Competency_Review'] = date('Y-m-d H:i:s');
                        $text = Auth::user()->name . " completed the CCR with " . ($recruiterDetails?->Candidate ?? '') . " for ".$patientName.".<br>";
                    }


                } else {
                    if(LD::checkFeatureFlag('coverage-tracker-view-action')){
                        if($isCoverageType){
                            $candidateName = '';
                            if(!empty($reqData['provider_id'])) {
                                $provider = MssqlProviders::select(DB::raw("CONCAT(FirstName,' ', LastName) as provider_name"))->find($reqData['provider_id']);
                                if(isset($provider->provider_name)) {
                                    $candidateName = $provider->provider_name;
                                }
                            }
                            $text = Auth::user()->name . " completed the CCR with " . $candidateName . ".<br>";
                        }else{
                            $upRecruiterTracker['Clinical_Competency_Review'] = date('Y-m-d H:i:s');
                            $text = Auth::user()->name . " completed the CCR with " . $recruiterDetails->Candidate . ".<br>";
                        }
                    }else{
                        $upRecruiterTracker['Clinical_Competency_Review'] = date('Y-m-d H:i:s');
                        $text = Auth::user()->name . " completed the CCR with " . $recruiterDetails->Candidate . ".<br>";
                    }

                }


                if (isset($reqData['ccrcheckbox'])) {
                    foreach ($reqData['ccrcheckbox'] as $key => $value) {
                        $text .= "[] " . H::getHelper("ConstantHelper")->getSNSCCRCheckbox($key) . '<br>';
                    }
                }

                if (isset($reqData['clinician_meeting']) && !empty($reqData['clinician_meeting'])) {
                    $text .= (LD::checkFeatureFlag('changes-to-ccr-form-for-pepr-process') ?
                        'Is clinician ready to start without additional training:' :
                        'Agree with clinician meeting client:') .
                        $reqData['clinician_meeting'] . '<br>';
                }

                if (!empty($reqData['reason'])) {
                    $text .= "Reason:" . $reqData['reason'] . '<br>';
                    $rsData['reason'] = $reqData['reason'];
                }


                if (!empty($reqData['ccr_donotuse_reason'])) {
                    $text .= "Reason for do not use:" . $reqData['reason'] . '<br>';
                    $rsData['ccr_donotuse_reason'] = $reqData['ccr_donotuse_reason'];
                }

                if (!empty($reqData['next_step'])) {
                    $text .= "Next Step:" . $reqData['next_step'] . '<br>';
                }
                if(!empty($reqData['ccr_training_type'])){
                    $text .= "Training Type:" . $reqData['ccr_training_type'] . '<br>';
                }
                if (!empty($reqData['find_another_client_text']) && $reqData['next_step']=='Find another client') {
                    $text .= "Next Step Note:" . $reqData['find_another_client_text'] . '<br>';
                    if(isset($reqData['ccr_completed_for_proposed_client'])) {
                        $text .= "CCR Completed For Proposed Client:Yes<br>";
                    }
                    $noteData['misc4'] = $reqData['find_another_client_text'];
                }
                if (!empty($reqData['reason_nurse_declined']) && $reqData['next_step']=='Nurse decided not to work for AOC') {
                    $text .= "Nurse Declined Reason:" . $reqData['reason_nurse_declined'] . '<br>';
                }

                if (!empty($reqData['copy_clipboard'])) {
                    $text .= "Comment:" . $reqData['copy_clipboard'] . '<br>';

                }

                //sending the notification and Automatically Move Record to Need to Review New Clients Based on CCR Outcome is Suggest Other Clients
                if(LD::checkFeatureFlag('ccr-auto-move-to-need-to-review-new-client')) {
                    if(isset($reqData['clinician_meeting']) && $reqData['clinician_meeting'] == 'No' && !empty($reqData['find_another_client_text']) && $reqData['next_step']=='Find another client'){
                        $clientObj = isset($reqData['patient_id']) ? MssqlPatients::find($reqData['patient_id']) : '';
                        $clientName = !empty($clientObj) ? $clientObj->Patient_FirstName.' '.$clientObj->Patient_LastName : '';
                        if(LD::checkFeatureFlag('treat-ccr-as-par-coverage-and-send-the-notification')){
                            if($isCoverageType){
                                $candidateName = '';
                                if(!empty($reqData['provider_id'])) {
                                    $provider = MssqlProviders::select(DB::raw("CONCAT(FirstName,' ', LastName) as provider_name"))->find($reqData['provider_id']);
                                    if(isset($provider->provider_name)) {
                                        $candidateName = $provider->provider_name;
                                    }
                                }
                                $title = "CCM determined ".$candidateName." is not a good fit for ".$clientName.". Candidate has been moved back to the Need to Review New Clients bucket. Please reach out ASAP to review new cases!";
                                $upRecruiterTracker['Candidate_Current_Status'] = 'Need to Review New Clients';
                                Notifications::addNotification(['user_id'=>$recruitingSchedule?->created_by,'title'=>$title,'type'=>'coverage','related_item'=>$recruitingSchedule?->coverage_id]);
                            }else{
                                $title = "CCM determined ".($recruiterDetails?->Candidate ?? '')." is not a good fit for ".$clientName.". Candidate has been moved back to the Need to Review New Clients bucket. Please reach out ASAP to review new cases!";
                                $upRecruiterTracker['Candidate_Current_Status'] = 'Need to Review New Clients';
                                Notifications::addNotification(['user_id'=>$recruiterDetails->user_id,'title'=>$title,'type'=>'trackerDetails','related_item'=>$recruiterDetails?->id]);
                                Notifications::addNotification(['user_id'=>H::getRecruiterAdmin($recruiterDetails?->user_id,true),'title'=>$title,'type'=>'trackerDetails','related_item'=>$recruiterDetails?->id]);
                            }
                        }else{
                            $title = "CCM determined ".($recruiterDetails?->Candidate ?? '')." is not a good fit for ".$clientName.". Candidate has been moved back to the Need to Review New Clients bucket. Please reach out ASAP to review new cases!";
                            $upRecruiterTracker['Candidate_Current_Status'] = 'Need to Review New Clients';
                            Notifications::addNotification(['user_id'=>$recruiterDetails?->user_id,'title'=>$title,'type'=>'trackerDetails','related_item'=>$recruiterDetails?->id]);
                            Notifications::addNotification(['user_id'=>H::getRecruiterAdmin($recruiterDetails?->user_id,true),'title'=>$title,'type'=>'trackerDetails','related_item'=>$recruiterDetails?->id]);
                        }

                    }
                }

                if(LD::checkFeatureFlag('coverage-tracker-view-action')){
                    if($isCoverageType){
                        $noteData['entity'] = 'client';
                        $noteData['type'] = 'coverage';
                        $noteData['entity_id'] = $reqData['patient_id'];
                        $noteData['provider_id'] = Arr::get($reqData, 'provider_id', '');
                        $noteData['patient_id'] = $reqData['patient_id'];
                        $noteData['notes'] = $text;
                        $noteData['misc1'] = 'CCR';
                        $noteData['misc2'] = 'Completed';
                        $noteData['misc3'] = json_encode(['clinician_meeting'=>$reqData['clinician_meeting']]);
                        $upRecruiterTracker = [];
                    }else{
                        $noteData['entity'] = 'tracker-activity';
                        $noteData['entity_id'] = $reqData['tracker_id'];
                        $noteData['provider_id'] = Arr::get($reqData, 'provider_id', '');
                        $noteData['candidate_id'] = $reqData['candidate_id'];
                        $noteData['patient_id'] = $reqData['patient_id'];
                        $noteData['notes'] = $text;
                        $noteData['misc1'] = 'CCR';
                        $noteData['misc2'] = 'Completed';
                        $noteData['misc3'] = json_encode(['clinician_meeting'=>$reqData['clinician_meeting']]);
                    }
                    H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                }else{
                    $noteData['entity'] = 'tracker-activity';
                    $noteData['entity_id'] = $reqData['tracker_id'];
                    $noteData['provider_id'] = Arr::get($reqData, 'provider_id', '');
                    $noteData['candidate_id'] = $reqData['candidate_id'];
                    $noteData['patient_id'] = $reqData['patient_id'];
                    $noteData['notes'] = $text;
                    $noteData['misc1'] = 'CCR';
                    $noteData['misc2'] = 'Completed';
                    $noteData['misc3'] = json_encode(['clinician_meeting'=>$reqData['clinician_meeting']]);
                    H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                }
            }  else if ($reqData['outcome'] === OutcomeStatusEnum::CANCELLED->value) {

                $rsData['outcome'] = OutcomeStatusEnum::CANCELLED->value;
                $rsData['updated_at'] = date('Y-m-d H:i:s');
                $rsData['updated_by'] = Auth::user()->id;

                $noteData['entity'] = 'tracker-activity';
                $noteData['entity_id'] = Arr::get($reqData, 'tracker_id', '');
                $noteData['provider_id'] = Arr::get($reqData, 'provider_id', '');
                $noteData['candidate_id'] = Arr::get($reqData, 'candidate_id', '');
                $noteData['patient_id'] = Arr::get($reqData, 'patient_id', '');
                $noteData['notes'] = Arr::get($reqData, 'note', '');
                $noteData['misc1'] = 'CCR';
                $noteData['misc2'] = OutcomeStatusEnum::CANCELLED->value;
                $noteData['misc3'] = json_encode(['clinician_meeting' => Arr::get($reqData, 'clinician_meeting', '')]);
                H::getHelper("ActivityNotes")->setActivityNotes($noteData);
            }

            RecruitingSchedule::where('id', $reqData['recruitingScheduleId'])->update($rsData);

            if(!empty($upRecruiterTracker)) {
                MssqlRecruitertracker::where('id', $reqData['tracker_id'])->update($upRecruiterTracker);
            }
            return response()->json(['success' => 1, 'message' => 'Infomation saved successfully']);
        } else {
            return response()->json(['success' => 0, 'message' => 'Something went wrong please try after some time.']);
        }
    }

    public function validateDate($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        return $d && $d->format($format) === $date;
    }
    public function getClinicalCase(Request $request)
    {
        $type = $request->input("type");
        if ($type == 'clinical_competency_reviews') {
            $data = H::getHelper("ClinicalCaseManager")->getClinicalCase($type, $request);
            $datatableObj = Datatables::of($data);
            if(LD::checkFeatureFlag('dashboard-past-due-count')) {
                $datatableObj->setRowAttr([
                    'class' => function ($row) {
                        /*$hourdiff = round((strtotime(date('Y-m-d H:i:s')) - strtotime($row->appointment_date)) / (60 * 60 * 24), 1);
                        $hourdiffStartDate = round((strtotime(date('Y-m-d H:i:s')) - strtotime($row->Candidate_Start_Date)) / (60 * 60 * 24), 1);
                        if (($hourdiff > 0 && $hourdiff < 24) || ($hourdiffStartDate > 0 && $hourdiffStartDate < 24)) {
                            return 'dashboard-older-record-cl-ra '.$hourdiffStartDate.'-'.$hourdiff;
                        } else {
                            return '';
                        }*/
                        return ((time() - strtotime($row->scheduled_date)) > 3600) ? "dashboard-older-record" : "";
                    },
                ])->with('pastduecount', function() use ($data) {
                    $data2 = $data;
                    $filtered_data = $data2->filter(function ($data2) {
                        return (time() - strtotime($data2->scheduled_date)) > 3600;
                    });
                    return $filtered_data->count();
                })->addColumn('Action', function ($row) {
                    $btn = '';
                    if(LD::checkFeatureFlag('coverage-tracker-view-action')){
                        if($row->shift_type == 'Coverage' && $row->coverage_id != ''){
                            $btn .= '<a href="javascript:void(0);" title = "Document Task" onclick="openCCRSNSForm(\'' . $row->id . '\',\'coverage\')"><i class="fa fa-calendar" aria-hidden="true"></i></a>';

                            $btn .= ' | <a href="javascript:void(0);" title = "View Tracker Details" onclick="getDetailsOfCoverageTracker(\'' . $row->coverage_id . '\')"><i class="fa fa-eye" aria-hidden="true"></i></a>';
                        }else{
                            $btn .= '<a href="javascript:void(0);" title = "Document Task" onclick="openCCRSNSForm(\'' . $row->id . '\')"><i class="fa fa-calendar" aria-hidden="true"></i></a>';
                            $btn .= ' | <a href="javascript:void(0);" title = "View Tracker Details" onclick="openRecruiterDetails(\'' . $row->tracker_id . '\')"><i class="fa fa-eye" aria-hidden="true"></i></a>';
                        }
                    }else{
                        $btn .= '<a href="javascript:void(0);" title = "Document Task" onclick="openCCRSNSForm(\'' . $row->id . '\')"><i class="fa fa-calendar" aria-hidden="true"></i></a>';

                        $btn .= ' | <a href="javascript:void(0);" title = "View Tracker Details" onclick="openRecruiterDetails(\'' . $row->tracker_id . '\')"><i class="fa fa-eye" aria-hidden="true"></i></a>';
                    }

                    if($row->ccr_acknowledged == 1){
                        $btn .= ' | <i class="fa fa-check-square-o" title = "Acknowledge" aria-hidden="true" style="color: green;"></i>';
                    }else{
                        $btn .= ' | <a href="javascript:void(0);" title = "Acknowledge" onclick="acknowledgeCcr(\'' . $row->tracker_id . '\')"><i class="fa fa-check-square-o" aria-hidden="true"></i></a>';
                    }
                    if(!empty($row->aoc_patient_id)){
                        $btn .= ' | <a href="javascript:void(0);"
                        title = "View client Profile"
                        onclick="getClientDetails(\'' . $row->aoc_patient_id . '\')">
                        <i class="fa fa-user" aria-hidden="true"></i></a>';
                    }
                    return $btn;
                })
                ->addColumn('select', function ($row) use($request){
                    if(LD::checkFeatureFlag('assign_ccm_to_coverage_tracker')){
                        if($row->shift_type == 'Coverage' && $row->coverage_id != ''){
                            $checkbox = '<input type="checkbox" name="checkbox-'.$request->input("type").'[]" class="form-input td select-all-' . str_replace(' ', '_', $request->input("type")) . '" value="coverage##' . $row->coverage_id . '" />';
                        } else {
                            $checkbox = '<input type="checkbox" name="checkbox-'.$request->input("type").'[]" class="form-input td select-all-' . str_replace(' ', '_', $request->input("type")) . '" value="recruiter##' . $row->tracker_id . '" />';
                        }
                    } else {
                        $checkbox = '<input type="checkbox" name="checkbox-'.$request->input("type").'[]" class="form-input td select-all-' . str_replace(' ', '_', $request->input("type")) . '" value="' . $row->tracker_id . '" />';
                    }
                    return $checkbox;
                })->editColumn('scheduled_date', function ($row) {
                    return [
                        'display' => e(H::getFormatedDate($row->scheduled_date) . ' ' . H::getFormatedTime($row->scheduled_date)),
                        'timestamp' => strtotime($row->scheduled_date)
                     ];
                })->editColumn('appointment_date', function ($row) {
                    return [
                        'display' => e(H::getFormatedDate($row->appointment_date)),
                        'timestamp' => strtotime($row->appointment_date)
                     ];
                });
            } else {
                $datatableObj->setRowAttr([
                    'class' => function ($row) {
                        $hourdiff = round((strtotime(date('Y-m-d H:i:s')) - strtotime($row->appointment_date)) / (60 * 60 * 24), 1);
                        if ($hourdiff > 1) {
                            return 'dashboard-older-record-cl-ra';
                        } else {
                            return '';
                        }
                    },
                ])->addColumn('Action', function ($row) {
                    $btn = '';
                    if(LD::checkFeatureFlag('coverage-tracker-view-action')){
                        if($row->shift_type == 'Coverage' && $row->coverage_id != ''){
                            $btn .= '<a href="javascript:void(0);" title = "Document Task" onclick="openCCRSNSForm(\'' . $row->id . '\',\'coverage\')"><i class="fa fa-calendar" aria-hidden="true"></i></a>';

                            $btn .= ' | <a href="javascript:void(0);" title = "View Tracker Details" onclick="getDetailsOfCoverageTracker(\'' . $row->coverage_id . '\')"><i class="fa fa-eye" aria-hidden="true"></i></a>';
                        }else{
                            $btn .= '<a href="javascript:void(0);" title = "Document Task" onclick="openCCRSNSForm(\'' . $row->id . '\')"><i class="fa fa-calendar" aria-hidden="true"></i></a>';
                            $btn .= ' | <a href="javascript:void(0);" title = "View Tracker Details" onclick="openRecruiterDetails(\'' . $row->tracker_id . '\')"><i class="fa fa-eye" aria-hidden="true"></i></a>';
                        }
                    }else{
                        $btn .= '<a href="javascript:void(0);" title = "Document Task" onclick="openCCRSNSForm(\'' . $row->id . '\')"><i class="fa fa-calendar" aria-hidden="true"></i></a>';
                        $btn .= ' | <a href="javascript:void(0);" title = "View Tracker Details" onclick="openRecruiterDetails(\'' . $row->tracker_id . '\')"><i class="fa fa-eye" aria-hidden="true"></i></a>';
                    }
                    if($row->ccr_acknowledged == 1){
                        $btn .= ' | <i class="fa fa-check-square-o" title = "Acknowledge" aria-hidden="true" style="color: green;"></i>';
                    }else{
                        $btn .= ' | <a href="javascript:void(0);"  title = "Acknowledge" onclick="acknowledgeCcr(\'' . $row->tracker_id . '\')"><i class="fa fa-check-square-o" aria-hidden="true"></i></a>';
                    }
                    if(!empty($row->aoc_patient_id)){
                        $btn .= ' | <a href="javascript:void(0);"
                        title = "View client Profile"
                        onclick="getClientDetails(\'' . $row->aoc_patient_id . '\')">
                        <i class="fa fa-user" aria-hidden="true"></i></a>';
                    }
                    return $btn;
                })
                ->addColumn('select', function ($row) use($request){
                    if(LD::checkFeatureFlag('assign_ccm_to_coverage_tracker')){
                        if($row->shift_type == 'Coverage' && $row->coverage_id != ''){
                            $checkbox = '<input type="checkbox" name="checkbox-'.$request->input("type").'[]" class="form-input td select-all-' . str_replace(' ', '_', $request->input("type")) . '" value="coverage##' . $row->coverage_id . '" />';
                        } else {
                            $checkbox = '<input type="checkbox" name="checkbox-'.$request->input("type").'[]" class="form-input td select-all-' . str_replace(' ', '_', $request->input("type")) . '" value="recruiter##' . $row->tracker_id . '" />';
                        }
                    } else {
                        $checkbox = '<input type="checkbox" name="checkbox-'.$request->input("type").'[]" class="form-input td select-all-' . str_replace(' ', '_', $request->input("type")) . '" value="' . $row->tracker_id . '" />';
                    }
                    return $checkbox;
                })->editColumn('scheduled_date', function ($row) {
                    return [
                        'display' => e(H::getFormatedDate($row->scheduled_date) . ' ' . H::getFormatedTime($row->scheduled_date)),
                        'timestamp' => strtotime($row->scheduled_date)
                     ];
                })->editColumn('appointment_date', function ($row) {
                    return [
                        'display' => e(H::getFormatedDate($row->appointment_date)),
                        'timestamp' => strtotime($row->appointment_date)
                     ];
                });
            }
            $datatableObj->editColumn('proposed_next_step_date', function ($row) {
                    if (in_array($row->Candidate_Current_Status, ['PEPI - Scheduled', 'WEPI - Scheduled', 'Training Shift Scheduled', 'Client Match'])) {
                        if($row->shift_type == 'Training'){
                            return H::getFormatedLongDateTime($row->proposed_next_step_date_raw). "<i class='fa fa-lock'></i>";
                        }
                        return $row->proposed_next_step_date . "<i class='fa fa-lock'></i>";
                    } else {
                        if($row->shift_type == 'Training'){
                            return H::getFormatedLongDateTime($row->proposed_next_step_date_raw);
                        }
                        return $row->proposed_next_step_date;
                    }
            });
            return $datatableObj->escapeColumns('Action')->escapeColumns('select')->make(true);
        } elseif ($type == 'clinical_exception_bucket'){
            $data = H::getHelper("ClinicalCaseManager")->getClinicalExceptionBucket($type, $request);
            $datatableObj = Datatables::of($data);
            $datatableObj->addColumn('Action', function ($row) {
                $btn = '';
                $btn .= ' <a href="javascript:void(0);" onclick="getClientDetails(\'' . $row->id . '\')"><i class="fa fa-user" aria-hidden="true"></i></a>';
                return $btn;
            })->editColumn('created_at', function ($row) {
                return [
                    'display' => e(H::getFormatedDate($row->created_at)),
                    'timestamp' => strtotime($row->created_at)
                 ];
            });
            return $datatableObj->escapeColumns('Action')->make(true);
        } elseif ($type == 'ccr_today_ipea') {
            $data = H::getHelper("ClinicalCaseManager")->getClinicalTodayIpea($type, $request);
            $datatableObj = Datatables::of($data);
            $datatableObj->addColumn('appointment_date', function ($row) {
                return [
                    'display' => e(H::getFormatedDate($row->appointment_date)),
                    'timestamp' => strtotime($row->appointment_date)
                 ];
            })
            ->addColumn('appointment_time', function ($row) {
                return H::getFormatedTime($row['appointment_date'].' '.$row['appointment_time']);
            })
            ->addColumn('Action', function ($row) {
                $btn = '';
                if (isset($row['id']) && $row['id'] != '' && $row['id'] != null) {
                    $btn .= '<a href="javascript:void(0)" onclick="updateNurseIpeaInfo(' . trim($row['id']) . ');" ><i class="fa fa-vcard" aria-hidden="true"></i></a>';
                }
                if (isset($row['aoc_patient_id']) && $row['aoc_patient_id'] != '' && $row['aoc_patient_id'] != null) {
                    $btn .= '<a href="javascript:void(0)" onclick="getClientDetails(' . trim($row['aoc_patient_id']) . ');" ><i class="fa fa fa-eye" aria-hidden="true"></i></a>';
                }
                return $btn;
            });
            return $datatableObj->escapeColumns('Action')->escapeColumns('select')->make(true);
        } elseif ($type == 'ccm_scheduled_visits') {
            $data = H::getHelper("ClinicalCaseManager")->getClinicalScheduledVisits($request);
            $datatableObj = Datatables::of($data);
            $datatableObj->setRowAttr([
                'class' => function ($row) {
                    $hourdiff = round((strtotime(date('Y-m-d H:i:s')) - strtotime($row->ScheduleDate)) / (60 * 60 * 24), 1);
                    if ($hourdiff > 1) {
                        return 'dashboard-older-record-cl-ra';
                    } else {
                        return '';
                    }
                },
            ])
            ->addColumn('Action', function ($row) {
                $btn = '';
                if (isset($row->aoc_patient_id) && $row->aoc_patient_id != '' && $row->aoc_patient_id != null) {
                    $btn .= '<a href="javascript:void(0)" onclick="getClientDetails(' . trim($row->aoc_patient_id) . ');" ><i class="fa fa-user" aria-hidden="true"></i></a>';
                }
                return $btn;
            });
            return $datatableObj->escapeColumns('Action')->make(true);
        }
    }
    public function reloadCountClinicalCase(Request $request)
    {
        $type = $request->input("type");
        if ($type == 'clinical_competency_reviews') {
            return json_encode(["counter" => H::getHelper('ClinicalCaseManager')->getClinicalCase($request->input("type"), $request, 1), "type" => $request->input("type")]);
        } elseif ($type == 'clinical_exception_bucket'){
            return json_encode(["counter" => H::getHelper('ClinicalCaseManager')->getClinicalExceptionBucket($request->input("type"), $request, 1), "type" => $request->input("type")]);
        } elseif ($type == 'ccr_today_ipea') {
            return json_encode(["counter" => H::getHelper('ClinicalCaseManager')->getClinicalTodayIpea($request->input("type"), $request, 1), "type" => $request->input("type")]);
        }elseif ($type == 'ccm_scheduled_visits') {
            return json_encode(["counter" => H::getHelper('ClinicalCaseManager')->getClinicalScheduledVisits($request, 1), "type" => $request->input("type")]);
        }

    }

    public function assignedCcr(Request $request)
    {
        $reqData = $request->all();
        $noteData = [];
        if (!empty($reqData['ccr_id']) && !empty($reqData['ids'])) {
            $selectedIds = explode(",",$reqData['ids']);
            if(LD::checkFeatureFlag('assign_ccm_to_coverage_tracker')){
                foreach($selectedIds as $idValue) {
                    try{
                        $idDetailArr = explode("##",$idValue);
                        H::ExcelLog(print_r($idDetailArr, true),'assignedCcr.log');
                        $shiftType = $idDetailArr[0];
                        $id = $idDetailArr[1];
                        if($shiftType == 'recruiter') {
                            H::ExcelLog("Type recruiter for ". $id,'assignedCcr.log');
                            MssqlRecruitertracker::where('id', $id)->update(['assigned_ccr' => $reqData['ccr_id']]);
                            $noteData['entity'] = 'tracker';
                            $noteData['entity_id'] = $id;
                            $noteData['notes'] = 'The Clinical Competency Review was reassigned to ' . User::find($reqData['ccr_id'])->name;

                            H::getHelper("ActivityNotes")->setActivityNotes($noteData);

                            $bucket = DB::table('recruiting_schedule')->select('recruiting_schedule.scheduled_date',"recruiting_schedule.tracker_id",'recruiting_schedule.id','mssql_patients.PatientAccount',DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"));
                            $bucket->leftJoin("mssql_patients",function($query){
                                $query->on("mssql_patients.id","=","recruiting_schedule.aoc_patient_id");
                            });
                            $bucket->where("recruiting_schedule.tracker_id",'=', $id);
                            $bucket->where("recruiting_schedule.type_id",'=', 2);
                            $recruitingScheduleData = $bucket->first();
                            $title = 'CCR for '.$recruitingScheduleData->patient_name.' has been assigned to you.';
                            Notifications::addNotification(['user_id'=> User::find($reqData['ccr_id'])->id,'title'=>$title,'type'=>'trackerDetails','related_item'=>$id,'notes'=>$title]);
                        } elseif($shiftType == 'coverage') {
                            H::ExcelLog("Type coverage for ". $id,'assignedCcr.log');
                            DB::table('recruiting_schedule')->where('coverage_id', $id)->update(['ccm_id' => $reqData['ccr_id']]);
                            $noteData['entity'] = 'tracker';
                            $noteData['entity_id'] = $id;
                            $noteData['notes'] = 'The Clinical Competency Review was reassigned to ' . User::find($reqData['ccr_id'])->name;

                            H::getHelper("ActivityNotes")->setActivityNotes($noteData);

                            $bucket = DB::table('recruiting_schedule')->select('recruiting_schedule.scheduled_date',"recruiting_schedule.coverage_id",'recruiting_schedule.id','mssql_patients.PatientAccount',DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"));
                            $bucket->leftJoin("mssql_patients",function($query){
                                $query->on("mssql_patients.id","=","recruiting_schedule.aoc_patient_id");
                            });
                            $bucket->where("recruiting_schedule.coverage_id",'=', $id);
                            $bucket->where("recruiting_schedule.type_id",'=', 2);
                            $recruitingScheduleData = $bucket->first();
                            $title = 'CCR for '.$recruitingScheduleData->patient_name.' has been assigned to you.';
                            Notifications::addNotification(['user_id'=> User::find($reqData['ccr_id'])->id,'title'=>$title,'type'=>'coverageDetails','related_item'=>$id,'notes'=>$title]);
                        }

                    }catch(Exception $e){
                        H::ExcelLog("Error for ". $idValue. ' is '. $e->getMessage(),'assignedCcr.log');
                    }
                }
            } else {
                MssqlRecruitertracker::whereIn('id', $selectedIds)->update(['assigned_ccr' => $reqData['ccr_id']]);
                foreach($selectedIds as $id) {
                    $noteData['entity'] = 'tracker';
                    $noteData['entity_id'] = $id;
                    $noteData['notes'] = 'The Clinical Competency Review was reassigned to ' . User::find($reqData['ccr_id'])->name;
                    H::getHelper("ActivityNotes")->setActivityNotes($noteData);

                    $bucket = DB::table('recruiting_schedule')->select('recruiting_schedule.scheduled_date',"recruiting_schedule.tracker_id",'recruiting_schedule.id','mssql_patients.PatientAccount',DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"));
                    $bucket->leftJoin("mssql_patients",function($query){
                        $query->on("mssql_patients.id","=","recruiting_schedule.aoc_patient_id");
                    });
                    $bucket->where("recruiting_schedule.tracker_id",'=', $id);
                    $bucket->where("recruiting_schedule.type_id",'=', 2);
                    $recruitingScheduleData = $bucket->first();
                    $title = 'CCR for '.$recruitingScheduleData->patient_name.' has been assigned to you.';
                    Notifications::addNotification(['user_id'=> User::find($reqData['ccr_id'])->id,'title'=>$title,'type'=>'trackerDetails','related_item'=>$id,'notes'=>$title]);
                }
            }

            return response()->json(['success' => 1, 'message' => 'Infomation saved successfully']);
        } else {
            return response()->json(['success' => 0, 'message' => 'Something went wrong please try after some time.']);
        }
    }

    public function assignedCcm(Request $request)
    {
        $reqData = $request->all();

        $noteData = [];
        if (!empty($reqData['ccr_id']) && !empty($reqData['ids'])) {
            $selectedIds = explode(",",$reqData['ids']);
            MssqlRecruitertracker::whereIn('id', $selectedIds)->update(['assigned_candidatestart' => $reqData['ccr_id']]);
            foreach($selectedIds as $id) {
                $noteData['entity'] = 'tracker';
                $noteData['entity_id'] = $id;
                $noteData['notes'] = 'The Candidate Start was reassigned to ' . User::find($reqData['ccr_id'])->name;

                H::getHelper("ActivityNotes")->setActivityNotes($noteData);

                $bucket = DB::table('recruiting_schedule')->select('recruiting_schedule.scheduled_date',"recruiting_schedule.tracker_id",'recruiting_schedule.id','mssql_patients.PatientAccount',DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"));
                $bucket->leftJoin("mssql_patients",function($query){
                    $query->on("mssql_patients.id","=","recruiting_schedule.aoc_patient_id");
                });
                $bucket->where("recruiting_schedule.tracker_id",'=', $id);
                $bucket->where("recruiting_schedule.type_id",'=', 6);
                $recruitingScheduleData = $bucket->first();
                $title = 'Candidate Start for '.$recruitingScheduleData->patient_name.' has been assigned to you.';
                Notifications::addNotification(['user_id'=> User::find($reqData['ccr_id'])->id,'title'=>$title,'type'=>'trackerDetails','related_item'=>$id,'notes'=>$title]);
            }

            return response()->json(['success' => 1, 'message' => 'Infomation saved successfully']);
        } else {
            return response()->json(['success' => 0, 'message' => 'Something went wrong please try after some time.']);
        }
    }

    public function acknowledgeCcr(Request $request) {
        try{
            $id = $request->id;
            $noteData = [];
            $noteData['entity'] = 'tracker-activity';
            $noteData['entity_id'] = $id;
            $noteData['notes'] = "CCR acknowledged by " . Auth::user()->name;
            H::getHelper("ActivityNotes")->setActivityNotes($noteData);
            MssqlRecruitertracker::where('id', $id)->update(['ccr_acknowledged' => '1']);
            return response()->json(['success' => 1]);
        }catch(Exception $e){
            return response()->json(['success' => 0, 'message' => 'Something went wrong']);
        }
    }

    public function getTherapistApplications()
    {
        $dataTable = H::getHelper('Datatable')->getConfiguration('candidates');

        return view('candidates.grid', ['dataTable' => $dataTable]);
    }
}
