<?php

namespace App\Http\Controllers;

use App\Candidates;
use App\Enums\NotificationTypeEnum;
use App\Helpers\CrossStateHelper;
use App\Repositories\ClassCategory\ClassCategoryReadRepository;
use App\DTO\NotesDTO;
use App\Enums\MssqlRecreturTrackerStatus;
use App\Enums\MssqlRecruiterTrackerBonusStatus;
use App\Enums\RolesEnum;
use App\Events\InactivateNRNCTrackers;
use App\Enums\PendingOutcomeReason;
use App\Enums\RecruitingBucket;
use App\Enums\JobViteRecruiterJobType;
use App\Enums\MssqlPatientStatus;
use App\Facades\CacheKeysHelper;
use App\FirstAdvantageCheck;
use App\FirstAdvantageErrors;
use App\Helpers\ConstantHelper;
use App\Helpers\H;
use App\Helpers\LD;
use App\Http\Requests\CandidateApplications\GetNurseApplicationsRequest;
use App\Jobs\test;
use App\MssqlFacilities;
use App\MssqlPatients;
use App\MssqlProviders;
use App\MssqlRecruitertracker;
use App\Notes;
use App\Helpers\Notifications;
use App\RecruitingSchedule;
use App\Repositories\App\CandidateApplications\CandidateApplicationsReadRepository;
use App\Repositories\Notes\NotesWriteRepository;
use App\Rules\IsValidPassword;
use App\Enums\ScheduleTypes;
use App\Enums\RecruitingScheduleEnum;
use App\Enums\ScheduleCandidateType;
use App\Schedule;
use App\ScheduleType;
use App\Enums\ScheduleNextStepOutcomesEnum;
use App\Services\BusinessDaysService;
use App\Services\Dashboard\Buckets\Jobvite\CandidateApplicationsService;
use App\Services\Dashboard\Buckets\SocialMedia\SocialMediaRequestOverdueService;
use App\Services\FirstAdvantage\FirstAdvantageService;
use App\Services\FirstAdvantage\FirstAdvantageValidation;
use App\Services\LogSq;
use App\Transformer\Dashboard\Buckets\Jobvite\NursesApplicationsTransformer;
use DataTables;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\NeedToHireAccounts;
use App\TrachVentClassRequest;
use App\BranchLocations;
use App\Helpers\Geocoding;
use App\CustomFields;
use App\NeedToHireChecklist;
use App\Repositories\App\JobCategories\JobCategoriesReadRepository;
use App\DTO\MssqlRecruiterTracker\TrackerHoursEligibilityResultDTO;
use App\Services\MssqlRecruiterTracker\TrackerHoursEligibilityService;
use Illuminate\Http\Response;
use Exception;

class Dashboard extends Controller
{
    private const SET_MISSED_VISITS_RETRIES = 5;
    private const IPEA_STATUS = 'Clinician Cancelled';
    private const IPEA_CANCELLED = 'Cancelled';
    private const EMAIL_DUPLICATE_SEARCH_LIMIT_TESTING = 1000;

    public function __construct(
        private FirstAdvantageValidation $fistAdvantageCheckValidation,
        private ClassCategoryReadRepository $classCategoryReadRepository,
    ) {}

    public function index()
    {
        return view('account.dashboard');
    }

    public function getCCCProgressBar(Request $request) {
            if($request->input("bucketType") == 'weekly_nth_calls') {
                return response()->json(H::getHelper("ClientCareCoordinatorException")
                ->getCCCWeeklyProgressBar($request->all()));
            } elseif($request->input("bucketType") == 'monthly_calls') {
                return response()->json(H::getHelper("ClientCareCoordinatorException")
                ->getCCCMonthlyProgressBar($request->all()));
            } else {
                return response()->json(H::getHelper("ConstantHelper")->getProgressBar($request->all()));
            }
    }

    public function getMissedVisitsCheckForm(Request $request) {
        $requestData = $request->all();
        return view('ccc.missedvisitconfirmation',['id'=>$requestData['id']]);
    }

    public function getMissedVisitsCheckForms(Request $request){
        $requestData = $request->all();
        $type = $requestData['type'];
        return view('ccc.missedvisitconfirmation',['ids'=>$requestData['ids'], 'type' => $type]);
    }

    public function setMissedVisits(Request $request) {

        $attempt = 0;
        $req  = $request->all();
        $ids = explode(",",$req['id']);
        while ($attempt < self::SET_MISSED_VISITS_RETRIES) {
            try {
                $attempt++;
                DB::table("mssql_schedulemaster")->whereIn("id",$ids)->update(['checked_off_by'=>Auth::user()->id,'checked_off_at'=>date('Y-m-d H:i:s')]);
            } catch (\Exception $e) {
                sleep($attempt * 2);
            }
        }
    }

    public function saveMonthlyInputCall(Request $request) {


        $reqData = $request->all();

        $weeklyFollowupText = '';
        try {
            $patientName = '';
            $bucket = DB::table("mssql_patients")->select("mssql_patients.*")->where('id',$request->aoc_patient_id)->first();
            $patientName = '';
            if(isset($bucket->Patient_FirstName)) {
                $patientName = $bucket->Patient_FirstName;
            }
            if(isset($bucket->Patient_LastName)) {
                $patientName.=' '.$bucket->Patient_LastName;
            }

            if($reqData['outcome']=='Attempt 1' || $reqData['outcome']=='Attempt 2' || $reqData['outcome']=='Attempt 3') {

                $patientCount = DB::table("mssql_patients")->select("*")->where('id',"=",$request->aoc_patient_id)->first();
                $patientCount->attempt_count = $patientCount->attempt_count + 1;
                $result = '';
                if(is_array($reqData['result'])) {
                    $result = implode(",",$reqData['result']);
                } else {
                    $result = $reqData['result'];
                }
                DB::table("mssql_patients")->where('id',"=",$request->aoc_patient_id)
                ->update(
                    [
                        "attempt_count"=>$patientCount->attempt_count,
                        "outcome"=>$reqData['outcome'],
                        "result"=>$result,
                        'monthly_follow_up_attempts'=>date('Y-m-d H:i:s')
                    ]);
                $weeklyFollowupText = Auth::user()->name.' attempted to contact the client for a Monthly call.  ('.$reqData['outcome'].') <br> Comment :'.$reqData['contact_comment'];


            } else if($reqData['outcome']=='Requested Call back') {
                DB::table("mssql_patients")->where('id',"=",$request->aoc_patient_id)
                ->update(
                    [
                        "requested_callback"=>$reqData['requested_callback'],
                        "outcome"=>$reqData['outcome'],
                        'monthly_follow_up_attempts'=>date('Y-m-d H:i:s')
                    ]);

                $weeklyFollowupText = Auth::user()->name.' Requested call back to the client for a Monthly call.  <br>  Requested a callback :'.$reqData['requested_callback'];

            } else if($reqData['outcome']=='Document Call'){

                $weeklyFollowupText='Monthly follow up call for '.$patientName.'<br>';

                if(isset($reqData['spokewith']) && !empty($reqData['spokewith'])) {
                    $weeklyFollowupText.='<p>Spoke With:'.$reqData['spokewith'].'</p>';
                }
                if(isset($reqData['textorphone']) && !empty($reqData['textorphone'])) {
                    $weeklyFollowupText.='<p>Text or Phone:'.$reqData['textorphone'].'</p>';
                }

                if(isset($reqData['doctorverified'])) {
                    if($reqData['doctorverified']=='1') {
                        $weeklyFollowupText.='<p>Doctor verified:Yes</p>';
                    } else {
                        $weeklyFollowupText.='<p>Doctor verified:No</p>';
                    }
                }

                if(isset($reqData['verifiedinsurance'])) {
                    if($reqData['verifiedinsurance']=='1') {
                        $weeklyFollowupText.='<p>Verified insurance:Yes</p>';
                    } else {
                        $weeklyFollowupText.='<p>Verified insurance:No</p>';
                    }
                }

                if(isset($reqData['verifiedpatient'])) {
                    if($reqData['verifiedpatient']=='1') {
                        $weeklyFollowupText.='<p>Verified patient data/classification:Yes</p>';
                    } else {
                        $weeklyFollowupText.='<p>Verified patient data/classification:No</p>';
                    }
                }



                if(isset($reqData['upcomingvacations'])) {
                    if($reqData['upcomingvacations']=='1') {
                        $weeklyFollowupText.='<p>Upcoming vacations:Yes</p>';
                        if(!empty($reqData['whereareyougoing'])) {
                            $weeklyFollowupText.='<p>Where are you going:'.$reqData['whereareyougoing'].'</p>';
                        }
                        if(!empty($reqData['fromdate'])) {
                            $weeklyFollowupText.='<p>From Date:'.$reqData['fromdate'].'</p>';
                        }

                        if(!empty($reqData['todate'])) {
                            $weeklyFollowupText.='<p>To Date:'.$reqData['todate'].'</p>';
                        }

                        if(isset($reqData['needtotakenurse'])) {
                            if($reqData['needtotakenurse']=='1') {
                                $weeklyFollowupText.='<p>Need to take nurse:Yes</p>';
                            } else {
                                $weeklyFollowupText.='<p>Need to take nurse:No</p>';
                            }
                        }
                    } else {
                        $weeklyFollowupText.='<p>Upcoming vacations:No</p>';
                    }

                }

                if(isset($reqData['workschedulechanged'])) {
                    if($reqData['workschedulechanged']=='1') {
                        $weeklyFollowupText.='<p>Work schedule changed (respite):Yes</p>';

                        if(!empty($reqData['workschedulefreetext'])) {
                            $weeklyFollowupText.='<p>'.$reqData['workschedulefreetext'].'</p>';
                        }

                    } else {
                        $weeklyFollowupText.='<p>Work schedule changed (respite):No</p>';
                    }
                }


                if(isset($reqData['verifiedprefrences'])) {
                    if($reqData['verifiedprefrences']=='1') {
                        $weeklyFollowupText.='<p>Verified Preferences:Yes</p>';
                    } else {
                        $weeklyFollowupText.='<p>Verified Preferences:No</p>';
                    }
                }

                if(isset($reqData['verifiedscheduleandauth'])) {
                    if($reqData['verifiedscheduleandauth']=='1') {
                        $weeklyFollowupText.='<p>Verified schedule and auth:Yes</p>';
                    } else {
                        $weeklyFollowupText.='<p>Verified schedule and auth:No</p>';
                    }
                }

                if(isset($reqData['discussednecessaryjournal'])) {
                    if($reqData['discussednecessaryjournal']=='1') {
                        $weeklyFollowupText.='<p>Discussed necessary journal notes:Yes</p>';
                    } else {
                        $weeklyFollowupText.='<p>Discussed necessary journal notes:No</p>';
                    }
                }

                if(isset($reqData['schoolsetup'])) {
                    if($reqData['schoolsetup']=='1') {
                        $weeklyFollowupText.='<p>School setup:Yes</p>';
                    } else {
                        $weeklyFollowupText.='<p>School setup:No</p>';
                    }
                }


                if(isset($reqData['askedaboutrelationshipwithnurse'])) {
                    if($reqData['askedaboutrelationshipwithnurse']=='1') {
                        $weeklyFollowupText.='<p>Asked about relationship with in-home nurses:Yes</p>';
                    } else {
                        $weeklyFollowupText.='<p>Asked about relationship with in-home nurses:No</p>';
                    }
                }


                if(isset($reqData['notesaboutrelationship']) && !empty($reqData['notesaboutrelationship'])) {
                    $weeklyFollowupText.='<p>Notes about relationship with in-home nurses:'.$reqData['notesaboutrelationship'].'</p>';
                }

                if (isset($reqData['rate']) && !empty($reqData['rate']) && ($reqData['rate'] <= 6)) {
                    $weeklyFollowupText .= '<p>On a scale of 1 to 10, how would you rate your experience at Angels of Care?:' . $reqData['rate'].'</p>';

                    $dbo_ids = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole($bucket->aoc_facility_id, 'DBO');
                    if (count($dbo_ids) > 0) {
                        foreach($dbo_ids as $dbo_id) {
                            $data = [];
                            $data['user_id'] = $dbo_id;
                            $data['title'] = 'Monthly Call was completed for '.$patientName.' by ' . Auth::user()->name . 'and the satisfaction rating was documented as '.$reqData['rate'].'.';
                            $data['type'] = 'client';
                            $data['related_item'] = $reqData['aoc_patient_id'];
                            $data['notes'] = '';
                            Notifications::addNotification($data);
                        }
                    }

                    $rdo_ids = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole($bucket->aoc_facility_id, 'Regional Director of Operations');
                    if (count($rdo_ids) > 0) {
                        foreach($rdo_ids as $rdo_id) {
                            $data = [];
                            $data['user_id'] = $rdo_id;
                            $data['title'] = 'Monthly Call was completed for '.$patientName.' by ' . Auth::user()->name . ' and the satisfaction rating was documented as '.$reqData['rate'].'.';
                            $data['type'] = 'client';
                            $data['related_item'] = $reqData['aoc_patient_id'];
                            $data['notes'] = '';
                            Notifications::addNotification($data);
                        }
                    }

                    if (!empty($bucket->ccc_id)) {
                        $data = [];
                        $data['user_id'] = $bucket->ccc_id;
                        $data['title'] = 'Monthly Call was completed for '.$patientName.' by ' . Auth::user()->name . ' and the satisfaction rating was documented as '.$reqData['rate'].'.';
                        $data['type'] = 'client';
                        $data['related_item'] = $reqData['aoc_patient_id'];
                        $data['notes'] = '';
                        Notifications::addNotification($data);
                    }

                    $ccc_mgr_ids = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole($bucket->aoc_facility_id, 'Client Care Manager');
                    if (count($ccc_mgr_ids) > 0) {
                        foreach($ccc_mgr_ids as $ccc_mgr_id) {
                            $data = [];
                            $data['user_id'] = $ccc_mgr_id;
                            $data['title'] = 'Monthly Call was completed for '.$patientName.' by ' . Auth::user()->name . ' and the satisfaction rating was documented as '.$reqData['rate'].'.';
                            $data['type'] = 'client';
                            $data['related_item'] = $reqData['aoc_patient_id'];
                            $data['notes'] = '';
                            Notifications::addNotification($data);
                        }
                    }

                    if ($reqData['rate'] < 3) {
                        $svp_ids = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole($bucket->aoc_facility_id, 'State VP');
                        if (count($svp_ids) > 0) {
                            foreach($svp_ids as $svp_id) {
                                $data = [];
                                $data['user_id'] = $svp_id;
                                $data['title'] = 'Monthly Call was completed for '.$patientName.' by ' . Auth::user()->name . ' and the satisfaction rating was documented as '.$reqData['rate'].'.';
                                $data['type'] = 'client';
                                $data['related_item'] = $reqData['aoc_patient_id'];
                                $data['notes'] = '';
                                Notifications::addNotification($data);
                            }
                        }
                    }
                }

                if(isset($reqData['recapofcall']) && !empty($reqData['recapofcall'])) {
                    $weeklyFollowupText.='<p>Recap of call:'.$reqData['recapofcall'].'</p>';
                }

                DB::table("mssql_patients")->where('id',"=",$request->aoc_patient_id)
                ->update(
                    [
                        "outcome"=>$reqData['outcome'],
                        'last_monthly_followup'=>date('Y-m-d H:i:s')
                    ]);
            }

            $noteData = [];
            $noteData['entity'] = 'client';
            $noteData['entity_id'] = $reqData['aoc_patient_id'];
            $noteData['notes'] =$weeklyFollowupText;

            H::getHelper("ActivityNotes")->setActivityNotes($noteData);

            return response()->json(['status'=>1]);


        } catch(\Exception $e) {
            H::ExcelLog("Error : " . $e->getMessage(),'saveMonthlyInputCall.log');
        }


    }
    private function saveWeeklyMonthlyInputCall(Request $request) {

        try {

            $reqData = $request->all();

            $followupText='';
            $mediaRefusedDate = null;

            $bucket = DB::table("ccc_followup_calls")
            ->select("mssql_patients.*","ccc_followup_calls.attempt_count")
            ->leftJoin("mssql_patients","mssql_patients.id","=","ccc_followup_calls.aoc_patient_id")
            ->where("ccc_followup_calls.id",$request->followupId)->first();

            $patientName = '';

            if(isset($bucket->Patient_FirstName)) {
                $patientName = $bucket->Patient_FirstName;
            }

            if(isset($bucket->Patient_LastName)) {
                $patientName.=' '.$bucket->Patient_LastName;
            }

            $isAttemptOutcome = $reqData['outcome']=='Attempt 1' || $reqData['outcome']=='Attempt 2'
                || ($reqData['followupType']!='weekly' && $reqData['outcome']=='Attempt 3');
            if($isAttemptOutcome) {
                $bucket->attempt_count = $bucket->attempt_count + 1;
                $result = '';
                if(is_array($reqData['result'])) {
                    $result = implode(",",$reqData['result']);
                } else {
                    $result = $reqData['result'];
                }
                DB::table("ccc_followup_calls")->where('ccc_followup_calls.id',"=",$request->followupId)
                ->update(
                    [
                        "attempt_count"=>$bucket->attempt_count,
                        "outcome"=>$reqData['outcome'],
                        "result"=> $result,
                        'weekly_follow_up_attempts'=>date('Y-m-d H:i:s')
                    ]);
                $followupText = Auth::user()->name.' attempted to contact the client for a Weekly call.  ('.$reqData['outcome'].') <br> Comment :'.$reqData['contact_comment'];
                //'Weekly follow up call for '.$patientName. ' ('.$reqData['outcome'].')';

            } else if($reqData['outcome']=='Requested Call back'){

                DB::table("ccc_followup_calls")->where('ccc_followup_calls.id',"=",$request->followupId)
                ->update(
                    [
                        "requested_callback"=>$reqData['requested_callback'],
                        "outcome"=>$reqData['outcome'],
                        'weekly_follow_up_attempts'=>date('Y-m-d H:i:s')
                    ]);


                $followupText = Auth::user()->name.' Requested call back to the client for a Weekly call.   <br>  Requested a callback :'.$reqData['requested_callback'];

            } else if($reqData['outcome']=='Document Call') {
                $formType = H::getHelper("ClientCareCoordinatorException")
                ->getFormType($reqData['followupType'],$reqData['monthlyCallId'],$reqData['weeklyCallId']);
                $followupText=$formType.' follow up call for '.$patientName.'<br>';

                if(isset($reqData['intro'])) {
                    $intro = $reqData['intro'];
                    if(isset($intro['guardian'])) {
                        $followupText.="May i speak with :".$intro['guardian'].'<br>';
                    }
                }

                //scheduling section
                if(isset($reqData['scheduling'])) {
                    $scheduling = $reqData['scheduling'];
                    if($formType=='Monthly') {

                        $followupText.="do you have any upcoming vacations :".$scheduling['upcomingvacation'].'<br>';
                        if($scheduling['upcomingvacation']=='Yes') {
                            $followupText.="Will you be needing a nurse to attend the vacation :".$scheduling['nurseattendvacation'].'<br>';
                        }
                        $followupText.="(Respite) Has the work schedule changed :".$scheduling['workschedulechanged'].'<br>';
                        $followupText.="And your current schedule is - has this changed :".$scheduling['currentschedulechanged'].'<br>';

                        if($scheduling['currentschedulechanged']=='Yes') {
                            $noteData = [];
                            $noteData['entity'] = 'Monthly call form';
                            $noteData['entity_id'] = $bucket->id;
                            $noteData['notes'] = "Schedule needs correction in KanTime";
                            $noteData['assigned_to'] = $bucket->ccc_id;
                            H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                        }



                    } else {


                        if($scheduling['coverage_shift_schedule']=='No') {
                            if($formType=='NTH Weekly') {
                                $noteData = [];
                                $noteData['entity'] = 'NTH form';
                                $noteData['entity_id'] = $bucket->id;
                                $noteData['notes'] =$patientName." Coverage shifts are incorrect. Correction is needed in KanTime.";
                                $noteData['assigned_to'] = $bucket->ccc_id;

                                H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                            } else if($formType=='Monthly & NTH') {
                                $noteData = [];
                                $noteData['entity'] = 'NTH & Monthly call form';
                                $noteData['entity_id'] = $bucket->id;
                                $noteData['notes'] =$patientName." Client Coverage schedule needs correction in KanTime";
                                $noteData['assigned_to'] = $bucket->ccc_id;

                                H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                            }
                        }



                        $followupText.="Do you accept coverage :".$scheduling['accept_coverage'].'<br>';
                        if($scheduling['accept_coverage']=='Accepts coverage with stipulations') {

                            $followupText.="Reasons for not accepting coverage :".$scheduling['reason_not_accept_coverage'].'<br>';
                        }

                        $followupText.="Coverage shifts are currently scheduled as... Is still correct? :".$scheduling['coverage_shift_schedule'].'<br>';
                        $followupText.="Are your currently available days and hours still (NTH schedule shows below & allow editing) :".$scheduling['available_days_hours_still'].'<br>';

                    }

                }

                // insurance section
                if(isset($reqData['insurance'])) {

                    if($reqData['insurance']['provider_correct']=='No') {
                        if($formType=='NTH Weekly') {
                            $noteData = [];
                            $noteData['entity'] = 'client';
                            $noteData['entity_id'] = $bucket->id;
                            $noteData['notes'] = $patientName.' '.Auth::user()->name." stated insurance is incorrect. Email is needing to be sent for correction.";
                            $noteData['assigned_to'] = $bucket->ccc_id;
                        } else if($formType=='Monthly') {
                            $noteData = [];
                            $noteData['entity'] = 'client';
                            $noteData['entity_id'] = $bucket->id;
                            $noteData['notes'] = $patientName." Client insurance information has changed. Send email to adjust insurance.";
                            $noteData['assigned_to'] = $bucket->ccc_id;
                        } else if($formType=='Monthly & NTH') {
                            $noteData = [];
                            $noteData['entity'] = 'client';
                            $noteData['entity_id'] = $bucket->id;
                            $noteData['notes'] =$patientName." Client insurance information has changed. Send email to adjust insurance";
                            $noteData['assigned_to'] = $bucket->ccc_id;
                        }
                        H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                    }
                    $followupText.="Are these insurance still correct ? :".$reqData['insurance']['provider_correct'].'<br>';
                }

                // journal notes

                if(isset($reqData['journalnotes'])) {

                    if($reqData['journalnotes']['confirmation']=='Yes') {
                        if($formType=='Monthly') {
                            $noteData = [];
                            $noteData['entity'] = 'Monthly call form';
                            $noteData['entity_id'] = $bucket->id;
                            $noteData['notes']=$patientName." Client needs assistance with journal notes on account. Follow up with client.";
                            $noteData['assigned_to'] = $bucket->ccc_id;
                            H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                        } else if($formType=='Monthly & NTH') {
                            $noteData = [];
                            $noteData['entity'] = 'NTH & Monthly call form';
                            $noteData['entity_id'] = $bucket->id;
                            $noteData['notes']=$patientName." Client needs assistance with journal notes on account. Follow up with client.";
                            $noteData['assigned_to'] = $bucket->ccc_id;
                            H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                        }

                    }

                    $followupText.="I see the following journal notes . Is there anything I can add or assist with further :".$reqData['journalnotes']['confirmation'].'<br>';
                }


                // nurse relation section

                if(isset($reqData['nurserelation'])) {
                    $nurseRelation = $reqData['nurserelation'];
                    $followupText.="Do you have a good relationship with all of the nurses in the home :".$nurseRelation['relation'].'<br>';
                    $nurseWithIssue = [];
                    if($nurseRelation['relation']=='No'){
                        if(isset($nurseRelation['currentissues'])){
                            foreach($nurseRelation['currentissues'] as $key => $nurse){

                                if($nurse['issue']!="None") {
                                    $followupText.=$nurse['nursename'].'<br>';
                                    $nurseWithIssue[$key]["name"]=$nurse['nursename'];

                                    $followupText.="What are the current issues -".$nurse['issue'].'<br>';
                                    if($nurse['issue']=='Clinicial training') {
                                        $nurseWithIssue[$key]["detail"]=$nurse['clinicialtraining'];
                                        $followupText.="Details of issue -".$nurse['clinicialtraining'].'<br>';
                                        if($nurse['clinicialtraining']=='Specialized skills') {
                                            $followupText.="Skills-".$nurse['skills'];
                                        }
                                    } else if($nurse['issue']=='Neglect/abuse/exploitation') {
                                        $nurseWithIssue[$key]["detail"]=$nurse['relationneglect'];
                                        $followupText.="Details of issue -".$nurse['relationneglect'].'<br>';
                                    } else if($nurse['issue']=='Professional conduct/Behaviour') {
                                        $nurseWithIssue[$key]["detail"]=$nurse['behaviour'];
                                        $followupText.="Details of issue -".$nurse['behaviour'].'<br>';
                                    } else if($nurse['issue']=='Personality/Fit') {
                                        $nurseWithIssue[$key]["detail"]=$nurse['personality'];
                                        $followupText.="Details of issue -".$nurse['personality'].'<br>';
                                    }

                                }
                            }

                            if(!empty($nurseWithIssue)) {
                                foreach($nurseWithIssue as $nurse) {

                                    $ccc_mgr_ids = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole($bucket->aoc_facility_id, 'Client Care Manager');
                                    if (count($ccc_mgr_ids) > 0) {
                                        foreach($ccc_mgr_ids as $ccc_mgr_id) {
                                            $data = [];
                                            $data['user_id'] = $ccc_mgr_id;
                                            $data['title'] = $formType.' call with '.$patientName.' having  '.$nurse["detail"].' issue  with nurse '.$nurse["name"].'.';
                                            $data['type'] = 'client';
                                            $data['related_item'] = $bucket->id;
                                            $data['notes'] = '';
                                            Notifications::addNotification($data);
                                        }
                                    }

                                    if (!empty($bucket->ccm_id) && !empty($bucket->id)) {
                                        $data = [];
                                        $data['user_id'] = $bucket->ccm_id;
                                        $data['title'] = $formType.' call with '.$patientName.' having  '.$nurse["detail"].' issue  with nurse '.$nurse["name"].'.';
                                        $data['type'] = 'client';
                                        $data['related_item'] = $bucket->id;
                                        $data['notes'] = '';
                                        Notifications::addNotification($data);
                                    }


                                    $dbo_ids = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole($bucket->aoc_facility_id, 'DBO');
                                    if (count($dbo_ids) > 0) {
                                        foreach($dbo_ids as $dbo_id) {
                                            $data = [];
                                            $data['user_id'] = $dbo_id;
                                            $data['title'] = $formType.' call with '.$patientName.' having  '.$nurse["detail"].' issue  with nurse '.$nurse["name"].'.';
                                            $data['type'] = 'client';
                                            $data['related_item'] = $bucket->id;
                                            $data['notes'] = '';
                                            Notifications::addNotification($data);
                                        }
                                    }

                                    $rdo_ids = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole($bucket->aoc_facility_id, 'Regional Director of Operations');
                                    if (count($rdo_ids) > 0) {
                                        foreach($rdo_ids as $rdo_id) {
                                            $data = [];
                                            $data['user_id'] = $rdo_id;
                                            $data['title'] = $formType.' call with '.$patientName.' having  '.$nurse["detail"].' issue  with nurse '.$nurse["name"].'.';
                                            $data['type'] = 'client';
                                            $data['related_item'] = $bucket->id;
                                            $data['notes'] = '';
                                            Notifications::addNotification($data);
                                        }
                                    }


                                }
                            }

                        }
                    }
                }
                // Client Satisfaction Setting

                if(isset($reqData['clientsatisfaction'])) {
                    $clientSatisfaction = $reqData['clientsatisfaction'];
                    if(isset($clientSatisfaction['weekly_frequency'])){
                        $weeklyFrequency = config("constant.nthCallFrequency")[$clientSatisfaction['weekly_frequency']];


                        if(!empty($clientSatisfaction['weekly_frequency'])){
                            if($bucket->ccc_nth_call_frequency!=$clientSatisfaction['weekly_frequency']) {
                                if($formType=='NTH Weekly') {
                                    $noteData = [];
                                    $noteData['entity'] = 'NTH form';
                                    $noteData['entity_id'] = $bucket->id;
                                    $noteData['notes'] = $patientName." Client requested NTH Weekly Call frequency to be changed to ".$weeklyFrequency.". Please, navigate to the client profile and adjust the frequency. ";
                                    if(!empty($bucket->ccc_id)){
                                        $cccManagerId = H::getUserById($bucket->ccc_id);
                                        if(!empty($cccManagerId->recruiting_manager)){
                                            $noteData['assigned_to'] = $cccManagerId->recruiting_manager;
                                        }else{
                                            $dbo_id = H::getHelper("UserPermissionResolver")
                                            ->getUserIdByFacilityAndRole($bucket->aoc_facility_id, 'Director of Branch Operations');
                                            if(isset($dbo_id[0])){
                                                $noteData['assigned_to'] = $dbo_id[0];
                                            }else{
                                                $noteData['assigned_to'] = 0;
                                            }
                                        }
                                    }else{
                                        $dbo_id = H::getHelper("UserPermissionResolver")
                                        ->getUserIdByFacilityAndRole($bucket->aoc_facility_id, 'Director of Branch Operations');
                                        if(isset($dbo_id[0])){
                                            $noteData['assigned_to'] = $dbo_id[0];
                                        }else{
                                            $noteData['assigned_to'] = 0;
                                        }
                                    }

                                    H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                                } else if($formType=='Monthly & NTH') {
                                    $noteData = [];
                                    $noteData['entity'] = 'NTH & Monthly Call form';
                                    $noteData['entity_id'] = $bucket->id;
                                    $noteData['notes'] = $patientName." Client requested NTH Weekly Call frequency to be changed to ".$weeklyFrequency.". Please, navigate to the client profile and adjust the frequency. ";
                                    if(!empty($bucket->ccc_id)){
                                        $cccManagerId = H::getUserById($bucket->ccc_id);
                                        if(!empty($cccManagerId->recruiting_manager)){
                                            $noteData['assigned_to'] = $cccManagerId->recruiting_manager;
                                        }else{
                                            $dbo_id = H::getHelper("UserPermissionResolver")
                                            ->getUserIdByFacilityAndRole($bucket->aoc_facility_id, 'Director of Branch Operations');
                                            if(isset($dbo_id[0])){
                                                $noteData['assigned_to'] = $dbo_id[0];
                                            }else{
                                                $noteData['assigned_to'] = 0;
                                            }
                                        }
                                    }else{
                                        $dbo_id = H::getHelper("UserPermissionResolver")
                                        ->getUserIdByFacilityAndRole($bucket->aoc_facility_id, 'Director of Branch Operations');
                                        if(isset($dbo_id[0])){
                                            $noteData['assigned_to'] = $dbo_id[0];
                                        }else{
                                            $noteData['assigned_to'] = 0;
                                        }
                                    }
                                    H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                                }
                            }
                        }

                        $followupText.="Parent request frequency of call to be adjusted (Weekly)-".$weeklyFrequency.'<br>';
                    }
                    if($clientSatisfaction['monthly_frequency']){
                        $monthlyFrequency = config("constant.clientCallFrequency")[$clientSatisfaction['monthly_frequency']];

                        if(!empty($clientSatisfaction['monthly_frequency'])){
                            if($bucket->ccc_client_call_frequency!=$clientSatisfaction['monthly_frequency']) {
                                if($formType=='Monthly') {
                                    $noteData = [];
                                    $noteData['entity'] = 'Monthly Call form';
                                    $noteData['entity_id'] = $bucket->id;
                                    $noteData['notes'] = $patientName." Client requested Monthly Call frequency to be changed to ".$monthlyFrequency.". Please, navigate to the client profile and adjust the frequency. ";
                                    if(!empty($bucket->ccc_id)){
                                        $cccManagerId = H::getUserById($bucket->ccc_id);
                                        if(!empty($cccManagerId->recruiting_manager)){
                                            $noteData['assigned_to'] = $cccManagerId->recruiting_manager;
                                        }else{
                                            $dbo_id = H::getHelper("UserPermissionResolver")
                                            ->getUserIdByFacilityAndRole($bucket->aoc_facility_id, 'Director of Branch Operations');
                                            if(isset($dbo_id[0])){
                                                $noteData['assigned_to'] = $dbo_id[0];
                                            }else{
                                                $noteData['assigned_to'] = 0;
                                            }
                                        }
                                    }else{
                                        $dbo_id = H::getHelper("UserPermissionResolver")
                                        ->getUserIdByFacilityAndRole($bucket->aoc_facility_id, 'Director of Branch Operations');
                                        if(isset($dbo_id[0])){
                                            $noteData['assigned_to'] = $dbo_id[0];
                                        }else{
                                            $noteData['assigned_to'] = 0;
                                        }
                                    }
                                    H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                                } else if($formType=='Monthly & NTH'){
                                    $noteData = [];
                                    $noteData['entity'] = 'NTH & Monthly Call form';
                                    $noteData['entity_id'] = $bucket->id;
                                    $noteData['notes'] = $patientName." Client requested Monthly Call frequency to be changed to ".$monthlyFrequency.". Please, navigate to the client profile and adjust the frequency. ";
                                    if(!empty($bucket->ccc_id)){
                                        $cccManagerId = H::getUserById($bucket->ccc_id);
                                        if(!empty($cccManagerId->recruiting_manager)){
                                            $noteData['assigned_to'] = $cccManagerId->recruiting_manager;
                                        }else{
                                            $dbo_id = H::getHelper("UserPermissionResolver")
                                            ->getUserIdByFacilityAndRole($bucket->aoc_facility_id, 'Director of Branch Operations');
                                            if(isset($dbo_id[0])){
                                                $noteData['assigned_to'] = $dbo_id[0];
                                            }else{
                                                $noteData['assigned_to'] = 0;
                                            }
                                        }
                                    }else{
                                        $dbo_id = H::getHelper("UserPermissionResolver")
                                        ->getUserIdByFacilityAndRole($bucket->aoc_facility_id, 'Director of Branch Operations');
                                        if(isset($dbo_id[0])){
                                            $noteData['assigned_to'] = $dbo_id[0];
                                        }else{
                                            $noteData['assigned_to'] = 0;
                                        }
                                    }
                                    H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                                }
                            }
                        }

                        $followupText.="Parent request frequency of call to be adjusted (Monthly)-".$monthlyFrequency.'<br>';
                    }
                }



                //rating section

                if (isset($reqData['rate'])){
                    $followupText.="On a scale of 1 to 10, how would you rate your experience at Angels of Care? ".$reqData['rate'].'<br>';
                }


                if (isset($reqData['rate']) && !empty($reqData['rate']) && ($reqData['rate'] <= 6)) {

                    $dbo_ids = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole($bucket->aoc_facility_id, 'DBO');
                    if (count($dbo_ids) > 0) {
                        foreach($dbo_ids as $dbo_id) {
                            $data = [];
                            $data['user_id'] = $dbo_id;
                            $data['title'] = $formType.' was completed for '.$patientName.' by ' . Auth::user()->name . 'and the satisfaction rating was documented as '.$reqData['rate'].'.';
                            $data['type'] = 'client';
                            $data['related_item'] = $bucket->id;
                            $data['notes'] = '';
                            Notifications::addNotification($data);
                        }
                    }

                    $rdo_ids = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole($bucket->aoc_facility_id, 'Regional Director of Operations');
                    if (count($rdo_ids) > 0) {
                        foreach($rdo_ids as $rdo_id) {
                            $data = [];
                            $data['user_id'] = $rdo_id;
                            $data['title'] = $formType.' was completed for '.$patientName.' by ' . Auth::user()->name . ' and the satisfaction rating was documented as '.$reqData['rate'].'.';
                            $data['type'] = 'client';
                            $data['related_item'] = $bucket->id;
                            $data['notes'] = '';
                            Notifications::addNotification($data);
                        }
                    }

                    if (!empty($bucket->ccc_id) && !empty($bucket->id)) {
                        $data = [];
                        $data['user_id'] = $bucket->ccc_id;
                        $data['title'] = $formType.' was completed for '.$patientName.' by ' . Auth::user()->name . ' and the satisfaction rating was documented as '.$reqData['rate'].'.';
                        $data['type'] = 'client';
                        $data['related_item'] = $bucket->id;
                        $data['notes'] = '';
                        Notifications::addNotification($data);
                    }

                    $ccc_mgr_ids = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole($bucket->aoc_facility_id, 'Client Care Manager');
                    if (count($ccc_mgr_ids) > 0) {
                        foreach($ccc_mgr_ids as $ccc_mgr_id) {
                            $data = [];
                            $data['user_id'] = $ccc_mgr_id;
                            $data['title'] = $formType.' was completed for '.$patientName.' by ' . Auth::user()->name . ' and the satisfaction rating was documented as '.$reqData['rate'].'.';
                            $data['type'] = 'client';
                            $data['related_item'] = $bucket->id;
                            $data['notes'] = '';
                            Notifications::addNotification($data);
                        }
                    }

                    if ($reqData['rate'] < 3) {
                        $svp_ids = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole($bucket->aoc_facility_id, 'State VP');
                        if (count($svp_ids) > 0) {
                            foreach($svp_ids as $svp_id) {
                                $data = [];
                                $data['user_id'] = $svp_id;
                                $data['title'] = $formType.' was completed for '.$patientName.' by ' . Auth::user()->name . ' and the satisfaction rating was documented as '.$reqData['rate'].'.';
                                $data['type'] = 'client';
                                $data['related_item'] = $bucket->id;
                                $data['notes'] = '';
                                Notifications::addNotification($data);
                            }
                        }
                    }
                }


                 //call recap section

                 if(isset($reqData['callrecap'])) {
                    $callRecap = $reqData['callrecap'];
                    if(!empty($callRecap['thiscall'])) {
                        $followupText.="Recap of this call : ".$callRecap['thiscall'].'<br>';
                    }

                }


                $weeklyData = [];
                $monthlyData = [];
                $weeklyId = 0;
                $monthlyId = 0;
                if($reqData['followupType']=='weekly') {
                    $weeklyData = [
                        "outcome"=>"Completed",
                        'last_weekly_followup'=>date('Y-m-d H:i:s'),
                        'outcome_json'=>json_encode($reqData)
                    ];
                    $weeklyId = $request->followupId;
                    if($reqData['monthlyCallId']) {
                        $monthlyData = [
                            "outcome"=>"Completed",
                            'last_monthly_followup'=>date('Y-m-d H:i:s'),
                            'outcome_json'=>json_encode($reqData)
                        ];
                        $monthlyId = $reqData['monthlyCallId'];
                    }
                } else if($reqData['followupType']=='monthly') {
                    $monthlyData = [
                        "outcome"=>"Completed",
                        'last_monthly_followup'=>date('Y-m-d H:i:s'),
                        'outcome_json'=>json_encode($reqData)
                    ];
                    $monthlyId = $request->followupId;
                    if($reqData['weeklyCallId']) {
                        $weeklyData = [
                            "outcome"=>"Completed",
                            'last_weekly_followup'=>date('Y-m-d H:i:s'),
                            'outcome_json'=>json_encode($reqData)
                        ];
                        $weeklyId = $reqData['weeklyCallId'];
                    }
                }

                if(!empty($weeklyData)) {
                    DB::table("ccc_followup_calls")->where('ccc_followup_calls.id',"=",$weeklyId)->update($weeklyData);
                }
                if(!empty($monthlyData)) {
                    DB::table("ccc_followup_calls")->where('ccc_followup_calls.id',"=",$monthlyId)->update($monthlyData);
                }


                    if(!empty($followupText)) {
                        $noteData = [];
                        $noteData['entity'] = 'client';
                        $noteData['entity_id'] = $bucket->id;
                        $noteData['notes'] =$followupText;
                        $noteData['type'] ='call';
                        $noteData['misc3'] =$weeklyId;
                        $noteData['misc4'] =$monthlyId;

                        H::getHelper("ActivityNotes")->setActivityNotes($noteData);

                    }


            }
            return response()->json(['status'=>1]);


        } catch(\Exception $e) {
            return response()->json(['status'=>0,'message'=>$e->getMessage()]);

        }
    }

    public function saveWeeklyInputCall(Request $request) {
            $res = $this->saveWeeklyMonthlyInputCall($request);
            return $res;
            //return response()->json(['status'=>1]);
    }

    public function getMonthlyInputCall(Request $request) {

        $attemptCount = 0;

        $clientDetails = MssqlPatients::select("mssql_patients.*","mssql_facilities.FacilityName",DB::raw("CONCAT(FLOOR((TIMESTAMPDIFF(MONTH, mssql_patients.Patient_DOB, CURDATE()) / 12)), ' Years ') AS Patient_age"))
        ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
        ->where("mssql_patients.id",$request->input('id'))->first();

        if($clientDetails->PatientName == ''){
            $clientDetails->PatientName = $clientDetails->Patient_FirstName.' '.$clientDetails->Patient_LastName;
        }
        //Check if Work force exist
        $is_workForce = 0;
        if (substr_count(strtolower($clientDetails->CF_WorkForceDevProj), 'yes')) {
            $is_workForce = 1;
        }
        $skillArray = $skillAddedArray = [];
        $skillDisplayValue = '<div class="tags-cnt">';
        if ($clientDetails->CF_Skills != '') {
            $skillAddedArray = explode(",", $clientDetails->CF_Skills);
            foreach ($skillAddedArray  as $skillValue) {
                if (strtolower($skillValue) == 'trach' || strtolower($skillValue) == 'vent') {
                    $skillArray[] = $skillValue;
                }
            }
            if (!empty($skillArray)) {
                foreach ($skillArray as $skillData) {
                    $skillDisplayValue .= '<span class="tag tag-grey">' . $skillData . '</span>';
                }
            }
        }
        $skillDisplayValue .= '</div>';
        $clientDetails->setAttribute('skillDisplayValue', $skillDisplayValue);
        //Client Preference - START
        $clientPreference = '<div class="tags-cnt">';
        if ($clientDetails->CF_MeetingNurses != '' && substr($clientDetails->CF_MeetingNurses . '   ',0,2)=='No') {
            $clientPreference .='<span class="tag tag-red">Not Meeting Nurses</span>';
        }else {
            $clientPreference .='<span class="tag">Meeting Nurses</span>';
        }
        $petsinHomeArray = [];
        if ($clientDetails->PetsinHome != '') {
            $petsinHomeArray = explode(",", $clientDetails->PetsinHome);
            foreach ($petsinHomeArray  as $petsinHomeValue) {
                if($petsinHomeValue !='') {
                    $clientPreference .= '<span class="tag">' . $petsinHomeValue . '</span>';
                }
            }
        }
        $clientLangArray = [];
        if ($clientDetails->Languages != '') {
            $clientLangArray = explode(",", $clientDetails->Languages);
            foreach ($clientLangArray  as $clientLangValue) {
                if($clientLangValue !='') {
                    $clientPreference .= '<span class="tag">' . $clientLangValue . '</span>';
                }
            }
        }
        if ($clientDetails->PreferenceList != '' && strpos(strtolower($clientDetails->PreferenceList),"nurse attending child") !== false) {
            $clientPreference .='<span class="tag">Nurse attending child\'s school</span>';
        }
        if ($clientDetails->PreferenceList != '' && strpos(strtolower($clientDetails->PreferenceList),"client prefers non-smoking") !== false) {
            $clientPreference .='<span class="tag">Non-smoking Nurses</span>';
        }
        if ($clientDetails->PreferenceList != '' && strpos(strtolower($clientDetails->PreferenceList),"media consent form on file") !== false) {
            $clientPreference .='<span class="tag">Media consent on file</span>';
        }
        if ($clientDetails->PreferenceList != '' && strpos(strtolower($clientDetails->PreferenceList),"accepts coverage : yes") !== false) {
            $clientPreference .='<span class="tag">Accepts Coverage</span>';
        }
        $clientPreference .= '</div>';
        $clientDetails->setAttribute('clientPreference', $clientPreference);
        //Client Preference - END
        //Clinician Preference - START
        $clinicianPreference = '<div class="tags-cnt">';
        $clinicianDisciplineArray = [];
        if ($clientDetails->CF_ClinicianDiscipline != '') {
            $clinicianDisciplineArray = explode(",", $clientDetails->CF_ClinicianDiscipline);
            foreach ($clinicianDisciplineArray  as $clinicianDisciplineValue) {
                if($clinicianDisciplineValue !='') {
                    $clinicianPreference .= '<span class="tag">' . $clinicianDisciplineValue . '</span>';
                }
            }
        }
        $petsNurseCannotOwnArray = [];
        if ($clientDetails->PetsNurseCannotOwn != '') {
            $petsNurseCannotOwnArray = explode(",", $clientDetails->PetsNurseCannotOwn);
            foreach ($petsNurseCannotOwnArray  as $petsNurseCannotOwnValue) {
                if($petsNurseCannotOwnValue !='') {
                    $clinicianPreference .= '<span class="tag">No ' . $petsNurseCannotOwnValue . '</span>';
                }
            }
        }
        $clinicianLangArray = [];
        if ($clientDetails->CF_NurseLanguage != '') {
            $clinicianLangArray = explode(",", $clientDetails->CF_NurseLanguage);
            foreach ($clinicianLangArray  as $clinicianLangValue) {
                if($clinicianLangValue !='') {
                    $clinicianPreference .= '<span class="tag">' . $clinicianLangValue . '</span>';
                }
            }
        }
        if ($clientDetails->CF_LiftingRequired != '' && substr_count(strtolower($clientDetails->CF_LiftingRequired), 'yes')) {
            $clinicianPreference .='<span class="tag">Lifting Required</span>';
        }else {
            $clinicianPreference .='<span class="tag">No Lifting Required</span>';
        }
        $genderPref = 'Either Gender';
        if(strpos(strtolower($clientDetails->PreferenceList),'does not accept female nurses') !== false){
            $genderPref = 'Male Only';
        }elseif (strpos(strtolower($clientDetails->PreferenceList),'male nurse accepted') === false){
            $genderPref = 'Female Only';
        }
        $clinicianPreference .= '<span class="tag">' . $genderPref . '</span>';

        if ($clientDetails->PreferenceList != '' && strpos(strtolower($clientDetails->PreferenceList),"smokers live in home") !== false) {
            $clinicianPreference .='<span class="tag">Smokers live in home</span>';
        }
        if ($clientDetails->PreferenceList != '' && strpos(strtolower($clientDetails->PreferenceList),"nurses must be covid-19 vaccin") !== false) {
            $clinicianPreference .='<span class="tag">COVID-19 Vaccinated</span>';
        }
        $clinicianPreference .= '</div>';
        $clientDetails->setAttribute('clinicianPreference', $clinicianPreference);
        //Clinician Preference - END

        $clientDetails->setAttribute('is_workForce', $is_workForce);


        $needToHireChecklistDetails = DB::table('NeedToHire_Accounts')->select("NeedToHire_Checklist.*",'NeedToHire_Accounts.*', DB::raw("DATEDIFF(now(),NeedToHire_Accounts.created_at) as daysInNTH"),DB::raw("NeedToHire_Accounts.id as needToHireId"),DB::raw("NeedToHire_Checklist.id as needToHireChecklistId"))
                ->leftJoin('NeedToHire_Checklist', 'NeedToHire_Checklist.aoc_nthaccounts_id', '=', 'NeedToHire_Accounts.id')
                ->where("NeedToHire_Accounts.aoc_patient_id",$request->input('id'))->orderBy('NeedToHire_Accounts.id','desc')->first();

       // $bucket = DB::table("mssql_patients")->select("mssql_patients.*")->where('id',$request->id)->first();

        if(isset($clientDetails->attempt_count)) {
            $attemptCount = $clientDetails->attempt_count;
        }
        $socialMediaAdDetail = [];
        	if(!empty($needToHireChecklistDetails)) {
				$socialMediaAdDetail = H::getHelper("SocialMedia")->getSocialMediaForPatient($needToHireChecklistDetails->needToHireId);
			}
        return view('ccc.monthlyForm',['aocPatientId'=>$request->id,"clientDetails"=>$clientDetails,"needToHireChecklistDetails"=>$needToHireChecklistDetails,"attemptCount"=>$attemptCount,'socialMediaAdDetail'=>$socialMediaAdDetail]);
    }

    public function getWeeklyInputCall(Request $request) {

        $isMediaConsent = 0;
        $attemptCount = 0;



            $isCombine = 0;
            $monthlyCallId = 0;
            $weeklyCallId = 0;

            $followupCall = DB::table("ccc_followup_calls")
            ->select("*")->where("id",$request->id)->first();

            $mssqlPatient = DB::table("NeedToHire_Accounts")->select("mssql_patients.PreferenceList","NeedToHire_Accounts.attempt_count")->leftJoin("mssql_patients",function($query){
                $query->on("mssql_patients.id","=","NeedToHire_Accounts.aoc_patient_id");
            })->where('NeedToHire_Accounts.id',"=",$followupCall->aoc_nthaccount_id)->where("PreferenceList","like",'%Media Consent Form on File%')->first();


            if($request->type=='weekly') {

                $monthlyCallId = H::getHelper("ClientCareCoordinatorException")->isMonthlyCallOpen($followupCall->aoc_patient_id);
                if($monthlyCallId) {
                    $isCombine = 1;
                }
            }
            if($request->type=='monthly') {
                $weeklyCallId = H::getHelper("ClientCareCoordinatorException")->isWeeklyCallOpen($followupCall->aoc_patient_id);

                if($weeklyCallId) {
                    $isCombine = 1;
                }
            }



            $clientId = $followupCall->aoc_patient_id;

            if($mssqlPatient){
                $isMediaConsent=1;
            }
            if($followupCall->attempt_count) {
                $attemptCount = $followupCall->attempt_count;
            }



        $clientDetails = MssqlPatients::select("mssql_patients.*","mssql_facilities.FacilityName",DB::raw("CONCAT(FLOOR((TIMESTAMPDIFF(MONTH, mssql_patients.Patient_DOB, CURDATE()) / 12)), ' Years ') AS Patient_age"))
        ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
        ->where("mssql_patients.id",$clientId)->first();

        if($clientDetails->PatientName == ''){
            $clientDetails->PatientName = $clientDetails->Patient_FirstName.' '.$clientDetails->Patient_LastName;
        }
        //Check if Work force exist
        $is_workForce = 0;
        if (substr_count(strtolower($clientDetails->CF_WorkForceDevProj), 'yes')) {
            $is_workForce = 1;
        }
        $skillArray = $skillAddedArray = [];
        $skillDisplayValue = '<div class="tags-cnt">';
        if ($clientDetails->CF_Skills != '') {
            $skillAddedArray = explode(",", $clientDetails->CF_Skills);
            foreach ($skillAddedArray  as $skillValue) {
                if (strtolower($skillValue) == 'trach' || strtolower($skillValue) == 'vent') {
                    $skillArray[] = $skillValue;
                }
            }
            if (!empty($skillArray)) {
                foreach ($skillArray as $skillData) {
                    $skillDisplayValue .= '<span class="tag tag-grey">' . $skillData . '</span>';
                }
            }
        }
        $skillDisplayValue .= '</div>';
        $clientDetails->setAttribute('skillDisplayValue', $skillDisplayValue);
        //Client Preference - START
        $clientPreference = '<div class="tags-cnt">';
        if ($clientDetails->CF_MeetingNurses != '' && substr($clientDetails->CF_MeetingNurses . '   ',0,2)=='No') {
            $clientPreference .='<span class="tag tag-red">Not Meeting Nurses</span>';
        }else {
            $clientPreference .='<span class="tag">Meeting Nurses</span>';
        }
        $petsinHomeArray = [];
        if ($clientDetails->PetsinHome != '') {
            $petsinHomeArray = explode(",", $clientDetails->PetsinHome);
            foreach ($petsinHomeArray  as $petsinHomeValue) {
                if($petsinHomeValue !='') {
                    $clientPreference .= '<span class="tag">' . $petsinHomeValue . '</span>';
                }
            }
        }
        $clientLangArray = [];
        if ($clientDetails->Languages != '') {
            $clientLangArray = explode(",", $clientDetails->Languages);
            foreach ($clientLangArray  as $clientLangValue) {
                if($clientLangValue !='') {
                    $clientPreference .= '<span class="tag">' . $clientLangValue . '</span>';
                }
            }
        }
        if ($clientDetails->PreferenceList != '' && strpos(strtolower($clientDetails->PreferenceList),"nurse attending child") !== false) {
            $clientPreference .='<span class="tag">Nurse attending child\'s school</span>';
        }
        if ($clientDetails->PreferenceList != '' && strpos(strtolower($clientDetails->PreferenceList),"client prefers non-smoking") !== false) {
            $clientPreference .='<span class="tag">Non-smoking Nurses</span>';
        }
        if ($clientDetails->PreferenceList != '' && strpos(strtolower($clientDetails->PreferenceList),"media consent form on file") !== false) {
            $clientPreference .='<span class="tag">Media consent on file</span>';
        }
        if ($clientDetails->PreferenceList != '' && strpos(strtolower($clientDetails->PreferenceList),"accepts coverage : yes") !== false) {
            $clientPreference .='<span class="tag">Accepts Coverage</span>';
        }
        $clientPreference .= '</div>';
        $clientDetails->setAttribute('clientPreference', $clientPreference);
        //Client Preference - END
        //Clinician Preference - START
        $clinicianPreference = '<div class="tags-cnt">';
        $clinicianDisciplineArray = [];
        if ($clientDetails->CF_ClinicianDiscipline != '') {
            $clinicianDisciplineArray = explode(",", $clientDetails->CF_ClinicianDiscipline);
            foreach ($clinicianDisciplineArray  as $clinicianDisciplineValue) {
                if($clinicianDisciplineValue !='') {
                    $clinicianPreference .= '<span class="tag">' . $clinicianDisciplineValue . '</span>';
                }
            }
        }
        $petsNurseCannotOwnArray = [];
        if ($clientDetails->PetsNurseCannotOwn != '') {
            $petsNurseCannotOwnArray = explode(",", $clientDetails->PetsNurseCannotOwn);
            foreach ($petsNurseCannotOwnArray  as $petsNurseCannotOwnValue) {
                if($petsNurseCannotOwnValue !='') {
                    $clinicianPreference .= '<span class="tag">No ' . $petsNurseCannotOwnValue . '</span>';
                }
            }
        }
        $clinicianLangArray = [];
        if ($clientDetails->CF_NurseLanguage != '') {
            $clinicianLangArray = explode(",", $clientDetails->CF_NurseLanguage);
            foreach ($clinicianLangArray  as $clinicianLangValue) {
                if($clinicianLangValue !='') {
                    $clinicianPreference .= '<span class="tag">' . $clinicianLangValue . '</span>';
                }
            }
        }
        if ($clientDetails->CF_LiftingRequired != '' && substr_count(strtolower($clientDetails->CF_LiftingRequired), 'yes')) {
            $clinicianPreference .='<span class="tag">Lifting Required</span>';
        }else {
            $clinicianPreference .='<span class="tag">No Lifting Required</span>';
        }
        $genderPref = 'Either Gender';
        if(strpos(strtolower($clientDetails->PreferenceList),'does not accept female nurses') !== false){
            $genderPref = 'Male Only';
        }elseif (strpos(strtolower($clientDetails->PreferenceList),'male nurse accepted') === false){
            $genderPref = 'Female Only';
        }
        $clinicianPreference .= '<span class="tag">' . $genderPref . '</span>';

        if ($clientDetails->PreferenceList != '' && strpos(strtolower($clientDetails->PreferenceList),"smokers live in home") !== false) {
            $clinicianPreference .='<span class="tag">Smokers live in home</span>';
        }
        if ($clientDetails->PreferenceList != '' && strpos(strtolower($clientDetails->PreferenceList),"nurses must be covid-19 vaccin") !== false) {
            $clinicianPreference .='<span class="tag">COVID-19 Vaccinated</span>';
        }
        $clinicianPreference .= '</div>';
        $clientDetails->setAttribute('clinicianPreference', $clinicianPreference);
        //Clinician Preference - END

        $clientDetails->setAttribute('is_workForce', $is_workForce);


        $needToHireChecklistDetails = DB::table('NeedToHire_Accounts')->select("NeedToHire_Checklist.*",'NeedToHire_Accounts.*', DB::raw("DATEDIFF(now(),NeedToHire_Accounts.created_at) as daysInNTH"),DB::raw("NeedToHire_Accounts.id as needToHireId"),DB::raw("NeedToHire_Checklist.id as needToHireChecklistId"))
                ->leftJoin('NeedToHire_Checklist', 'NeedToHire_Checklist.aoc_nthaccounts_id', '=', 'NeedToHire_Accounts.id')
                ->where("NeedToHire_Accounts.aoc_patient_id",$clientId)->orderBy('NeedToHire_Accounts.id','desc')->first();

        $socialMediaAdDetail = [];
            $socialMediaAdDetail = H::getHelper("SocialMedia")->getSocialMediaForPatient($request->id);
        $needToHireAccount = DB::table("NeedToHire_Accounts")
        ->select("NeedToHire_Accounts.date_media_refused")
        ->leftJoin("mssql_patients",function($query){
            $query->on("mssql_patients.id","=","NeedToHire_Accounts.aoc_patient_id");
        })->where('NeedToHire_Accounts.id',"=",$request->id)->first();

            $clientSkills = $skillAddedArray;
            $liftingRequired = $clientDetails->CF_LiftingRequired;
            if(substr_count(strtolower($clientDetails->CF_LiftingRequired), 'yes')) {
                $clientSkills[]="Lifting Requirements";
            }
            $monday = date('Y-m-d', strtotime("this week"));

            $dateTime = new \DateTime($monday);
            $dateTime->modify('+1 weeks');
            $sunday = $dateTime->modify('Sunday')->format("Y-m-d");

            $scheduleMasterData = DB::table("mssql_schedulemaster")
            ->select("ScheduleDate",DB::raw("DAYNAME(ScheduleDate) as day"),
            DB::raw("concat(mssql_providers.FirstName,' ',mssql_providers.LastName) as ProviderName"),
            "PlannedStart",
            "PlannedEnd",
            "Hours"
            )
            ->leftJoin("mssql_providers","mssql_providers.ProviderID","=","mssql_schedulemaster.ProviderID")
            ->whereDate("ScheduleDate",">=",$monday)
            ->whereDate("ScheduleDate","<=",$sunday)
            ->where("PatientID",$clientDetails->PatientID)
            ->orderBy("PlannedStart")->get();


            $upcomingAppointments = $coverageUpcomingAppoinments = [];
            $upcomingAppointments = DB::table('recruiting_schedule')
            ->leftJoin('schedule_types', 'recruiting_schedule.type_id', '=', 'schedule_types.id')
            ->leftJoin('mssql_recruitertracker', 'mssql_recruitertracker.id', '=', 'recruiting_schedule.tracker_id')
            ->select('schedule_types.type as type','recruiting_schedule.*','mssql_recruitertracker.Candidate','mssql_recruitertracker.Skillset', 'mssql_recruitertracker.Schedule')
            ->where('recruiting_schedule.aoc_patient_id',$clientDetails->id)->whereDate('appointment_date','>=',date('Y-m-d'))->orderBy('appointment_date','asc')->get();

            $needToHireChecklistDetails = DB::table('NeedToHire_Accounts')->select("NeedToHire_Checklist.*",'NeedToHire_Accounts.*', DB::raw("DATEDIFF(now(),NeedToHire_Accounts.created_at) as daysInNTH"),DB::raw("NeedToHire_Accounts.id as needToHireId"),DB::raw("NeedToHire_Checklist.id as needToHireChecklistId"))
                ->leftJoin('NeedToHire_Checklist', 'NeedToHire_Checklist.aoc_nthaccounts_id', '=', 'NeedToHire_Accounts.id')
                ->where("NeedToHire_Accounts.aoc_patient_id",$clientDetails->id)->orderBy('NeedToHire_Accounts.IsActive','desc')->orderBy('NeedToHire_Accounts.id','desc')->first();

            $coverageShiftsQuery = DB::Raw("with cte as
                (SELECT
                    'tracker' as type,
                    shift_date,
                    DAYNAME(shift_date) as day,
                    Schedule_Time,
                    Hours,
                    ifnull(p.ProviderName, new_clinician_name) as ClinicianName
                    FROM `coverage_tracker`
                    left join mssql_providers p on aoc_provider_id = p.id
                    where aoc_patient_id = ? and shift_date > ? and shift_date < ?
                union
                select
                    'map' as type,
                    shift_date,
                    DAYNAME(shift_date) as day,
                    Schedule_Time,
                    Hours,
                    '' as ClinicianName
                    from maps_coverageneeds
                    where aoc_patient_id = ? and shift_date > ? and shift_date < ?
                )
                select * from cte order by shift_date"
            );
            $coverageShifts = DB::select($coverageShiftsQuery, [
                $clientDetails->id,
                \Carbon\Carbon::today()->format('Y-m-d'),
                \Carbon\Carbon::today()->addWeeks(2)->format('Y-m-d'),
                $clientDetails->id,
                \Carbon\Carbon::today()->format('Y-m-d'),
                \Carbon\Carbon::today()->addWeeks(2)->format('Y-m-d')
            ]);

            $primaryInsurance = $secondaryInsurance = '';
            if (LD::checkFeatureFlag('fixed-issue-with-monthly-list-insurance')) {
                if (!empty($clientDetails->kantime_primary_payer_id)) {
                    $primaryInsurance = DB::table('payment_source')
                                ->select(
                                    'payment_source.name as name'
                                )
                                ->where('payment_source.payersource_id', $clientDetails->kantime_primary_payer_id)
                                ->offset(0)
                                ->limit(1)
                                ->get();
                }
                if (!empty($clientDetails->secondary_payer_id)) {
                    $secondaryInsurance = DB::table('payment_source')
                                ->select(
                                    'payment_source.name as name'
                                )
                                ->where('payment_source.payersource_id', $clientDetails->secondary_payer_id)
                                ->offset(0)
                                ->limit(1)
                                ->get();
                }
            } else {
                $primaryInsurance = DB::table('client_payment_source')
                            ->select(
                                'payment_source.name as name'
                            )
                            ->leftJoin(
                                'payment_source',
                                'payment_source.payersource_id',
                                '=',
                                DB::Raw("CASE WHEN client_payment_source.additional_payer_id = 0
                                THEN client_payment_source.primary_payer_id ELSE
                                client_payment_source.additional_payer_id END")
                            )
                            ->where('client_payment_source.primary_payer_id', $clientDetails->kantime_primary_payer_id)
                            ->where('client_payment_source.is_primary', '1')
                            ->where('client_payment_source.payertype', '1')
                            ->whereRaw('(ifnull(start_date,\'0000-00-00 00:00:00\') <= CURRENT_DATE or ifnull(start_date,\'0000-00-00 00:00:00\') = \'0000-00-00 00:00:00\')')
                            ->whereRaw('(ifnull(end_date,\'0000-00-00 00:00:00\') >= CURRENT_DATE or ifnull(end_date,\'0000-00-00 00:00:00\') = \'0000-00-00 00:00:00\')')
                            ->whereRaw('payment_source.name NOT LIKE \'%RESPITE%\'')
                            ->orderBy('client_payment_source.PayerType', 'DESC')
                            ->orderBy('client_payment_source.start_date', 'DESC')
                            ->offset(0)
                            ->limit(1)
                            ->get();
            $secondaryInsurance = DB::table('client_payment_source')
                            ->select(
                                'payment_source.name as name'
                            )
                            ->leftJoin(
                                'payment_source',
                                'payment_source.payersource_id',
                                '=',
                                DB::Raw("CASE WHEN client_payment_source.additional_payer_id = 0
                                THEN client_payment_source.primary_payer_id ELSE
                                client_payment_source.additional_payer_id END")
                            )
                            ->where('client_payment_source.primary_payer_id', $clientDetails->kantime_primary_payer_id)
                            ->where('client_payment_source.payertype', '2')
                            ->whereRaw('(ifnull(start_date,\'0000-00-00 00:00:00\') <= CURRENT_DATE or ifnull(start_date,\'0000-00-00 00:00:00\') = \'0000-00-00 00:00:00\')')
                            ->whereRaw('(ifnull(end_date,\'0000-00-00 00:00:00\') >= CURRENT_DATE or ifnull(end_date,\'0000-00-00 00:00:00\') = \'0000-00-00 00:00:00\')')
                            ->whereRaw('payment_source.name NOT LIKE \'%RESPITE%\'')
                            ->orderBy('client_payment_source.PayerType', 'DESC')
                            ->orderBy('client_payment_source.start_date', 'DESC')
                            ->offset(0)
                            ->limit(1)
                            ->get();
            }

            $customFields = [];
            $customFieldsList = CustomFields::where('is_deleted', 0)
                            ->where('entity', 'client')
                            ->where('category', 'NTH')
                            ->orderBy('sort')
                            ->get()
                            ->toArray();
            foreach($customFieldsList as $key => $field) {
                $customFields[] = view('ManagingCustomFields.customfields', [
                    'field' => $field,
                    'details' => $clientDetails,
                    'id' => $clientDetails->id,
                    'labelField' => 'call_script'
                ])->render();
            }

            $prefFields = [];
            $prefFieldList = CustomFields::where('is_deleted', 0)
                            ->where('entity', 'client')
                            ->where('category', 'Preferences')
                            ->orderBy('sort')
                            ->get()
                            ->toArray();
            foreach($prefFieldList as $key => $field) {
                $prefFields[] = view('ManagingCustomFields.customfields', [
                    'field' => $field,
                    'details' => $clientDetails,
                    'id' => $clientDetails->id,
                    'labelField' => 'call_script'
                ])->render();
            }


            $emergencyContact = DB::table("client_emergency_contacts")
            ->select(DB::raw('concat(first_name," ",last_name) as FullName'),"relation")
            ->where("client_id",$clientDetails->BillingInternalPatientID)
            ->orderBy("priority")->get();

            $nurseRelation = DB::table("mssql_schedulemaster")
            ->distinct()->select(DB::raw('concat(mssql_providers.FirstName," ",mssql_providers.LastName) as ProviderName'),'mssql_providers.id')
            ->leftJoin("mssql_providers","mssql_providers.ProviderID","=","mssql_schedulemaster.ProviderID")
            ->where("PatientID",$clientDetails->PatientID)
            ->whereDate("ScheduleDate",">",date("Y-m-d"))
			->whereNotNull('mssql_schedulemaster.ProviderID')->get();

            $oneMonthAgo = new \DateTime('1 month ago');
            $journalNotes = DB::table("journal_notes")->select("notes","added_date")
            ->where("PatientID",$clientDetails->PatientID)
            ->whereDate("added_date",">",$oneMonthAgo->format('Y-m-d'))->orderBy("added_date","DESC")->get();

			$nthSchedules = [];
			if($needToHireChecklistDetails) {
            $nthSchedules = DB::table("NeedToHire_Schedules")
                ->select("NeedToHire_Schedules.*", 'mssql_patients.is_intake')
                ->leftjoin('NeedToHire_Accounts',
                'NeedToHire_Accounts.id',
                'NeedToHire_Schedules.aoc_nthaccounts_id')
                ->leftjoin('mssql_patients',
                'mssql_patients.id',
                'NeedToHire_Accounts.aoc_patient_id')
                ->where("NeedToHire_Schedules.IsActive",1)
                ->where("aoc_nthaccounts_id", $needToHireChecklistDetails->needToHireId)
                ->orderByRaw("NeedToHire_Schedules.StartDayOfWeekInt=1, NeedToHire_Schedules.StartDayOfWeekInt")
                ->orderBy("NeedToHire_Schedules.StartHour","ASC")
                ->orderBy("NeedToHire_Schedules.StartMinute","ASC")->get();
			}

            return view('ccc.weeklyForm',[
                'aocPatientId'=>$request->id,
                'attemptCount'=>$attemptCount,
                "clientDetails"=>$clientDetails,
                "needToHireChecklistDetails"=>$needToHireChecklistDetails,
                'mediaConsent'=>$isMediaConsent,
                'clientId'=>$clientId,
                'socialMediaAdDetail'=>$socialMediaAdDetail,
                'needToHireAccount'=> $needToHireAccount,
                'upcomingAppointments'=>$upcomingAppointments,
                'coverageUpcomingAppoinments' => $coverageUpcomingAppoinments,
                'coverageShifts' => $coverageShifts,
                'primaryInsurance' => $primaryInsurance,
                'secondaryInsurance' => $secondaryInsurance,
                'customFields' => $customFields,
                'emergencyContact'=>$emergencyContact,
                'nurseRelation'=>$nurseRelation,
                'journalNotes'=>$journalNotes,
                'preferencefields'=>$prefFields,
                'isCombine'=>$isCombine,
                'monthlyCallId'=>$monthlyCallId,
                'weeklyCallId'=>$weeklyCallId,
                'type'=>$request->type,
                'followupId'=>$request->id,
                'clientskills'=>$clientSkills,
                'scheduleMasterData'=>$scheduleMasterData,
                'nthSchedules' => (count($nthSchedules)) ?
                    view('ccc.documentcall.tabs._buildScheduleGrid',[
                        'nthschedule' => $nthSchedules
                    ])->render() : ''
            ]);


    }

    public function getSocialMediaDashboard(
        Request $request,
        SocialMediaRequestOverdueService $socialMediaRequestOverdueService,
        BusinessDaysService $businessDaysService
    ) {
        if ($request->ajax()) {
            $data = H::getHelper("SocialMedia")->getSocialMediaByType($request->input("type"), $request);
            $isRequestsBucket = $request->input('type') === 'social_media_request';
            $holidays = $isRequestsBucket ? $businessDaysService->getHolidaySchedule() : [];

            return Datatables::of($data)
            ->addColumn('is_overdue', function ($row) use ($isRequestsBucket, $socialMediaRequestOverdueService, $holidays) {
                return $isRequestsBucket && $socialMediaRequestOverdueService->isOverdue($row->request_created_at ?? null, $holidays);
            })
            ->editColumn('request_created_at', function ($row) {
                return H::getFormatedLongDateTime($row->request_created_at);
            })
            ->filterColumn('request_created_at', function ($query, $keyword) {
                $query->whereRaw(
                    'DATE_FORMAT(social_media_ads.created_at, "' . config('constant')['mysqlsearchdateformat'] . '") LIKE ?',
                    ["%{$keyword}%"]
                );
            })
            ->addColumn('Action', function ($row) use($request){
                $btn = '';
                if($request->input("type") == 'social_media_request'){
                    $btn .= '<a href="javascript:void(0)" onclick="showAdsInfoForm('.$row->socialMediaId.')" ><i class="fa fa-calendar" aria-hidden="true"></i></a>';
                    $btn .= '<a href="javascript:void(0)" onclick="getClientDetails('.$row->mpid.')" ><i class="fa fa-eye" aria-hidden="true"></i></a>';
                }elseif($request->input("type") == 'social_media_active_ads'){
                    $btn = '<a href="javascript:void(0)" onclick="getSocialMediaDetails('.$row->socialMediaId.')" ><i class="fa fa-eye" aria-hidden="true"></i></a> | ';
                        $btn .= '<a href="javascript:void(0)" onclick="getSocialMediaClosePopup('.$row->socialMediaId.')" ><i class="fa fa-check-square-o" aria-hidden="true"></i></a>';
                }

                return $btn;
            })
            ->editColumn('start_date', function ($request) {
                return H::getFormatedDate($request->start_date);
            })
            ->filterColumn('ClientNameCustom', function ($query, $keyword) {
                $kw = mb_strtolower($keyword);
                $query->whereRaw(
                    "LOWER(CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName)) LIKE ?",
                    ["%{$kw}%"]
                );
            })
            ->filterColumn('cityState', function ($query, $keyword) {
                $kw = mb_strtolower($keyword);
                $query->whereRaw(
                    "LOWER(CONCAT(social_media_ads.city,'/',social_media_ads.state)) LIKE ?",
                    ["%{$kw}%"]
                );
            })
            ->filterColumn('Patient_Zip', function ($query, $keyword) {
                $kw = mb_strtolower($keyword);
                $query->whereRaw("LOWER(mssql_patients.Patient_Zip) LIKE ?", ["%{$kw}%"]);
            })
            ->addIndexColumn()->escapeColumns('Action')->make(true);
        }
    }
    public function reloadCountSocialMedia(Request $request) {
        return json_encode(["counter"=>H::getHelper('SocialMedia')->getSocialMediaByType($request->input("type"),$request,1),"type"=>$request->input("type")]);
    }

    public function getTriageData(Request $request) {
        $data = H::getHelper("Triage")->getTriageDataByType($request->input("type"),$request);
            return Datatables::of($data)->with('pastduecount', function() use ($data) {
                $data2 = $data;
                $filtered_data = $data2->filter(function ($data2) {
                    return $data2->contactwithcaller == '0';
                });
                return $filtered_data->count();
            })->editColumn('callStartTime', function ($row) {
                return H::getFormatedDate($row->callDate).' '.H::getFormatedTime($row->callStartTime);
            })->make(true);
    }
    public function reloadTriageData(Request $request) {
        return json_encode(["counter"=>H::getHelper('Triage')->getTriageDataByType($request->input("type"),$request,1),"type"=>$request->input("type")]);
    }

    public function getClientCareExceptions(Request $request) {
        DB::enableQueryLog();




        if($request->input("type") == 'ccc_intake_coverage_shift_covered_by_perm_nurse') {
            $data = H::getHelper("ClientCareCoordinatorException")->getIntakeCoverageShiftCoveredPermNurse('ccc',$request);
            return Datatables::of($data)
            ->editColumn('CF_GoalSOCDate', function ($row) {
                if(!empty($row->CF_GoalSOCDate)){
                    return [
                        'display' => e(H::getFormatedDate($row->CF_GoalSOCDate)),
                        'timestamp' => strtotime($row->CF_GoalSOCDate)
                    ];
                } else {
                    return [
                        'display' => '',
                        'timestamp' => ''
                    ];
                }
            })->editColumn('shift_date', function ($row) {
                if(!empty($row->shift_date)){
                    return [
                        'display' => e($row->shift_date),
                        'timestamp' => strtotime($row->shift_date)
                    ];
                } else {
                    return [
                        'display' => '',
                        'timestamp' => ''
                    ];
                }
            })->make(true);
        } elseif($request->input("type") == 'ccc_intake_coverage_shift_before_goal_soc_date') {
            $data = H::getHelper("ClientCareCoordinatorException")->getIntakeCoverageShiftBeforeGoalSocDate('ccc',$request);
            return Datatables::of($data)
            ->editColumn('CF_GoalSOCDate', function ($row) {
                if(!empty($row->CF_GoalSOCDate)){
                    return [
                        'display' => e(H::getFormatedDate($row->CF_GoalSOCDate)),
                        'timestamp' => strtotime($row->CF_GoalSOCDate)
                    ];
                } else {
                    return [
                        'display' => '',
                        'timestamp' => ''
                    ];
                }
            })->editColumn('shift_date', function ($row) {
                if(!empty($row->shift_date)){
                    return [
                        'display' => e($row->shift_date),
                        'timestamp' => strtotime($row->shift_date)
                    ];
                } else {
                    return [
                        'display' => '',
                        'timestamp' => ''
                    ];
                }
            })->make(true);
        } else {
            if($request->input("type") == 'weekly_nth_calls') {
                    $data = H::getHelper("ClientCareCoordinatorException")
                    ->getClientCareWeeklyCall($request);
                } elseif($request->input("type") == 'monthly_calls') {
                    $data = H::getHelper("ClientCareCoordinatorException")
                    ->getClientCareMonthlyCall($request);
                LogSq::processSqlLogs('now');
                } else {
                    $data = H::getHelper("ClientCareCoordinatorException")
                    ->getClientCareException($request->input("type"),$request);
                }
        }
        if ($request->input("type") == 'staffed_coverage_shifts_not_updated_in_kantime') {
            return Datatables::of($data)->editColumn('shift_date', function ($row) {
                if(!empty($row->shift_date)){
                    return [
                        'display' => H::getFormatedDate($row->shift_date),
                        'timestamp' => strtotime($row->shift_date)
                    ];
                } else {
                    return [
                        'display' => '',
                        'timestamp' => ''
                    ];
                }
            })->make(true);
        } elseif($request->input("type") == 'weekly_nth_calls') {

            if(LD::checkFeatureFlag('weekly-nth-bucket-ccr-show-all-excelssonly')) {
                $thisDatatable=  Datatables::of($data)->editColumn('is_completed', function ($row) {
                    if($row->attempt_count>=2 || $row->outcome=='Document Call') {
                        return "Yes";
                    } else {
                        return "No";
                    }
                })->setRowAttr([
                    'class' => function($row) {
                        if ($row->outcome == null || $row->outcome == '') {
                            $expiry = date( 'Y-m-d', strtotime( 'wednesday this week' ) );
                            $expiry = $expiry. ' 09:00:00';
                            $today = (new \DateTime())->format('Y-m-d H:i:s');
                            if (strtotime($today) > strtotime($expiry)) {
                                return 'redexception';
                            }
                        }
                        return '';
                    },
                ]);
            } else {
                $thisDatatable=  Datatables::of($data)->setRowAttr([
                    'class' => function($row) {
                        if ($row->outcome == null || $row->outcome == '') {
                            $expiry = date( 'Y-m-d', strtotime( 'wednesday this week' ) );
                            $expiry = $expiry. ' 09:00:00';
                            $today = (new \DateTime())->format('Y-m-d H:i:s');
                            if (strtotime($today) > strtotime($expiry)) {
                                return 'redexception';
                            }
                        }
                        return '';
                    },
                ]);
            }

        } elseif($request->input("type") == 'monthly_calls') {
            $thisDatatable=  Datatables::of($data)->setRowAttr([
                'class' => function($row) {
                    if ($row->outcome == null || $row->outcome == '') {
                        $today = (new \DateTime())->format('Y-m-d');
                        $next15th = date("Y-m-15", strtotime($today));
                        $next20th = date("Y-m-20", strtotime($today));
                        if (strtotime($today) > strtotime($next20th)) {
                            return 'redexception';
                        } elseif (strtotime($today) > strtotime($next15th)) {
                            return 'yellowexception';
                        }
                    }
                    return '';
                },
            ]);
        } elseif($request->input("type") == 'ccc_intake_coverage_shifts_booked') {
            return Datatables::of($data)->editColumn('shift_date', function ($row) {
                if(!empty($row->shift_date)){
                    return [
                        'display' => e($row->shift_date),
                        'timestamp' => strtotime($row->shift_date)
                    ];
                } else {
                    return [
                        'display' => '',
                        'timestamp' => ''
                    ];
                }
            })->make(true);
        } elseif($request->input("type") == 'coverage_shift_without_kantime_clinician') {
            return Datatables::of($data)->editColumn('shift_date', function ($row) {
                if(!empty($row->shift_date)){
                    return [
                        'display' => e($row->shift_date),
                        'timestamp' => strtotime($row->shift_date)
                    ];
                } else {
                    return [
                        'display' => '',
                        'timestamp' => ''
                    ];
                }
            })->make(true);
        } else {
            $thisDatatable=  Datatables::of($data)->setRowAttr([
                'class' => function($row) {
                    if (!empty($row->exception_addressed_date)) {
                        $today = (new \DateTime())->format('Y-m-d');
                        $expiry = (new \DateTime($row->exception_addressed_date))->format('Y-m-d');
                        if (strtotime($today) > strtotime($expiry)) {
                            return 'redexception';
                        } else {
                            return 'yellowexception';
                        }
                    }
                    return '';
                },
            ]);
        }
        $thisDatatable = $thisDatatable->with('plannedhours', function() use ($data) {
            $totalHr =  number_format(array_sum($data->pluck('PlannedHours')->all()), 0);
            return '('.$data->count().' | ' . $totalHr . ' <span style="text-transform: initial;">hrs</span>)';
        })->editColumn('CreatedOn', function ($row) {
            if(!empty($row->CreatedOn)) {
            return H::getFormatedDate($row->CreatedOn);
            }
        })->editColumn('last_weekly_followup', function ($row) {
            if(isset($row->last_weekly_followup)) {
                return H::getFormatedDate($row->last_weekly_followup);
            }
        })->editColumn('last_monthly_followup', function ($row) {
            if(isset($row->last_monthly_followup)){
                return [
                    'display' => e(H::getFormatedDate($row->last_monthly_followup)),
                    'timestamp' => strtotime($row->last_monthly_followup)
                ];
            } else {
                return [
                    'display' => '',
                    'timestamp' => ''
                ];
            }
        })->editColumn('PlannedTime', function ($row) {
            if(isset($row->PlannedStart) && isset($row->PlannedEnd)) {
                return H::getFormatedTime($row->PlannedStart).' - '.H::getFormatedTime($row->PlannedEnd);
            }
        })->editColumn('ScheduleDate', function ($row) {
            if(isset($row->ScheduleDate)) {
                return H::getFormatedDate($row->ScheduleDate);
            }
        })->editColumn('created_at', function ($row) {
            if(isset($row->created_at)) {
                return H::getFormatedDate($row->created_at);
            }
        })->editColumn('SOCDate', function ($row) {
            if(isset($row->SOCDate)) {
                return H::getFormatedDate($row->SOCDate);
            }
        })->addColumn('OnNTH', function ($row) {
            if(empty($row->nth_aoc_patient_id)) {
                return 'No';
            }else{
                return 'Yes';
            }
        })->addColumn('IsPriority', function ($row) {
            if(isset($row->IsPriority) && $row->IsPriority==1) {
                return 'Yes';
            }else{
                return 'No';
            }
        })->addColumn('planned_time_diff', function ($row) {
            if(isset($row->PlannedEnd)) {
                    $diff = H::getHelper("ConstantHelper")->getTimeDifference($row->PlannedEnd,date('Y-m-d H:i:s'));
                    return $diff['hours'];
            } else {
                return 0;
            }

        })->addColumn('checked_off_pasttime', function ($row) {
            if(isset($row->PlannedStart)) {
                return round(Carbon::parse($row->PlannedStart)->diffInMinutes(Carbon::now(), false) / 60, 2);
            }
            return 0;

        })->addColumn('select', function ($row) use($request){
            $checkbox = '<input type="checkbox" name="checkbox-'.$request->input("type").'[]" class="form-input td select-all-' . str_replace(' ', '_', $request->input("type")) . '" value="' . $row->id . '" />';
            return $checkbox;
        })->escapeColumns('select')->make(true);
        return $thisDatatable;
    }
    public function reloadCountClientCareExceptions(Request $request) {
        if($request->input("type") == 'ccc_intake_coverage_shift_covered_by_perm_nurse') {
            return json_encode(["counter"=>H::getHelper('ClientCareCoordinatorException')
            ->getIntakeCoverageShiftCoveredPermNurse('ccc',$request,1),"type"=>$request->input("type")]);
        } elseif($request->input("type") == 'ccc_intake_coverage_shift_before_goal_soc_date') {
            return json_encode(["counter"=>H::getHelper('ClientCareCoordinatorException')
            ->getIntakeCoverageShiftBeforeGoalSocDate('ccc',$request,1),"type"=>$request->input("type")]);
        }else {
                if($request->input("type") == 'weekly_nth_calls') {
                    return json_encode(["counter"=>H::getHelper('ClientCareCoordinatorException')
            ->getClientCareWeeklyCall($request,1),"type"=>$request->input("type")]);
                } elseif($request->input("type") == 'monthly_calls') {
                    return json_encode(["counter"=>H::getHelper('ClientCareCoordinatorException')
            ->getClientCareMonthlyCall($request,1),"type"=>$request->input("type")]);
                } else {
                    return json_encode(["counter"=>H::getHelper('ClientCareCoordinatorException')
            ->getClientCareException($request->input("type"),$request,1),"type"=>$request->input("type")]);
                }
        }
    }

    public function getClientCareCoverageTracker(Request $request) {
        $data = H::getHelper("ClientCareCoordinatorCoverage")->getClientCareCoverageTracker($request->input("type"),$request);
        if ($request->input("type") == 'escalated') {
                return Datatables::of($data)->with('counthours', function() use ($data) {
                    $totalHr =  number_format(array_sum($data->pluck('Total_Hours')->all()), 0);
                    return '('.$data->count().' | ' . $totalHr . ' <span style="text-transform: initial;">hrs</span>)';
                })->addColumn('Action', function ($row) {
                    $btn = '<a href="javascript:void(0)" onclick=\'getCoverageDetails("'.trim($row->id).'")\' ><i class="fa fa-eye" aria-hidden="true"></i></a>';
                    return $btn;
                })->addColumn('select', function ($row) use($request){
                        $checkbox = '<input type="checkbox" name="checkbox-'.$request->input("type").'[]" class="form-input td select-all-' . str_replace(' ', '_', $request->input("type")) . '" value="' . $row->id . '" data-patient_id = "' . $row->aoc_patient_id . '" data-patient_name = "' . $row->patient_name . '" data-ccc_id = "' . $row->userid . '" data-Schedule_Time = "' . $row->Schedule_Time . '"/>';
                    return $checkbox;
                })->escapeColumns('Action')->make(true);
        } else {
            return Datatables::of($data)
            ->with('counthours', function() use ($data) {
                $totalHr =  number_format(array_sum($data->pluck('Total_Hours')->all()), 0);
                return '('.$data->count().' | ' . $totalHr . ' <span style="text-transform: initial;">hrs</span>)';
            })
            ->addColumn('select', function ($row) use($request){
                    $checkbox = '<input type="checkbox" name="checkbox-'.$request->input("type").'[]" class="form-input td select-all-' . str_replace(' ', '_', $request->input("type")) . '" value="' . $row->id . '" data-patient_id = "' . $row->aoc_patient_id . '" data-patient_name = "' . $row->patient_name . '" data-ccc_id = "' . $row->userid . '" data-Schedule_Time = "' . $row->Schedule_Time . '"/>';
                return $checkbox;
            })->addColumn('Action', function ($row) use($request){
                $btn = ' <a href="javascript:void(0)" onclick="showCoverageTrackerStepForm(\'' . $request->input("type") . '\',\'' . $row->id . '\', this)"><i class="fa fa-vcard" aria-hidden="true"></i></a>';
                $btn .= '| <a href="javascript:void(0)" onclick=\'getCoverageDetails("'.trim($row->id).'")\' ><i class="fa fa-eye" aria-hidden="true"></i></a>';
                return $btn;
            })->escapeColumns('select')->make(true);
        }
    }
    public function reloadCountClientCareCoverageTracker(Request $request) {
        return json_encode(["counter"=>H::getHelper('ClientCareCoordinatorCoverage')->getClientCareCoverageTracker($request->input("type"),$request,1),"type"=>$request->input("type")]);
    }
    public function getRecruitingException(Request $request) {


            $data = H::getHelper("RecruitingException")->getRecruitingException($request->input("type"),$request);
                if ($request->input("type") == 'nth_active_tracker_disabled_recruiter' || $request->input("type") == 'mismatched_first_shift_confirmed') {

                    if($request->input("type") == 'mismatched_first_shift_confirmed') {
                        return Datatables::of($data) ->addColumn('Candidate_Start_Date', function ($row) {
                            return [
                                'display' => e(H::getFormatedDate($row->Candidate_Start_Date)),
                                'timestamp' => strtotime($row->Candidate_Start_Date)
                             ];
                        }) ->make(true);
                    } else {
                        return Datatables::of($data) ->make(true);
                    }

                } else {
					if($request->input("type") == 'mismatched_first_shift_confirmed') {
						return Datatables::of($data) ->addColumn('Candidate_Start_Date', function ($row) {
                            return [
                                'display' => e(H::getFormatedDate($row->Candidate_Start_Date)),
                                'timestamp' => strtotime($row->Candidate_Start_Date)
                             ];
                        }) ->make(true);
					} else {
						return Datatables::of($data)->editColumn('lastActivity', function ($row) {
							return H::getFormatedDate($row->lastActivity);
						})->editColumn('CreatedOn', function ($request) {
							return H::getFormatedDate($request->CreatedOn);
						})->make(true);
					}
                }
    }

    public function getRecruitingExceptionCount(Request $request) {
        return json_encode(["counter"=>H::getHelper('RecruitingException')->getRecruitingException($request->input("type"),$request,1),"type"=>$request->input("type")]);
    }
    public function getNTHChecklistData(Request $request) {
        if ($request->ajax()) {

            $data = H::getHelper("NTHChecklist")->getNTHChecklistDataByType($request->input("type"),$request);
            $nonStepBucket = ['social_media_sponsored','hub_city_requisition','escaleted'];
            $moveStepBucket = ['hub_city_requisition','escaleted'];
            if(LD::checkFeatureFlag('add-date-field-into-social-media-sponsored-bucket')){
                return Datatables::of($data)
                ->addColumn('select', function ($row) use($request,$moveStepBucket){
                    $checkbox = '';
                    if(!in_array($request->input("type"),$moveStepBucket)) {
                        $checkbox = '<input type="checkbox" name="checkbox-'.$request->input("type").'[]" class="form-input td select-all-' . str_replace(' ', '_', $request->input("type")) . '" value="' . $row->nthcheckid . '" />';
                    }
                    return $checkbox;
                })
                ->addColumn('SocialMediaStarted', function ($row) {
                    return [
                        'display' => e(H::getFormatedDate($row->SocialMediaStarted)),
                        'timestamp' => strtotime($row->SocialMediaStarted)
                    ];
                })
                ->addColumn('start_date', function ($row) {
                    return [
                        'display' => e(H::getFormatedDate($row->start_date)),
                        'timestamp' => strtotime($row->start_date)
                    ];
                })
                ->addColumn('Action', function ($row) use($request,$nonStepBucket){
                        $btn = '<a href="javascript:void(0)" onclick=\'getClientDetails("'.trim($row->clientId).'")\' ><i class="fa fa-user" aria-hidden="true"></i></a>';
                        if($btn !='') {
                            $btn = $btn.' | ';
                        }
                        $btn .= '<a href="javascript:void(0)" onclick=\'openNthChecklistDetails("'.trim($row->nthaccounts_id).'")\' ><i class="fa fa-eye" aria-hidden="true"></i></a>';
                    if(!in_array($request->input("type"),$nonStepBucket)) {
                        $btn .= ' | <button class="btn bg-transparent btn-with-icon" onclick="showNthForm(\'' . $request->input("type") . '\',\'' . $row->nthcheckid . '\', this)"><i class="fa fa-vcard" aria-hidden="true"></i></button>';
                    }
                    if($request->input("type") == 'social_media_sponsored') {
                        $btn .= ' | <button class="btn bg-transparent btn-with-icon" onclick="showNthSocialMediaForm(\'' . $row->nthaccounts_id . '\', this)"><i class="fa fa-vcard" aria-hidden="true"></i></button>';
                    }
                        if (isset($row->nthaccounts_id) && $row->nthaccounts_id != '') {
                            $btn .= ' | <a data-bs-dismiss="modal" href="javascript:void(0);" onclick="event.preventDefault();openNotes('.$row->nthaccounts_id.',\'nthAccount\',\'NTH Checklist Notes Added\')"><i class="fa  fa-sticky-note-o"></i></a>';
                        }
                    return $btn;
                })
                ->addIndexColumn()->escapeColumns('Action')->make(true);
            }else{
                return Datatables::of($data)
                ->addColumn('select', function ($row) use($request,$moveStepBucket){
                    $checkbox = '';
                    if(!in_array($request->input("type"),$moveStepBucket)) {
                        $checkbox = '<input type="checkbox" name="checkbox-'.$request->input("type").'[]" class="form-input td select-all-' . str_replace(' ', '_', $request->input("type")) . '" value="' . $row->nthcheckid . '" />';
                    }
                    return $checkbox;
                })
                ->addColumn('Action', function ($row) use($request,$nonStepBucket){
                        $btn = '<a href="javascript:void(0)" onclick=\'getClientDetails("'.trim($row->clientId).'")\' ><i class="fa fa-user" aria-hidden="true"></i></a>';
                        if($btn !='') {
                            $btn = $btn.' | ';
                        }
                        $btn .= '<a href="javascript:void(0)" onclick=\'openNthChecklistDetails("'.trim($row->nthaccounts_id).'")\' ><i class="fa fa-eye" aria-hidden="true"></i></a>';
                    if(!in_array($request->input("type"),$nonStepBucket)) {
                        $btn .= ' | <button class="btn bg-transparent btn-with-icon" onclick="showNthForm(\'' . $request->input("type") . '\',\'' . $row->nthcheckid . '\', this)"><i class="fa fa-vcard" aria-hidden="true"></i></button>';
                    }
                    if($request->input("type") == 'social_media_sponsored') {
                        $btn .= ' | <button class="btn bg-transparent btn-with-icon" onclick="showNthSocialMediaForm(\'' . $row->nthaccounts_id . '\', this)"><i class="fa fa-vcard" aria-hidden="true"></i></button>';
                    }
                        if (isset($row->nthaccounts_id) && $row->nthaccounts_id != '') {
                            $btn .= ' | <a data-bs-dismiss="modal" href="javascript:void(0);" onclick="event.preventDefault();openNotes('.$row->nthaccounts_id.',\'nthAccount\',\'NTH Checklist Notes Added\')"><i class="fa  fa-sticky-note-o"></i></a>';
                        }
                    return $btn;
                })
                ->addIndexColumn()->escapeColumns('Action')->make(true);
            }
        }
    }

    public function getNTHExpiringJobslist(Request $request) {
        if ($request->ajax()) {

            $data = H::getHelper("Jobvite")->getNTHExpiringJobs();

            return Datatables::of($data)
            ->editColumn('created_at', function ($request) {
                return H::getFormatedDate($request->created_at);
              })
                ->addColumn('Action', function ($row){
                    $btn = '<a href="https://app.jobvite.com/jhire/modules/requisitions/tabs.html#/summary/' . $row->eId . '" target="_blank"><i class="fa fa-eye" aria-hidden="true"></i></a>';
                    return $btn;
                })
                ->addIndexColumn()->escapeColumns('Action')->make(true);
        }
    }

    public function reloadCountNTHExpiringJobs(Request $request) {
        return json_encode(["counter" => H::getHelper('Jobvite')->getNTHExpiringJobs(1), "type" => 'nth_expiring_jobs']);
    }

    public function getStateBranch(Request $request) {
        $bucketType = !empty($request->input("btype"))?$request->input("btype"):'all';
       return H::getHelper('ConstantHelper')->getFacilitiesByState($request->input("state"),$bucketType);
    }

    public function getOCStateBranch(Request $request) {
        return H::getHelper('ConstantHelper')->getOCBranchByState($request->input("state"));
     }

    public function getJobviteBranchByState(Request $request) {
        $helper = H::getHelper('ConstantHelper');
        // PD-5392: Jobvite dashboard buckets list branches from `jobs`; other callers keep the NeedToHire_Hub list.
        if ($request->input('source') === 'jobs') {
            return $helper->getJobsBranchByState($request->input('state'));
        }
        return $helper->getJobviteBranchByState($request->input('state'));
    }

    public function getOrientationData(Request $request) {



        if ($request->ajax()) {
            //var_dump($request->all());
            $data = H::getHelper("Orientation")->getOrientationData($request->input("type"),$request,0);

            return Datatables::of($data)
            ->filterColumn('NS', function($query, $keyword) {
                $sql = "(SELECT CONCAT(type,': ',DATE_FORMAT(appointment_date, '" . config('constant')['mysqlsearchdateformat'] . "'),' ',TIME_FORMAT(appointment_time, '" . config('constant')['mysqlsearchtimeformat'] . "')) FROM recruiting_schedule as rsinner left join schedule_types on rsinner.type_id = schedule_types.id WHERE rsinner.type_id <> 1 AND rsinner.appointment_date >= CURDATE()  AND rsinner.tracker_id = recruiting_schedule.tracker_id order by appointment_date asc, appointment_time desc limit 1) like ?";
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
               ->addColumn('appointment_date', function ($row) {

                    if ($this->validateDate($row['appointment_date'], 'Y-m-d')) {
                        $date = new \DateTime($row['appointment_date']);
                        $formattedDate = $date->format('m/d/Y');

                        return $formattedDate;
                    } else {
                        return '-';
                    }
                })
                ->addColumn('appointment_time', function ($row) {
                    //return "1:00 PM";
                    return H::getFormatedTime($row['appointment_date'].' '.$row['appointment_time']);

                })
                ->addColumn('Action', function ($row) use ($request){
                    $btn = '';
					if(LD::checkFeatureFlag('oc-next-step')){
                        if (isset($row['id']) && $row['id'] != '' && $row['id'] != null) {
                            $btn .= '<a href="javascript:void(0)" onclick="showOrientationForm(\''.trim($row['id']).'\', \''.$request->input("type").'\');"><i class="fa fa-calendar" aria-hidden="true"></i></a>';
                        }
                    }

                    if (isset($row['id']) && $row['id'] != '' && $row['id'] != null) {
                        $btn .= '<a href="javascript:void(0)" onclick="updateNurseIpeaInfo(' . trim($row['id']) . ');" ><i class="fa fa-vcard" aria-hidden="true"></i></a>';
                    }

                    if(H::canPermissions('can_remove_ipea_template')){
                        if (isset($row['rtid']) && $row['rtid'] != '' && $row['rtid'] != null) {
                            $btn .= '<a href="javascript:void(0)" title="Remove Candidate" onclick="showRemoveCandidateModel(' . trim($row['rtid']) . ',\''.$request->input("type").'\');" ><i class="fa fa-times text-danger" aria-hidden="true"></i></a>';
                        }
                    }

                    if (isset($row['providerId']) && $row['providerId'] != '' && $row['providerId'] != null) {
                        $btn .= '<a href="javascript:void(0)" title="Badge Process" onclick="processProviderBadge(' . trim($row['providerId']) . ');" ><i class="fa fa-check-square-o" aria-hidden="true"></i></a>';
                    }

                    return $btn;
                })
                ->addIndexColumn()->escapeColumns('Action')->make(true);
        }
    }

    public function getAssignRecruiterForm(Request $request){
        $requestData = $request->all();
        $candidateIds = explode(",",$requestData['ids']);
        $recruiters = DB::table("candidates")->distinct()->select("name")->leftJoin("users",function($query){
            $query->on("users.id","=","candidates.user_id");
        })
        ->whereIn("candidates.id",$candidateIds)->get()->toArray();
        $recruiters = json_decode(json_encode($recruiters),true);
        $recruitersList = [];
        $recruiterString = '';
        foreach($recruiters as $recruiter) {
            $recruitersList[] = $recruiter['name'];
        }
        if(!empty($recruitersList)) {
            $recruiterString = implode(",",$recruitersList);
        }

        return view('jobvite.assignrecruiter',['candidateIds' => $candidateIds,'current_recruiter'=>$recruiterString]);
    }

    public function saveAssignRecruiter(Request $request){
        try {
            $requestData = $request->all();
            if(!empty($requestData['candidates'])) {
                $candidates = explode(",",$requestData['candidates']);
                $newRecruiter = DB::table("users")->select("name")->where("id",$requestData['recruiter'])->first();

                foreach($candidates as $candidate) {
                    $currentRecruiter = DB::table("candidates")->select("name")->leftJoin("users",function($query){
                            $query->on("users.id","=","candidates.user_id");
                        })->where("candidates.id",$candidate)->first();


                    //Adding Notes
                    $notesData = [
                            'entity' => 'candidate',
                            'entity_id' => $candidate,
                            'notes' => Auth::user()->name. ' updated the recruiter from '.$currentRecruiter->name.' to '.$newRecruiter->name,
                        ];

                      H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                }
                //exit;
                DB::table("candidates")->whereIn("id",$candidates)->update(["user_id"=>$requestData['recruiter']]);
            }
            return response()->json(["success" => true]);
        } catch(\Exception $e) {
            return response()->json(['status' => false]);
        }
        //exit;
    }

    public function getJobviteData(
        GetNurseApplicationsRequest $request,
        CandidateApplicationsService $candidateApplicationsService,
        NursesApplicationsTransformer $nursesApplicationsTransformer,
        CandidateApplicationsReadRepository $applicationsReadRepository,
        ConstantHelper $helper
    ) {
        $payload = $request->getNurseApplicationsListDTO($helper->getIsUserInRoleSafe(RolesEnum::getRoleName(RolesEnum::THERAPY_CLINICAL_MANAGER->value), Auth::id()));
        $applications = $candidateApplicationsService->getFilteredCandidateApplications($payload);

        return $nursesApplicationsTransformer->transform(
            $applications,
            $payload,
            $applicationsReadRepository->getFilteredNursesApplicationsPastDueCount($payload)
        );
    }

    public function reloadCount(Request $request) {
        return json_encode(["counter"=>H::getHelper('Activity')->getActivityByStatus($request->input("type"),$request,1),"type"=>$request->input("type")]);
    }

    public function reloadCountNTH(Request $request) {
        return json_encode(["counter"=>H::getHelper('NTHChecklist')->getNTHChecklistDataByType($request->input("type"),$request,1),"type"=>$request->input("type")]);
    }

    public function reloadCountOrientation(Request $request) {
        return json_encode(["counter"=>H::getHelper('Orientation')->getOrientationData($request->input("type"),$request,1),"type"=>$request->input("type")]);
    }

    public function reloadCountJobvite(
        GetNurseApplicationsRequest $request,
        CandidateApplicationsReadRepository $applicationsReadRepository
    ) {
        $counters = $applicationsReadRepository->getFilteredNursesApplicationsPastDueCount($request->getNurseApplicationsListDTO());
        $counter = sprintf('(%s)', $counters['total']);

        if(LD::checkFeatureFlag('dashboard-past-due-count')) {
            $counter = sprintf('<span>(%s | %s Past Due)</span>', $counters['past_due_count'], $counters['total']);
        }

        return json_encode(['counter' => $counter, 'type' => $request->input('type')]);
    }

    public function getPayrateApprovalsDashboard(Request $request) {
        if ($request->ajax()) {
            $data = H::getHelper("Payrate")->getPayrateApprovals($request, 0);
            return Datatables::of($data)
            ->filterColumn('patient_name', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) like ?", ["%{$keywords}%"]);
             })
             ->filterColumn('FacilityName', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw("msqlfac.FacilityName like ?", ["%{$keywords}%"]);
             })
             ->editColumn('GP', function($data) {
                return ($data->GP * 100) . '%';
            })
            ->editColumn('Agreed_Pay_Rate', function($data) {
                return ($data->Agreed_Pay_Rate > 0) ? '$'. $data->Agreed_Pay_Rate : '';
            })
            ->addColumn('Action', function ($row){
                $btn = '';
                $btn .= '<a title="Approve Rate" href="javascript:void(0)"
                onclick=\'getPayrateApprovalConfirmation("'.$row->id.'","'.
                $row->Agreed_Pay_Rate.'","'.($row->GP * 100).'","'.$row->Candidate.
                '");\' ><i class="fa fa-check-square-o" aria-hidden="true"></i></a>';
                return $btn;
            })
            ->addIndexColumn()->escapeColumns('Action')->make(true);
        }
    }

    public function getPayrateApprovalConfirmation(Request $request) {
        $requestData = $request->all();
        return view('recruiterTracker.payrateapprovalconfirmation',['requestData' => $requestData]);
    }

    public function reloadCountPayrateApproval(Request $request) {
        return json_encode(["counter"=>H::getHelper('Payrate')->getPayrateApprovals($request, 1)]);
    }

    public function savePayrateApproved(Request $request) {
        try {
            if ($request->input('id') != '') {
                DB::table("mssql_recruitertracker")->where("id", $request->input('id'))->update(['payrate_approved' => 1, 'payrate_approved_on' => date('Y-m-d H:i:s'), 'payrate_approved_by' => Auth::user()->id]);
                $noteData = [];
                $noteData['entity'] = 'tracker-activity';
                $noteData['entity_id'] = $request->input('id');
                $noteData['notes'] = Auth::user()->name . ' confirmed approval of $'.number_format($request->input('payrate'),2).' with GP of '.$request->input('gp').'% for '.$request->input('candidate');

                H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                return response()->json(["success" => true]);
            } else {
                return response()->json(['status' => false]);
            }
        }catch(\Exception $e){
            return response()->json(['status' => false]);
        }
    }

    public function profile()
    {
        return view('account.profile');
    }

    public function validateDate($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        return $d && $d->format($format) === $date;
    }

    /**
     * @param Request $request
     * @return array|JsonResponse
     */
    public function ajaxAllActivityData(Request $request): array|JsonResponse
    {
        try {
            return H::getHelper('Datatable')->ajaxDataResponse($request, 'mssql_recruitertracker');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getAllActivityData(Request $request)
    {
        $dataTable = H::getHelper('Datatable')->getConfiguration('mssql_recruitertracker');

        return view('account.allactivity', ['dataTable'=>$dataTable]);
    }
    public function excludePRN(Request $request){

        if(!empty($request->tracker_id)){

            $recruiterTracker['is_prn_confirmed']=1;
            $recruiterTracker['prn_confirmed_date']=date("Y-m-d H:i:s");
            $recruiterTracker['prn_confirmed_by']=Auth::user()->id;
            MssqlRecruitertracker::where('id', $request->tracker_id)->update($recruiterTracker);
            return response()->json(['status' => true]);
        } else {
            return response()->json(['status' => false]);
        }
    }

    public function getPRNConfirmationDialog(Request $request){
        $requestData = $request->all();
        return view('account.prnConfirmationPopup',['id'=>$requestData['id']]);
    }

    public function getActivityData(Request $request)
    {
        if ($request->ajax()) {
            //var_dump($request->all());
            $data = H::getHelper('Activity')->getActivityByStatus($request->input('type'), $request);

            $type = $request->input('type');

            return Datatables::of($data)
                ->with('counthours', function () use ($data, $type) {
                    $typeArray = ['IPEA' => 'IPEA_Date', 'CCRS' => 'Clinical_Competency_Review', 'PEPI' => 'PEPI_Date', 'WEPI' => 'WEPI_Date', 'PENDING CANDIDATE STARTS' => 'Candidate_Start_Date', 'CANDIDATE STARTS' => 'Candidate_Start_Date', 'TRAINING SHIFT' => 'Training_Shift', 'PRN' => 'Candidate_Start_Date', 'SCHOOL SETS UP' => 'School_Set_Up_Due_Date'];
                    $totalHr = number_format(array_sum($data->pluck('Total_Hours')->all()), 0);

                    if (isset($typeArray[$type])) {
                        $filtered_data = $data->filter(function ($data) use ($typeArray, $type) {
                            return date('Y-m-d', strtotime($data->{$typeArray[$type]})) < date('Y-m-d');
                        });
                        $pastDuetotalHr = number_format(array_sum($filtered_data->pluck('Total_Hours')->all()), 0);
                        return '<span>(' . $data->count() . ' | ' . $totalHr . ' <span style="text-transform: initial;">hrs</span>)</span>' . '<span style="margin-left: 12px">(' . $filtered_data->count() . ' | ' . $pastDuetotalHr . ' <span style="text-transform: initial;">hrs Past Due</span>)</span>';
                    } elseif ($type == 'NEED TO REVIEW NEW CLIENTS' || $type == 'NEED TO BE SCHEDULED BY RA') {
                        $filtered_data = $data->filter(function ($data) {
                            return round((strtotime(date('Y-m-d H:i:s')) - strtotime($data->Status_Changed_Date)) / 3600, 1) > 1;
                        });
                        $pastDuetotalHr = number_format(array_sum($filtered_data->pluck('Total_Hours')->all()), 0);
                        return '<span>(' . $data->count() . ' | ' . $totalHr . ' <span style="text-transform: initial;">hrs</span>)</span>' . '<span style="margin-left: 12px">(' . $filtered_data->count() . ' | ' . $pastDuetotalHr . ' <span style="text-transform: initial;">hrs Past Due</span>)</span>';
                    } else {
                        return '(' . $data->count() . ' | ' . $totalHr . ' <span style="text-transform: initial;">hrs</span>)';
                    }
                })
                ->setRowAttr([
                    'class' => function ($data) use ($type) {
                        $typeArray = ['IPEA' => 'IPEA_Date', 'PEPI' => 'PEPI_Date', 'WEPI' => 'WEPI_Date', 'PENDING CANDIDATE STARTS' => 'Candidate_Start_Date', 'CANDIDATE STARTS' => 'Candidate_Start_Date', 'TRAINING SHIFT' => 'Training_Shift', 'PRN' => 'Candidate_Start_Date', 'SCHOOL SETS UP' => 'School_Set_Up_Due_Date'];

                        if ($type === 'PRN' && LD::checkFeatureFlag('update-active-coverage-description')) {
                            return '';
                        }
                        if ($type != 'NEED TO REVIEW NEW CLIENTS' && $type != 'NEED TO BE SCHEDULED BY RA' && $type != 'CCRS') {
                            foreach ($typeArray as $k => $value) {
                                if ($k == $type && date('Y-m-d', strtotime($data->{$value})) < date('Y-m-d')) {
                                    return 'dashboard-older-record';
                                }
                            }
                            return '';
                        } elseif ($type == 'CCRS') {
                            return ((time() - strtotime($data->ccr_requested_date)) > 3600) ? "dashboard-older-record" : "";
                        } else {
                            $hourdiff = round((strtotime(date('Y-m-d H:i:s')) - strtotime($data->Status_Changed_Date)) / 3600, 1);
                            if ($hourdiff > 1) {
                                return 'dashboard-older-record-cl-ra';
                            } else {
                                return '';
                            }
                        }
                    },
                ])
                ->editColumn('attempt_count', function ($row) {
                    return (isset($row->attempt_count) && $row->attempt_count > 0) ? 'Attempt ' . $row->attempt_count : '';
                })
                ->addColumn('Candidate_Notes', function ($row) {
                    //$actionBtn='<a href="'.route("purchaseedit.edit",["id"=>$row["pe_id"],"prev"=>"true"]).'" class=""><i class="fa fa-eye"></i></a>';
                    //$actionBtn.= '&nbsp;<a href="'.route("purchaseedit.edit",["id"=>$row["pe_id"]]).'" class=""><i class="fa fa-pencil"></i></a>&nbsp;&nbsp;<a href="'.route("purchaseedit.delete",["id"=>$row["pe_id"]]).'" class="delete-item"><i class="fa fa-trash"></i></a>';
                    $str = '';
                    if (strlen($row->Candidate_Notes) > 35) {
                        $str = substr($row->Candidate_Notes, 0, 35) . '...';
                    } else {
                        $str = $row->Candidate_Notes;
                    }

                    return "<span data-toggle='tooltip' title='" . mb_convert_encoding($row->Candidate_Notes, 'UTF-8', 'UTF-8') . "'>" . mb_convert_encoding($str, 'UTF-8', 'UTF-8') . '</span>';

                })
                ->addColumn('Candidate_Start_Date', function ($row) {
                    return [
                        'display' => e(H::getFormatedDate($row->Candidate_Start_Date)),
                        'timestamp' => strtotime($row->Candidate_Start_Date)
                    ];
                })
                ->addColumn('Status_Changed_Date', function ($row) {
                    if (isset($row->Status_Changed_Date)) {
                        return [
                            'display' => e(H::getFormatedDate($row->Status_Changed_Date)),
                            'timestamp' => strtotime($row->Status_Changed_Date)
                        ];
                    }
                    return [
                        'display' => '-',
                        'timestamp' => '-',
                    ];
                })
                ->editColumn('ccrOutcome', function ($row) {
                    if (isset($row->notesCCROutcome) && $row->notesCCROutcome != '') {
                        $ccrOutcomStr = json_decode($row->notesCCROutcome, true);
                        if ($ccrOutcomStr['clinician_meeting'] == 'Yes') {
                            return 'Ready to Schedule Next Steps';
                        } else {
                            if (isset($row->notesDescription) && strpos($row->notesDescription, 'Next Step:Nurse decided not to work for AOC') !== false) {
                                return "No Longer Interested";
                            } else {
                                return 'Suggest Other Clients';
                            }
                        }
                    } else {
                        return isset($row->ccrOutcome) ? $row->ccrOutcome : '';
                    }
                })
                ->editColumn('ccr_requested_date', function ($row) {
                    if (!empty($row->ccr_requested_date)) {
                        return [
                            'display' => e(H::getFormatedDateTime($row->ccr_requested_date)),
                            'timestamp' => strtotime($row->ccr_requested_date)
                        ];
                    } else {
                        return [
                            'display' => '-',
                            'timestamp' => ''
                        ];
                    }
                })
                ->editColumn('proposed_ns_datetime', function ($row) {
                    if (!empty($row->proposed_ns_datetime)) {
                        return [
                            'display' => e(H::getFormatedDateTime($row->proposed_ns_datetime)),
                            'timestamp' => strtotime($row->proposed_ns_datetime)
                        ];
                    } else {
                        return [
                            'display' => '-',
                            'timestamp' => ''
                        ];
                    }
                })
                ->addColumn('Training_Shift', function ($row) {
                    if (isset($row->Training_Shift) && $this->validateDate($row->Training_Shift, 'Y-m-d H:i:s')) {
                        $date = new \DateTime($row->Training_Shift);
                        $formattedDate = $date->format('m/d/Y');
                        return $formattedDate;
                    } else {
                        return '-';
                    }
                })
                ->editColumn('IPEA_Date', function ($row) {
                    if (isset($row->IPEA_Date)) {
                        return [
                            'display' => e(H::getFormatedDate($row->IPEA_Date)),
                            'timestamp' => strtotime($row->IPEA_Date),
                        ];
                    }
                    return [
                        'display' => '-',
                        'timestamp' => '',
                    ];
                })
                ->addColumn('PEPI_Date', function ($row) {
                    if (isset($row->PEPI_Date) && $this->validateDate($row->PEPI_Date, 'Y-m-d H:i:s')) {
                        return [
                            'display' => e(H::getFormatedDate($row->PEPI_Date)),
                            'timestamp' => strtotime($row->PEPI_Date)
                        ];
                    } else {
                        return [
                            'display' => '-',
                            'timestamp' => '',
                        ];
                    }
                })
                ->addColumn('Bonus_Paid_Date', function ($row) {
                    if (isset($row->Bonus_Paid_Date)) {
                        return [
                            'display' => e(H::getFormatedDate($row->Bonus_Paid_Date)),
                            'timestamp' => strtotime($row->Bonus_Paid_Date),
                        ];
                    }
                    return [
                        'display' => '-',
                        'timestamp' => '-',
                    ];
                })
                ->addColumn('PEPI_Time', function ($row) {
                    if (isset($row->PEPI_Time)) {
                        return H::getFormatedTime($row->PEPI_Time);
                    }
                    return '';
                })
                ->addColumn('IPEA_Time', function ($row) {
                    if (isset($row->IPEA_Time)) {
                        return H::getFormatedTime($row->IPEA_Time);
                    }
                    return '';
                })
                ->addColumn('WEPI_Time', function ($row) {
                    if (isset($row->WEPI_Time)) {
                        return H::getFormatedTime($row->WEPI_Time);
                    }
                    return '';
                })
                ->addColumn('Candidate_Start_Time', function ($row) {
                    if (isset($row->Candidate_Start_Time)) {
                        return H::getFormatedTime($row->Candidate_Start_Time);
                    }
                    return '';
                })
                ->addColumn('Training_Shift_Time', function ($row) {
                    if (isset($row->Training_Shift_Time)) {
                        return H::getFormatedTime($row->Training_Shift_Time);
                    }
                    return '';
                })
                ->addColumn('WEPI_Date', function ($row) {
                    if (isset($row->WEPI_Date) && $this->validateDate($row->WEPI_Date, 'Y-m-d H:i:s')) {
                        $date = new \DateTime($row->WEPI_Date);
                        $formattedDate = $date->format('m/d/Y');
                        return $formattedDate;
                    } else {
                        return '-';
                    }
                })
                ->addColumn('School_Set_Up_Due_Date', function ($row) {
                    if (isset($row->School_Set_Up_Due_Date) && $this->validateDate($row->School_Set_Up_Due_Date, 'Y-m-d H:i:s')) {
                        $date = new \DateTime($row->School_Set_Up_Due_Date);
                        $formattedDate = $date->format('m/d/Y');
                        return $formattedDate;
                    } else {
                        return '-';
                    }
                })
                ->addColumn('application_date', function ($row) {
                    if (isset($row->application_date)) {
                        return [
                            'display' => e(H::getFormatedDate($row->application_date)),
                            'timestamp' => strtotime($row->application_date)
                        ];
                    }
                    return [
                        'display' => '-',
                        'timestamp' => '',
                    ];
                })
                ->addColumn('created_at', function ($row) {
                    if (isset($row->created_at)) {
                        // if ($this->validateDate($row['created_at'], 'Y-m-d H:i:s')) {

                        $date = new \DateTime($row->created_at);
                        $formattedDate = $date->format('m/d/Y');

                        return $formattedDate;
                    }
                    return '';
                })
                ->addColumn('Action', function ($row) use ($request) {
                    $btn = '';

                    if ($request->input('type') != 'PRN') {
                        $btn .= '<a href="javascript:void(0)" onclick="showScheduleForm(\'' . trim($row->id) . '\', \'' . $request->input('type') . '\');" ><i class="fa fa-calendar" aria-hidden="true"></i></a> | ';
                    }

                    if (!LD::checkFeatureFlag('add-outcome-to-recruiting-active-coverage-bucket')) {
                        if ($request->input('type') == 'PRN') {
                            $bonusPaidDate = strtotime(date('Y-m-d', strtotime($row->Bonus_Paid_Date)));
                            $today = strtotime(date("Y-m-d"));
                            if (($row->Hours_Worked >= 40) && ($bonusPaidDate <= $today)) {
                                $btn .= '<a href="javascript:void(0)" onclick="showConfirmationPRN(\'' . trim($row->id) . '\');" ><i class="fa fa-check-square-o" aria-hidden="true"></i></a> | ';
                            }

                        }
                    }

                    if (LD::checkFeatureFlag('make-ssu-form-view-only')) {
                        if ($request->input('type') == 'SCHOOL SETS UP') {
                            if (!empty($row->aoc_patient_id) && !empty($row->school_setup_id)) {
                                $btn .= '<a href="javascript:void(0);"
                                    onclick="loadSchoolSetupForm(\'' . $row->aoc_patient_id . '\', \'' . $row->school_setup_id . '\', \'view\')">
                                    <i class="fa fa-paperclip"></i>
                                </a> | ';
                            }
                        }
                    }

                    $btn .= '<a href="javascript:void(0)" onclick="openRecruiterDetails(\'' . trim($row->id) . '\');" ><i class="fa fa-eye" aria-hidden="true"></i></a>';
                    if ($request->input('type') == 'IPEA') {
                        if (isset($row->linked_IPEA) && $row->linked_IPEA != '' && $row->linked_IPEA != null) {
                            $btn .= '<a href="javascript:void(0)" onclick="updateNurseIpeaInfo(' . trim($row->linked_IPEA) . ');" ><i class="fa fa-vcard" aria-hidden="true"></i></a>';
                        }
                    }
                    if (isset($row->id) && $row->id != '') {
                        $btn .= ' | <a data-bs-dismiss="modal" href="javascript:void(0);" onclick="event.preventDefault();openNotes(' . $row->id . ',\'tracker\',\'Recruiter Tracker Notes Added\')"><i class="fa  fa-sticky-note-o"></i></a>';
                    }

                    if (LD::checkFeatureFlag('conversation-icon-on-tracker')) {
                        if ($request->input('type') == 'TRAINING SHIFT') {
                            if ($row->aoc_provider_id != 0) {
                                $btn .= '<a href="javascript:void(0);" data-candidate-name="' . $row->fullName . '" data-candidate-id="' . $row->aoc_provider_id . '" data-candidate-phone="' . $row->mobile . '"  id="startConversationWithClinicianBtn" >
                                <i class="fa fa-comment-o"></i></a>';
                            } else {
                                $btn .= '<a href="javascript:void(0);" data-candidate-name="' . $row->fullName . '" data-candidate-id="' . $row->aoc_provider_id . '" data-candidate-phone="' . $row->mobile . '"  id="startConversationWithCandidateBtn" >
                                <i class="fa fa-comment-o"></i></a>';
                            }


                        }
                    }
                    if (LD::checkFeatureFlag('prn-process-active-coverage-bucket-fixes')) {
                        if (!LD::checkFeatureFlag('add-outcome-to-recruiting-active-coverage-bucket')) {
                            if ($request->input('type') == 'PRN') {
                                $btn .= '<a href="javascript:void(0)" onclick="showScheduleForm(\'' . trim($row->id) . '\', \'' . $request->input('type') . '\');" ><i class="fa fa-trash" aria-hidden="true"></i></a> | ';

                            }
                        }
                    }

                    if (LD::checkFeatureFlag('add-outcome-to-recruiting-active-coverage-bucket')) {
                        if ($request->input('type') == 'PRN') {
                            $btn .= '| <a href="javascript:void(0)" onclick="showScheduleForm(\'' . trim($row->id) . '\', \'' . $request->input('type') . '\');" ><i class="fa fa-calendar" aria-hidden="true"></i></a>';
                        }
                    }


                    return $btn;
                })
                ->addIndexColumn()
                ->escapeColumns('Candidate_Notes')->make(true);
        }
    }

    public function dashboard(Request $request, JobCategoriesReadRepository $jobCategoriesReadRepository, ConstantHelper $helper)
    {
        return view('account.dashboard', [
            'isRecruiter' => $helper->getIsUserInRole('Recruiter', Auth::id()),
            'isTherapyClinicalManager' => $helper->getIsUserInRole('Therapy Clinical Manager', Auth::id()),
            'nursesCategories' => $jobCategoriesReadRepository->getJobCategoriesFilteredByKeyword(JobCategoriesReadRepository::NURSES_CATEGORIES),
            'therapyCategories' => $jobCategoriesReadRepository->getJobCategoriesFilteredByKeyword(JobCategoriesReadRepository::THERAPY_CATEGORIES),
        ]);
    }

    public function profilesave(Request $request)
    {
        if (H::isLoggedIn()) {
           // echo "<pre>"; print_r($request->all());exit;
            $validator = Validator::make($request->all(), [
                'profile_picture' => 'mimes:jpeg,jpg|max:2048|dimensions:min_width=300,min_height=300,max_width=800,max_height=800',
            ], [
                'profile_picture.max' => 'Max allowed size is up to 2MB'
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
            $requestData = $request->all();
            $loggedInUser = H::getLoggedInUser();
            $data = [];
            if (isset($requestData['name'])) {
                $data['name'] = $requestData['name'];
            }

            if(LD::checkFeatureFlag('add-bilingual-on-user-page')) {
                $data['bilingual'] = $requestData['bilingual'] ?? null;
            }
            if (isset($requestData['title'])) {
                $data['title'] = $requestData['title'];
            }
            $data['signature'] = $requestData['signature'];
            if (!empty($requestData['password'])) {
                $validator = Validator::make($request->all(), [
                    'password' => ['min:8', 'max:64', 'required_with:confirm-password', 'same:confirm-password', new IsValidPassword()],
                ]);
                if ($validator->fails()) {
                    return back()->withErrors($validator)->withInput();
                }
            }
            $file = $request->file('profile_picture');
            $timestamp = time();
            if (!empty($file)) {
                $destinationPath = storage_path() . '/user_profile';
                $file->move($destinationPath, $timestamp . '_' . $file->getClientOriginalName());
                $data['profile_picture'] = $timestamp . '_' . $file->getClientOriginalName();
            }

            if (isset($requestData['user_timezone'])) {
                $validator = Validator::make($request->all(), [
                    'user_timezone' => ['required'],
                ]);
                if ($validator->fails()) {
                    return back()->withErrors($validator)->withInput();
                }
                $data['user_timezone'] = $requestData['user_timezone'];
            }

            DB::table('users')
                ->where('email', $loggedInUser['email'])
                ->limit(1)
                ->update($data);

            return redirect(route('users.profile'))->withSuccess('Profile Updated Successfully.');
        }
    }

    public function saveScheduleNext(
        Request $request,
        FirstAdvantageService $advantageService,
        NotesWriteRepository $notesWriteRepository,
        TrackerHoursEligibilityService $trackerHoursEligibilityService,
        CrossStateHelper $crossStateHelper
    ) {
        if (LD::checkFeatureFlag('moved-tracker-functions')) {
            H::ExcelLog('ERROR!!!!! code is in Dashboard.php saveScheduleNext','updateTracker.log');
            echo 'ERROR!!!!! code is in Dashboard.php saveScheduleNext';
            die();
        }

        if($request->status=="current" && empty($request->provider_id)) {
            return response()->json(['error'=>10, 'message'=>'You must select a clinician from the drop down list if \'Current\' is selected']);
        }
        if(($request->status=="new" || $request->status=="current") && empty($request->patient_id)) {
            H::ExcelLog('ERROR!!!!! Patient Id is blank','updateTracker.log');
            return response()->json(['error'=>10, 'message'=>'There is something wrong. Please select the client again']);
        }
        if(isset($request->nth_lead_bucket_id) && $request->nth_lead_bucket_id != ''){
            $lead_id = explode('##',$request->nth_lead_bucket_id)[1];
            H::updateLeadData($lead_id);
        }
        H::ExcelLog("new Request from " . Auth::user()->name,'updateTracker.log');
        H::ExcelLog(print_r($request->all(),true),'updateTracker.log');
		$trackerId = $request->tracker_id;
        $linkedIPEA = $request->linked_IPEA;
		$candidate_id = $request->candidate_id;
        $appointment_date = '';
        $appointment_time = '';
        $originalNote = '';
        if (!empty($request->appointment_date)) {
            $appointment_date = date('Y-m-d', strtotime($request->appointment_date));
        }
        if (!empty($request->appointment_time)) {
            $appointment_time = date('H:i', strtotime($request->appointment_time));
        }
        H::ExcelLog('appt date: ' . $appointment_date, 'new_candidate.log');
        $gp = 0;
        if(isset($request->gp) && $request->gp != ''){
            $gp = floatval(str_replace('%', '', $request->gp)) / 100;
        }
        $scheduleTypes = DB::table('schedule_types')->where('type', $request->schedule_type)->first();
        $scheduleTypes_id = 0;
        if (!empty($scheduleTypes)) {
            $scheduleTypes_id = $scheduleTypes->id;
        }
        $activityNote = '';
        $whiteBoard = '';
        $skillSet = '';
        $userId = '';
        $recruiterName = '';
        $skipScheduling = 0;
        $needsCCR = 0;
        if ($request->mode == 'Edit') {
            if (LD::checkFeatureFlag('schedule-next-step-pending-outcomes')
                && RecruitingBucket::supportsPendingOutcomes(strtoupper($request->schedule_type))
            ) {
                $pendingOutcomes = ScheduleNextStepOutcomesEnum::getPendingOutcomes();

                if (in_array($request->outcome, $pendingOutcomes) && !empty($request->pending_reason)) {
                    $request->merge([
                        'outcome' => $request->outcome . ': ' . $request->pending_reason
                    ]);
                }
            }

            H::ExcelLog('Mode: Edit','updateTracker.log');
            $activityNote = $request->note;
            $recruitertracker = MssqlRecruitertracker::where('id', $trackerId)->with('trackerActivityLog')->first();
            $isInactive = $recruitertracker->isInactive();
            $isAutoInactive = $recruitertracker->isAutoInactive();

            if (LD::checkFeatureFlag('auto-inactivate-recruiter-trackers')) {
                if ($request->get('needs_to_review_expanded') === 'true' && ($isInactive || $isAutoInactive)) {
                    $msg = 'This tracker has recently been inactivated by another user and can no longer be updated.';
                    if ($isAutoInactive) {
                        $msg = 'This tracker has recently been auto-inactivated and can no longer be updated.';
                    } else {
                        $notesWriteRepository->create(
                            new NotesDTO(
                                'tracker-activity',
                                $recruitertracker->id,
                                'edit',
                                'Tried to change inactive tracker status by ' . Auth::id(),
                                now(),
                            )
                        );
                    }

                    return response()->json(['error' => 'inactive', 'message' => $msg], Response::HTTP_CONFLICT);
                }
            }

            $aocPatientId = $recruitertracker->aoc_patient_id;
            H::ExcelLog('aocPatientId: ' . $aocPatientId,'updateTracker.log');
            $aocProviderId = $recruitertracker->aoc_provider_id;
            $aocCandidateId = $recruitertracker->aoc_candidate_id;
            $aocFacilityId = $recruitertracker->aoc_facility_id;
            $originalNote = $recruitertracker->Candidate_Notes;
            $skillSet = $recruitertracker->Skillset;
            $userId = $recruitertracker->user_id;
            $recruiterName = $recruitertracker->Recruiter;
            //see if we need to schedule a CCR
            //see if an IPEA has already been scheduled
            $relatedIPEA = RecruitingSchedule::where(['type_id' => 1, 'aoc_patient_id' => $request->patient_id, 'tracker_id' => $trackerId])->first();
            if (!$relatedIPEA) {
                //see if a CCR has already been scheduled
                $lastScheduleData = RecruitingSchedule::where(['type_id' => 2, 'aoc_patient_id' => $request->patient_id, 'tracker_id' => $trackerId])->orderBy('created_at', 'desc')->first();
                if (!isset($lastScheduleData->id)) {
                    H::ExcelLog('Mode: found1','updateTracker.log');
                    //make sure nothing else has already been scheduled (so we don't create a CCR when they scheduled something else before we went live)
                    $lastScheduleData = DB::table('recruiting_schedule')->where('tracker_id',$trackerId)->where('aoc_patient_id',$request->patient_id)->where('created_at','<','2023-03-27')->first();
                    if(!isset($lastScheduleData->id)) {
                        H::ExcelLog('Mode: found2','updateTracker.log');
                        //there were no appointments scheduled before 3/27 . . so we need a CCR
                        $needsCCR = 1;
                    }
                }else{
                    H::ExcelLog('test:  ' . print_r($lastScheduleData,true),'updateTracker.log');
                }
            }else{
                H::ExcelLog('ipea found','updateTracker.log');
            }
            //get schedule type for what schedule to update with outcome (based on the status/bucket)
            $cbucket = $recruitertracker->Candidate_Current_Status;

            H::ExcelLog('bucket: ' . $cbucket,'updateTracker.log');

            $schedType = '';
            if ($cbucket == 'IPEA - Scheduled') {
                $schedType = 'IPEA';
            }
            if ($cbucket == 'PEPI - Scheduled') {
                $schedType = 'PEPI';
            }
            if ($cbucket == 'WEPI - Scheduled') {
                $schedType = 'WEPI';
            }
            if ($cbucket == 'Training Shift Scheduled') {
                $schedType = 'Training Shift';
            }
            if ($cbucket == 'Client Match') {
                $schedType = 'Candidate Start';
            }
            $schedTypesId = 0;
            $schedTypes = DB::table('schedule_types')->where('type', $schedType)->first();
            if (!empty($schedTypes)) {
                $schedTypesId = $schedTypes->id;
            }


            $provider = MssqlProviders::find($request->get('provider_id'));
            $isInsideInactiveBuckets = in_array($cbucket, ['PEPI - Scheduled', 'WEPI - Scheduled', 'Training Shift Scheduled']);

            if (
                ($request->get('outcome') === 'Client Match' && $isInsideInactiveBuckets)
                || ($cbucket === 'IPEA - Scheduled' && $request->get('schedule_type') === 'Candidate Start')
            ) {
                $provider?->update(['SMS_Status' => 'Inactive - Case Assignment']);
            }

            if ($request->get('outcome') === 'First Shift Worked' && $cbucket === 'Client Match') {
                $trackerWithAverage = DB::table('mssql_providers as mrt')
                    ->leftjoin('maps_nthalerts as mt', 'mt.ClinicianID', '=', 'mrt.ClinicianID')
                    ->where('mrt.id', $request->get('provider_id'))
                    ->select(
                        'mrt.id',
                        'mrt.ProviderID',
                        DB::raw('CAST(mt.WeeklyAverage AS DECIMAL(5,2)) as weekly_average')
                    )
                    ->first();
                $upcomingShifts = DB::table('mssql_schedulemaster')
                    ->where('ScheduleStatusID', 5)
                    ->where('IsBillable', 1)
                    ->where('PlannedStart', '>', now())
                    ->where('ProviderID', $request->get('provider_id'))
                    ->count();
                $weeklyAverage = (float) ($trackerWithAverage->weekly_average ?? 0);

                if ($upcomingShifts > 0 || $weeklyAverage >= 12) {
                    $provider?->update(['SMS_Status' => 'Inactive - Case Assignment']);
                } else {
                    $provider?->update(['SMS_Status' => 'Active']);
                }
            }

            H::ExcelLog('last Sched Type ID: ' . $schedTypesId,'updateSchedules.log');
            $updateRecruiter = [];
            $updateRecruiter['aoc_patient_id'] = $aocPatientId;
            if (LD::checkFeatureFlag('save-reason-in-recruiting-schedule')) {
                $lastScheduleData = RecruitingSchedule::where([
                    'type_id' => $schedTypesId,
                    'tracker_id' => $trackerId
                ])->orderBy('created_at', 'desc')
                ->first();
                if ($lastScheduleData) {
                    $scheduleData['outcome'] = $request->outcome;
                    $scheduleData['updated_at'] = date('Y-m-d H:i:s');
                    $scheduleData['updated_by'] = Auth::user()->id;
                    RecruitingSchedule::where('id', $lastScheduleData->id)->update($scheduleData);
                }
            }
            if (in_array($request->outcome, ['NC/NS', 'Pending Nurse', 'Pending Parent'])) {

                $lastScheduleData = RecruitingSchedule::where(['type_id' => $schedTypesId, 'tracker_id' => $trackerId])->orderBy('created_at', 'desc')->first();

                if ($lastScheduleData) {
                    if ($request->attampted_to_contact == 1) {
                        $scheduleData['attempt_count'] = ($lastScheduleData->attempt_count + 1);
                    }
                    $scheduleData['outcome'] = $request->outcome;
                    $scheduleData['updated_at'] = date('Y-m-d H:i:s');
                    $scheduleData['updated_by'] = Auth::user()->id;

                    H::ExcelLog('updating sched: ' . $lastScheduleData->id . ' - ' . print_r($scheduleData,true),'updateSchedules.log');
                    RecruitingSchedule::where('id', $lastScheduleData->id)->update($scheduleData);
                }
            } elseif ($request->outcome=='Cancelled' && $schedType == 'IPEA'){
                $reason = 'TBD';
                $lastScheduleData = RecruitingSchedule::where(['type_id' => $schedTypesId, 'tracker_id' => $trackerId])->orderBy('created_at', 'desc')->first();
                if ($lastScheduleData) {
                    $scheduleData['outcome'] = $request->outcome;
                    $scheduleData['updated_at'] = date('Y-m-d H:i:s');
                    $scheduleData['updated_by'] = Auth::user()->id;
                    $scheduleData['ipea_status'] = self::IPEA_STATUS;
                    $scheduleData['IPEA_clinician_arrived'] = self::IPEA_CANCELLED;
                    if (LD::checkFeatureFlag('save-reason-in-recruiting-schedule')) {
                        $cancledReason = '';
                        switch($request->cancelled_reason) {
                            case 'better_offer':
                                $cancledReason = 'Received better job offer';
                                break;
                            case 'not_work':
                                $cancledReason = 'Nurse decided not to work for AOC';
                                break;
                            case 'change_prn':
                                $cancledReason = 'Change to Active Coverage';
                                break;
                            case 'different_recruiter':
                                $cancledReason = 'Placed on case by different recruiter';
                                break;
                            case 'ineligible_for_hire':
                                $cancledReason = 'Ineligible for hire/rehire';
                                break;
                        }
                        $scheduleData['outcome_reason'] = $cancledReason;
                    }
                    H::ExcelLog('updating sched: ' . $lastScheduleData->id . ' - ' . print_r($scheduleData,true),'updateSchedules.log');
                    RecruitingSchedule::where('id', $lastScheduleData->id)->update($scheduleData);
                }
                H::ExcelLog('Ipea cancelled - Inactivate on tracker','updateTracker.log');
                $updateRecruiter['Candidate_Current_Status'] = 'Inactive';
                $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                $updateRecruiter['inactive_reason'] = $reason;
                //$activityNote = 'Candidate set to Inactive - ' . $reason;
                $noteMsg = 'Ipea Cancelled - ' . $request->cancelled_reason . PHP_EOL;
                $noteMsg .= $request->note . PHP_EOL;
                $noteMsg .= 'Candidate set to Inactive.';
                $activityNote = $noteMsg;
                if (LD::checkFeatureFlag('ipea-received-better-job')) {
                    if ($request->cancelled_reason == 'better_offer') {
                        $updateRecruiter['better_offer_type'] = $request->better_offer_type;
                        $updateRecruiter['better_offer_rate'] = $request->better_offer_rate;

                        $noteMsg1 = 'Ipea Cancelled - Received better job offer.' . PHP_EOL;
                        $noteMsg1 .= $request->better_offer_type . ' - ' . $request->better_offer_rate . PHP_EOL;
                        $noteMsg1 .= $request->note . PHP_EOL;
                        $noteMsg1 .= 'Candidate set to Inactive.';
                        $activityNote = $noteMsg1;
                    }
                }

                if ($request->cancelled_reason == 'change_prn') {
                    if (LD::checkFeatureFlag('active-coverage-prn-branch')) {
                        $updateRecruiter['Branch_Location'] = $request->prn_branch;
                    }
                    $updateRecruiter['Candidate_Current_Status'] = 'PRN';
                }

                if ($request->cancelled_reason == 'ineligible_for_hire') {
                    $updateRecruiter['Candidate_Current_Status'] = 'Inactive';
                    $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');

                    $noteMsg1 = 'Ipea Cancelled - Ineligible for hire/rehire.' . PHP_EOL;
                    $noteMsg1 .= $request->note . PHP_EOL;
                    $noteMsg1 .= 'Candidate set to Inactive.';
                    $activityNote = $noteMsg1;
                }
            } elseif (in_array($request->outcome, ['Cancelled', 'Nurse Declined', 'Parent Declined', 'Client authorization denied',
                        'Client/caregiver declined services', 'Client hospitalized - nurse discussing other cases', 'Nurse declined case - nurse discussing other cases', ])) {
                $lastScheduleData = RecruitingSchedule::where(['type_id' => $schedTypesId, 'tracker_id' => $trackerId])->orderBy('created_at', 'desc')->first();
                if ($lastScheduleData) {
                    $scheduleData['outcome'] = $request->outcome;
                    $scheduleData['updated_at'] = date('Y-m-d H:i:s');
                    $scheduleData['updated_by'] = Auth::user()->id;
                    if (LD::checkFeatureFlag('save-reason-in-recruiting-schedule')) {
                        $cancledReason = '';
                        switch($request->cancelled_reason) {
                            case 'better_offer':
                                $cancledReason = 'Received better job offer';
                                break;
                            case 'not_work':
                                $cancledReason = 'Nurse decided not to work for AOC';
                                break;
                            case 'change_prn':
                                $cancledReason = 'Change to Active Coverage';
                                break;
                            case 'different_recruiter':
                                $cancledReason = 'Placed on case by different recruiter';
                                break;
                            case 'ineligible_for_hire':
                                $cancledReason = 'Ineligible for hire/rehire';
                                break;
                        }
                        $scheduleData['outcome_reason'] = $cancledReason;
                    }
                    H::ExcelLog('updating sched: ' . $lastScheduleData->id . ' - ' . print_r($scheduleData,true),'updateSchedules.log');
                    RecruitingSchedule::where('id', $lastScheduleData->id)->update($scheduleData);
                }
                //now update RT table
                $reason = $request->cancelled_reason;
                if ($reason == 'new_client' || $reason == '') {
                    H::ExcelLog('reason: new_client or empty','updateTracker.log');
                    $updateRecruiter['aoc_patient_id'] = 0;
                    //$updateRecruiter['aoc_facility_id'] = 0;
                    $updateRecruiter['Client_Name'] = '';
                    $updateRecruiter['Client_ID'] = '';
                    $updateRecruiter['Payor_Source'] = '';
                    $updateRecruiter['Agreed_Pay_Rate'] = '';
                    $updateRecruiter['GP'] = '';
                    $updateRecruiter['Total_Hours'] = '';
                    $updateRecruiter['Approved_By'] = '';
                    $updateRecruiter['PatientID'] = '';
                    //$updateRecruiter['aoc_facility_id'] = 0;
                    //$aocFacilityId = 0;
                    //$updateRecruiter['Branch_Location'] = '';
                    $updateRecruiter['Candidate_Current_Status'] = 'Need to Review New Clients';
                    $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                    $updateRecruiter['Client_Match_Date'] = null;
                    $updateRecruiter['Candidate_Start_Date'] = null;
                } elseif ($reason == 'different_recruiter') {
                    $updateRecruiter['inactive_reason'] = 'Placed on case by different recruiter';
                    $updateRecruiter['Candidate_Current_Status'] = 'Inactive';
                    $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                    $activityNote = 'Candidate set to Inactive - Placed on case by different recruiter';
                    $updateRecruiter['First_Shift_Not_Worked'] = $request->outcome;
                } else {
                    $updateRecruiter['inactive_reason'] = 'Nurse decided not to work for AOC';
                    $updateRecruiter['Candidate_Current_Status'] = 'Inactive';
                    $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                    $activityNote = 'Candidate set to Inactive - Nurse decided not to work for AOC';
                    if (in_array($request->outcome, ['Client authorization denied', 'Client/caregiver declined services', 'Nurse declined case - nurse discussing other cases', 'Nurse declined case - nurse discussing other cases'])) {
                        $updateRecruiter['First_Shift_Not_Worked'] = $request->outcome;
                    }
                }
                if(LD::checkFeatureFlag('reason-nurse-declined')){
                    //adding reason when outcome is "Nurse Declined"
                    if(isset($request->reason_nurse_declined) && $request->reason_nurse_declined != ''){
                        $updateRecruiter['Reason_Nurse_Declined_AOC'] = $request->reason_nurse_declined;
                    }
                }

                if ($request->cancelled_reason == 'change_prn') {
                    if (LD::checkFeatureFlag('active-coverage-prn-branch')) {
                        $updateRecruiter['Branch_Location'] = $request->prn_branch;
                    } else {
                        $updateRecruiter['aoc_patient_id'] = $request->PRN_PATIENT_ID;
                        $updateRecruiter['Client_ID'] = $request->PRN_Client_ID;
                    }

                    $updateRecruiter['Candidate_Current_Status'] = 'PRN';
                    //Change inactive reason and activity not for change PRN
                    $updateRecruiter['inactive_reason'] = '';
                    $activityNote = 'Candidate set to PRN';
                }

                if ($request->cancelled_reason == 'ineligible_for_hire') {
                    $updateRecruiter['Candidate_Current_Status'] = 'Inactive';
                    $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');

                    $noteMsg1 = $schedType . ' Cancelled - Ineligible for hire/rehire.' . PHP_EOL;
                    $noteMsg1 .= $request->note . PHP_EOL;
                    $noteMsg1 .= 'Candidate set to Inactive.';
                    $activityNote = $noteMsg1;
                }

                if(LD::checkFeatureFlag('add-new-field-reason-when-nurse-declines')){
                    if ($request->outcome == 'Nurse Declined' && isset($request->nurseDeclineReason) && $request->nurseDeclineReason != '') {
                        H::ExcelLog('Nurse Declined for reason: '.$request->nurseDeclineReason,'updateTracker.log');
                        $noteData = [];
                        $noteData['entity'] = 'tracker-activity';
                        $noteData['entity_id'] = $trackerId;
                        $noteData['notes'] ='Nurse Declined for reason: '.$request->nurseDeclineReason . '<br />' . $request->note;
                        H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                        $activityNote = '';
                    }
                }

            } elseif ($request->outcome == 'Inactivate on tracker') {
                $lastScheduleData = RecruitingSchedule::where(['type_id' => $schedTypesId, 'tracker_id' => $trackerId])->orderBy('created_at', 'desc')->first();
                if ($lastScheduleData) {
                    $scheduleData['outcome'] = $request->outcome;
                    $scheduleData['updated_at'] = date('Y-m-d H:i:s');
                    $scheduleData['updated_by'] = Auth::user()->id;
                    H::ExcelLog('updating sched: ' . $lastScheduleData->id . ' - ' . print_r($scheduleData,true),'updateSchedules.log');
                    RecruitingSchedule::where('id', $lastScheduleData->id)->update($scheduleData);
                }
                //now update RT table
                $reason = $request->inactivate_reason;
                H::ExcelLog('Inactivate on tracker','updateTracker.log');

                $updateRecruiter['aoc_patient_id'] = 0;
                //$updateRecruiter['aoc_facility_id'] = 0;
                $updateRecruiter['Client_Name'] = '';
                //$updateRecruiter['Branch_Location'] = '';
                $updateRecruiter['Client_ID'] = '';
                $updateRecruiter['Payor_Source'] = '';
                $updateRecruiter['Agreed_Pay_Rate'] = '';
                $updateRecruiter['GP'] = '';
                $updateRecruiter['Total_Hours'] = '';
                $updateRecruiter['Approved_By'] = '';
                $updateRecruiter['PatientID'] = '';
                $updateRecruiter['Candidate_Current_Status'] = 'Inactive';
                $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                $updateRecruiter['inactive_reason'] = $reason;
                $activityNote = 'Candidate set to Inactive - ' . $reason;
            } elseif ($request->outcome == 'Inactivate Tracker') {
                $lastScheduleData = RecruitingSchedule::where(['type_id' => $schedTypesId, 'tracker_id' => $trackerId])->orderBy('created_at', 'desc')->first();
                if ($lastScheduleData) {
                    $scheduleData['outcome'] = $request->outcome;
                    $scheduleData['updated_at'] = date('Y-m-d H:i:s');
                    $scheduleData['updated_by'] = Auth::user()->id;
                    H::ExcelLog('updating sched: ' . $lastScheduleData->id . ' - ' . print_r($scheduleData,true),'updateSchedules.log');
                    RecruitingSchedule::where('id', $lastScheduleData->id)->update($scheduleData);
                }
                $updateRecruiter['aoc_patient_id'] = 0;
                //$updateRecruiter['aoc_facility_id'] = 0;
                $updateRecruiter['Client_Name'] = '';
                //$updateRecruiter['Branch_Location'] = '';
                $updateRecruiter['Client_ID'] = '';
                $updateRecruiter['Payor_Source'] = '';
                $updateRecruiter['Agreed_Pay_Rate'] = '';
                $updateRecruiter['GP'] = '';
                $updateRecruiter['Total_Hours'] = '';
                $updateRecruiter['Approved_By'] = '';
                $updateRecruiter['PatientID'] = '';
                $updateRecruiter['Candidate_Current_Status'] = 'Inactive';
                $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                $updateRecruiter['inactive_reason'] = '';
                $updateRecruiter['activecoverage_outcome'] = 'Inactive';

                $loggedInUser = H::getLoggedInUser();
                $noteData = [];
                $noteData['entity'] = 'tracker-activity';
                $noteData['entity_id'] = $trackerId;
                $noteData['notes'] = $loggedInUser['name'] . " moved tracker to inactive Notes: ". $request->note;
                H::getHelper("ActivityNotes")->setActivityNotes($noteData);
            } elseif ($request->outcome == 'No longer interested in Active Coverage - move to needs to review') {
                $updateRecruiter['activecoverage_outcome'] = 'No longer interested in Active Coverage - move to needs to review';
                $loggedInUser = H::getLoggedInUser();
                $updateRecruiter['Candidate_Current_Status'] = 'Need to Review New Clients';
                $noteText = $loggedInUser['name'] . " moved tracker to needs to review new clients. Notes: " . $request->note;
                H::getHelper('ActivityNotes')->setActivityNotes([
                    'entity' => 'tracker-activity',
                    'entity_id' => $trackerId,
                    'notes' => $noteText
                ]);
            } else if ($request->outcome == 'Confirm Completed') {
                $updateRecruiter['is_prn_confirmed']=1;
                $updateRecruiter['prn_confirmed_date']=date("Y-m-d H:i:s");
                $updateRecruiter['prn_confirmed_by']=Auth::user()->id;
                $updateRecruiter['activecoverage_outcome'] = 'Confirm Completed';
                $loggedInUser = H::getLoggedInUser();
                $noteText = $loggedInUser['name'] . " moved tracker to completed after bonus was paid to recruiter. Notes " . $request->note;
                H::getHelper('ActivityNotes')->setActivityNotes([
                    'entity' => 'tracker-activity',
                    'entity_id' => $trackerId,
                    'notes' => $noteText
                ]);
            } elseif ($request->outcome == 'Schedule Next Step' || $request->outcome == 'Change Client') {

                if (empty($request->patient_id)) {
                    H::ExcelLog('Patient Id is blank on Schedule Next Step/Change Client','updateTracker.log');
                    return response()->json(['error' => 'missing_client', 'message' => 'Please select the client again'], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                H::ExcelLog('Schedule Next Step: ' . $request->patient_id,'updateTracker.log');
                $updateRecruiter['aoc_patient_id'] = $request->patient_id;
                $aocPatientId = $request->patient_id;
                //get patient to pre-populate other details
                $patientDetails = MssqlPatients::find($aocPatientId);



                if(!empty($patientDetails)){
                    $updateRecruiter['Client_Name'] = $patientDetails->Patient_FirstName . ' ' . $patientDetails->Patient_LastName;
                    $updateRecruiter['Client_ID'] = $patientDetails->PatientAccount;
                    $updateRecruiter['Payor_Source'] = $patientDetails->Payer;
                    $updateRecruiter['aoc_facility_id'] = $patientDetails->aoc_facility_id;
                    $aocFacilityId = $patientDetails->aoc_facility_id;
                    $facilityDetails = MssqlFacilities::find($aocFacilityId);
                    if(isset($facilityDetails->FacilityName)) {
                        $updateRecruiter['Branch_Location'] = $facilityDetails->FacilityName;
                    }
                }
                if(isset($request->pay_rate) && $request->pay_rate != ''){
                    $updateRecruiter['Agreed_Pay_Rate'] = $request->pay_rate;
                }
                if(isset($request->gp) && $request->gp != '' && $gp > 0){
                    $updateRecruiter['GP'] = $gp;
                }
                if(isset($request->gp_approved_by) && $request->gp_approved_by != ''){
                    $updateRecruiter['Approved_By'] = $request->gp_approved_by;
                }
                if (LD::checkFeatureFlag('add-schedule-matching-the-nth-schedules')) {
                    if (empty($request->nth_schedule_ids)) {
                        $clientHasNthSchedules = !empty(H::getHelper('RecruiterTrackerHelper')->getnthschedulebyclientid($aocPatientId));

                        if ($clientHasNthSchedules) {
                            return response()->json([
                                'error' => 'missing_schedule',
                                'message' => 'No NTH hours selected. Please select a schedule to create the tracker.',
                            ], Response::HTTP_UNPROCESSABLE_ENTITY);
                        }
                    }
                    if (!empty($request->nth_schedule_ids)) {
                        $updateRecruiter['nth_schedule_ids'] = implode(",", $request->nth_schedule_ids);
                    }
                } else {
                    if (isset($request->schedule) && $request->schedule != '') {
                        $updateRecruiter['Schedule'] = $request->schedule;
                    }
                }
                if (isset($request->total_hours) && $request->total_hours !== '') {
                    $updateRecruiter['Total_Hours'] = $request->total_hours;
                }
                if($request->outcome == 'Change Client'){

                    if (LD::checkFeatureFlag('change-client-notes-schedule-next-steps')) {
                        $previousPatientDetails = MssqlPatients::find($recruitertracker->aoc_patient_id);
                        $currentClientName = $updateRecruiter['Client_Name'];
                        $previousClientName = $previousPatientDetails->Patient_FirstName . ' ' . $previousPatientDetails->Patient_LastName;

                        $loggedInUser = H::getLoggedInUser();
                        $noteData = [];
                        $noteData['entity'] = 'tracker-activity';
                        $noteData['entity_id'] = $trackerId;
                        $noteData['notes'] ="Client was changed from ".$previousClientName." to ".$currentClientName." by ".$loggedInUser['name'];


                        H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                    }


                    //schedule a CCR any time they change to a new client
                    $needsCCR = 1;
                    if ($cbucket == 'IPEA - Scheduled') {
                        //set to 0 so it bypasses Need to be scheduled by RA bucket
                        $request->scheduling = 0;
                    }
                }

                $lastScheduleData22 = RecruitingSchedule::where(['type_id' => 2, 'tracker_id' => $trackerId])
                    ->orderBy('created_at', 'desc')
                    ->first();

                if (isset($lastScheduleData22->id) && $lastScheduleData22->outcome != 'Completed') {
                    $scheduleData['outcome'] = $request->outcome;
                    $scheduleData['updated_at'] = date('Y-m-d H:i:s');

                    $duplicate = RecruitingSchedule::where('tracker_id', $trackerId)
                        ->where('type_id', 2)
                        ->where('id', '!=', $lastScheduleData22->id)
                        ->where('outcome', $request->outcome)
                        ->first();

                    if ($duplicate) {
                        RecruitingSchedule::where('id', $duplicate->id)
                            ->update(['is_deleted' => 1]);

                        Log::warning("Duplicate RecruitingSchedule #{$duplicate->id}. Mark as deleted");
                    }

                    RecruitingSchedule::where('id', $lastScheduleData22->id)->update($scheduleData);
                }
            } elseif ($request->outcome == 'Nurse decided not to work for Angels of Care') {
                $lastScheduleData = RecruitingSchedule::where(['type_id' => $schedTypesId, 'tracker_id' => $trackerId])->orderBy('created_at', 'desc')->first();
                if ($lastScheduleData) {
                    $scheduleData['outcome'] = $request->outcome;
                    $scheduleData['updated_at'] = date('Y-m-d H:i:s');
                    $scheduleData['updated_by'] = Auth::user()->id;
                    H::ExcelLog('updating sched: ' . $lastScheduleData->id . ' - ' . print_r($scheduleData,true),'updateSchedules.log');
                    RecruitingSchedule::where('id', $lastScheduleData->id)->update($scheduleData);
                }
                //now update RT table
                $updateRecruiter['inactive_reason'] = 'Nurse decided not to work for AOC';
                $updateRecruiter['Candidate_Current_Status'] = 'Inactive';
                $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                $activityNote = 'Candidate set to Inactive - Nurse decided not to work for AOC';
                $updateRecruiter['First_Shift_Not_Worked'] = $request->outcome;
            } elseif ($request->outcome == 'First Shift Worked' || $request->outcome == 'First Shift Confirm') {
                $lastScheduleData = RecruitingSchedule::where(['type_id' => $schedTypesId, 'tracker_id' => $trackerId])->orderBy('created_at', 'desc')->first();
                if ($lastScheduleData) {
                    $scheduleData['outcome'] = $request->outcome;
                    $scheduleData['updated_at'] = date('Y-m-d H:i:s');
                    $scheduleData['updated_by'] = Auth::user()->id;
                    H::ExcelLog('updating sched: ' . $lastScheduleData->id . ' - ' . print_r($scheduleData,true),'updateSchedules.log');
                    RecruitingSchedule::where('id', $lastScheduleData->id)->update($scheduleData);
                }
                $updateRecruiter['Candidate_Current_Status'] = 'First Shift Confirmed';
                $updateRecruiter['Bonus_Status'] = 'Pending Hours';
                $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                if(LD::checkFeatureFlag('add-outcomes-to-schedule-next-step-form-for-prn')) {
                    $activityNote = 'Candidate set to First Shift Confirmed';
                }
            } elseif ($request->outcome == 'Client Match') {

                if(LD::checkFeatureFlag('client-match-sns-additional-form')) {
                    $updateRecruiter['hard_stop'] = isset($request->hard_stop)?$request->hard_stop:0;
                    $updateRecruiter['pay_rate_entered_in_kantime'] = isset($request->pay_rate_entered_in_kantime)?$request->pay_rate_entered_in_kantime:0;
                    $updateRecruiter['right_signature_sent'] = isset($request->right_signature_sent)?$request->right_signature_sent:0;
                    $updateRecruiter['schedule_override'] = isset($request->schedule_override)?$request->schedule_override:'';
                    $updateRecruiter['first_week_created'] = isset($request->first_week_created)?$request->first_week_created:0;
                    if(LD::checkFeatureFlag('client-match-sns-patientstatus-candidatestatus')) {
                        $updateRecruiter['kantime_candidate_status'] = isset($request->kantime_candidate_status)?$request->kantime_candidate_status:0;

                    } else {
                        $updateRecruiter['kantime_patient_status'] = isset($request->kantime_patient_status)?$request->kantime_patient_status:0;
                    }

                }
                $lastScheduleData = RecruitingSchedule::where(['type_id' => $schedTypesId, 'tracker_id' => $trackerId])->orderBy('created_at', 'desc')->first();
                if ($lastScheduleData) {
                    $scheduleData['outcome'] = $request->outcome;
                    $scheduleData['updated_at'] = date('Y-m-d H:i:s');
                    $scheduleData['updated_by'] = Auth::user()->id;
                    H::ExcelLog('updating sched: ' . $lastScheduleData->id . ' - ' . print_r($scheduleData,true),'updateSchedules.log');
                    RecruitingSchedule::where('id', $lastScheduleData->id)->update($scheduleData);
                }


                $updateRecruiter['Candidate_Current_Status'] = 'Client Match';
                $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');

                //Add school setup
                    $aocPatientId = $request->patient_id;
                    H::ExcelLog('patient_id: ' . $aocPatientId,'addSsuForClientMatch.log');
                    $patientDetails = MssqlPatients::find($aocPatientId); //get patient to pre-populate other details
                    H::ExcelLog('patientDetails ' . print_r($patientDetails,true),
                    'addSsuForClientMatch.log');
                    if(strpos(strtolower($patientDetails->PreferenceList),"nurse attending child") !== false) {
                        H::ExcelLog('nurse attending child is true for
                        patient_id: ' . $aocPatientId,'addSsuForClientMatch.log');
                        try{
                            $user = H::getLoggedInUser();
                            $schoolData['created_at'] = date('Y-m-d');
                            $schoolData['created_by'] = $user['id'];
                            if(!empty($aocCandidateId)) {
                                $schoolData['aoc_candidate_id'] = $aocCandidateId;
                            } elseif(!empty($aocProviderId)) {
                                $schoolData['aoc_provider_id'] = $aocProviderId;
                            }
                            $schoolData['tracker_id'] = $trackerId;
                            $schoolData['aoc_patient_id'] = $aocPatientId;
                            $schoolData['assigned_ccc'] = $patientDetails->ccc_id??0;
                            $schoolData['school_name'] =  $patientDetails->CF_SchoolName??'';
                            $schoolData['school_district'] = $patientDetails->CF_Schooldistrict??'';
                            H::ExcelLog('school_setup_requests ' . print_r($schoolData,true),
                            'addSsuForClientMatch.log');
                            DB::table("school_setup_requests")->insert($schoolData);
                            //Create notification
                            $patientName = $patientDetails->Patient_FirstName.' '.$patientDetails->Patient_LastName;
                            $title ="Recruiter ".$recruiterName." has scheduled a client match
                            with client ".$patientName.".
                            A school set up request will need to be completed, so nurse can attend school.";
                                $notificationReceiver=[];
                                if(!empty($patientDetails->ccc_id)) {
                                    $notificationReceiver[] = $patientDetails->ccc_id;
                                    H::ExcelLog('Notification ccc id: ' . $patientDetails->ccc_id,
                                    'addSsuForClientMatch.log');
                                } elseif(!empty($patientDetails->ccm_id)) {
                                    $notificationReceiver[] = $patientDetails->ccm_id;
                                    H::ExcelLog('Notification ccm id: ' . $patientDetails->ccm_id,
                                    'addSsuForClientMatch.log');
                                } else {
                                    $rolename = 'Director of Branch Operations';
                                    $notificationReceiver = H::getHelper("UserPermissionResolver")
                                    ->getUserIdByBranchAndRole($patientDetails->aoc_facility_id, $rolename);
                                    H::ExcelLog('Notification DBO ' . print_r($notificationReceiver,true),
                                    'addSsuForClientMatch.log');
                                }
                                if (!empty($notificationReceiver)) {
                                    foreach($notificationReceiver as $notificationReceiverId) {
                                        $data = [];
                                        $notificationData = ['user_id'=>$notificationReceiverId,
                                        'title'=>$title,'type'=>'ClientMatch',
                                        'related_item'=>$trackerId,'notes'=>''];
                                        H::ExcelLog('notificationData ' . print_r($notificationData,true),
                                        'addSsuForClientMatch.log');
                                        Notifications::addNotification($notificationData);
                                    }
                                }
                        } catch(\Exception $e) {
                            H::ExcelLog('addSsuForClientMatch Exception:' .$e->getMessage(),
                            'addSsuForClientMatch.log');
                        }
                    }
            } elseif ($request->outcome == 'Rescheduled') {
                $lastScheduleData = RecruitingSchedule::where(['type_id' => $schedTypesId, 'tracker_id' => $trackerId])->orderBy('created_at', 'desc')->first();
                if ($lastScheduleData) {
                    $scheduleData['outcome'] = $request->outcome;
                    $scheduleData['updated_at'] = date('Y-m-d H:i:s');
                    $scheduleData['updated_by'] = Auth::user()->id;
                    H::ExcelLog('updating sched: ' . $lastScheduleData->id . ' - ' . print_r($scheduleData,true),'updateSchedules.log');
                    RecruitingSchedule::where('id', $lastScheduleData->id)->update($scheduleData);
                }
            } elseif ($request->outcome == 'Completed School Set Up') {
                $schedTypesId = H::getHelper('ConstantHelper')->getScheduleType('School Set Up');
                H::ExcelLog('schedtype: ' . $schedTypesId,'tmplol.log');
                $lastScheduleData = RecruitingSchedule::where(['type_id' => $schedTypesId, 'tracker_id' => $trackerId])->orderBy('created_at', 'desc')->first();
                if ($lastScheduleData) {
                    $scheduleData['outcome'] = $request->outcome;
                    $scheduleData['updated_at'] = date('Y-m-d H:i:s');
                    $scheduleData['updated_by'] = Auth::user()->id;
                    H::ExcelLog('updating sched: ' . $lastScheduleData->id . ' - ' . print_r($scheduleData,true),'updateSchedules.log');
                    RecruitingSchedule::where('id', $lastScheduleData->id)->update($scheduleData);
                }
                $updateRecruiter['School_Set_Up_Completed'] = date('Y-m-d H:i:s');
            } elseif ($request->outcome == 'Pending Next Steps') {
                $updateRecruiter['Candidate_Notes'] = $request->note;
            } elseif ($request->outcome == 'Need to review new clients' || ($request->outcome == 'Suggest Other Clients' && $cbucket == 'IPEA - Scheduled')
            || ($request->outcome == 'Suggest Other Clients' && $cbucket == 'PRN')) {
                $updateRecruiter['Candidate_Current_Status'] = 'Need to Review New Clients';
                $noteText = "Tracker was moved to Need to Review New Clients by " . Auth::user()->name;
                H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'tracker-activity', 'entity_id' => $trackerId,  'notes' => $noteText]);
                $request->scheduling = 0; //to bypass Needs to be scheduled by RA bucket
                $currentTracker = MssqlRecruitertracker::find($trackerId);
                $updateRecruiter['aoc_intake_id'] = ($currentTracker->aoc_intake_id > 0) ?
                                                    ($currentTracker->aoc_intake_id * -1) : $currentTracker->aoc_intake_id;
            } elseif ($request->outcome == 'Move to PRN'){
                $updateRecruiter['Candidate_Current_Status'] = 'PRN';
				$updateRecruiter['aoc_patient_id'] = $request->PRN_PATIENT_ID;
				$updateRecruiter['Client_ID'] = $request->PRN_ClientID;
                $noteText = "Tracker was moved to PRN by " . Auth::user()->name;
                H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'tracker-activity', 'entity_id' => $trackerId,  'notes' => $noteText]);
            } elseif ($request->outcome == 'No Longer Interest in PRN - Needs to Review New Clients') {
                $updateRecruiter['Candidate_Notes'] = $request->note;
                $updateRecruiter['Candidate_Current_Status'] = 'Need to Review New Clients';
                $noteText = "No Longer Interested in PRN - Needs to Review New Clients.";
                H::getHelper('ActivityNotes')->setActivityNotes([
                    'entity' => 'tracker-activity',
                    'entity_id' => $trackerId,
                    'notes' => $noteText]
                );
            } elseif ($request->outcome == 'No Longer Interested in PRN - Moved to Active Coverage Status') {
                $updateRecruiter['Candidate_Notes'] = $request->note;
                $updateRecruiter['inactive_reason'] = 'No Longer Interested in PRN - Moved to Active Coverage Status';
                $updateRecruiter['Candidate_Current_Status'] = 'Inactive';
                $noteText = "Tracker record made inactive by " . Auth::user()->name." due to nurse no longer being interested in PRN";
                H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'tracker-activity', 'entity_id' => $trackerId,  'notes' => $noteText]);
            } elseif ($request->outcome == 'Move to Active Coverage') {
                $updateRecruiter['Candidate_Notes'] = $request->note;
                $updateRecruiter['inactive_reason'] = 'Move to Active Coverage';
                $updateRecruiter['Candidate_Current_Status'] = 'Inactive';
                $noteText = "Tracker record made inactive by " . Auth::user()->name." due to nurse no longer being interested in PRN";
                H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'tracker-activity', 'entity_id' => $trackerId,  'notes' => $noteText]);
            } elseif ($request->outcome == 'Move to Active Coverage') {
                $updateRecruiter['Candidate_Notes'] = $request->note;
                $updateRecruiter['inactive_reason'] = 'Move to Active Coverage';
                $updateRecruiter['Candidate_Current_Status'] = 'Inactive';
                $noteText = "Tracker record made inactive by " . Auth::user()->name." due to nurse no longer being interested in PRN";
                H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'tracker-activity', 'entity_id' => $trackerId,  'notes' => $noteText]);
            } elseif ($request->outcome == 'No Longer Interested in PRN - Move to Active Coverage Status') {
                $updateRecruiter['aoc_patient_id'] = $request->active_PRN_PATIENT_ID;
                $updateRecruiter['Client_ID'] = $request->active_PRN_Client_ID;
                $updateRecruiter['Candidate_Current_Status'] = 'PRN';

                if(!empty($request->active_days_available_hidden)) {
                    $daysAvailableArray = explode(",",$request->active_days_available_hidden);
                    $formattedDates = array_map(function($daysAvailableArray) {
                        $dt =\DateTime::createFromFormat('m/d/Y', $daysAvailableArray);
                        return $dt ? $dt->format('Y-m-d') : null; // handle parse failure
                    }, $daysAvailableArray);
                    if(!empty($formattedDates)) {
                        $updateRecruiter['days_available'] = implode(",",$formattedDates);
                    }

                }
                if(!empty($request->active_shift_type)) {
                    $updateRecruiter['shift_type'] =implode(",",$request->active_shift_type);

                }

                $noteText = "Tracker was moved to active coverage by ".Auth::user()->name;
                H::getHelper('ActivityNotes')->setActivityNotes([
                    'entity' => 'tracker-activity',
                    'entity_id' => $trackerId,
                    'notes' => $noteText]
                );

                H::getHelper('ActivityNotes')->setActivityNotes([
                    'entity' => 'candidate',
                    'entity_id' => $candidate_id,
                    'notes' => $noteText]
                );
            } elseif ($request->outcome == 'PRN Agreement Signed & First Month Scheduled') {
                $updateRecruiter['Candidate_Current_Status'] = 'PRN';
                $first_month_scheduled = [];
                $org_first_month_scheduled = [];
                if(!empty($request->first_month_scheduled)) {
                    foreach(explode(",",$request->first_month_scheduled) as $tempScheduleDate) {
                        $first_month_scheduled[]= date('Y-m-d', strtotime($tempScheduleDate));
                        $org_first_month_scheduled[] = $tempScheduleDate;
                    }
                }
                $updateRecruiter['prn_first_schedule'] = json_encode($first_month_scheduled);
                $noteText = "PRN Agreement Signed & First Month Scheduled for ";
                $noteText .= implode(", ", $org_first_month_scheduled);
                H::getHelper('ActivityNotes')->setActivityNotes([
                    'entity' => 'tracker-activity',
                    'entity_id' => $trackerId,
                    'notes' => $noteText]
                );
            }
                if($request->outcome=='Parent Declined Agency - IPEA Cancelled' || $request->outcome=='Parent Declined Agency - Cancel Candidate Start'
                ||$request->outcome=='Clinician Declined the Agency') {

                    $lastScheduleData = RecruitingSchedule::where(['type_id' => $schedTypesId, 'tracker_id' => $trackerId])->orderBy('created_at', 'desc')->first();
                    if ($lastScheduleData) {
                        $scheduleData['outcome'] = "Cancelled";
                        $scheduleData['updated_at'] = date('Y-m-d H:i:s');
                        $scheduleData['updated_by'] = Auth::user()->id;
                        H::ExcelLog('updating sched: ' . $lastScheduleData->id . ' - ' . print_r($scheduleData,true),'updateSchedules.log');
                        RecruitingSchedule::where('id', $lastScheduleData->id)->update($scheduleData);
                    }
                    if($request->outcome=='Parent Declined Agency - Cancel Candidate Start') {

                        $updateRecruiter['Candidate_Notes'] = $request->note;
                        $updateRecruiter['inactive_reason'] = 'Parent Declined Agency - Cancel Candidate Start';
                        $updateRecruiter['Candidate_Current_Status'] = 'Inactive';
                        $noteText = "Tracker record made inactive by " . Auth::user()->name." due to Parent Declined Agency - Cancel Candidate Start";
                        H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'tracker-activity', 'entity_id' => $trackerId,  'notes' => $noteText]);

                    }elseif($request->outcome=='Clinician Declined the Agency'){
                        $updateRecruiter['Candidate_Notes'] = $request->note??'';
                        $updateRecruiter['inactive_reason'] = $request->declined_reason?? '';
                        $updateRecruiter['Candidate_Current_Status'] = 'Inactive';
                        $noteText = "Tracker record made inactive by " . Auth::user()->name." due to Clinician Declined the Agency";
                        H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'tracker-activity', 'entity_id' => $trackerId,  'notes' => $noteText]);
                    } else {
                        $updateRecruiter['Candidate_Notes'] = $request->note;
                        $updateRecruiter['inactive_reason'] = 'Parent Declined Agency - IPEA Cancelled';
                        $updateRecruiter['Candidate_Current_Status'] = 'Inactive';
                        $noteText = "Tracker record made inactive by " . Auth::user()->name." due to Parent Declined Agency - IPEA Cancelled";
                        H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'tracker-activity', 'entity_id' => $trackerId,  'notes' => $noteText]);
                    }

                } elseif(($request->outcome=='Need to review new clients')
                    && ($request->bucket_type=='INTAKE-SCHEDULE-IPEA'
                    || $request->bucket_type=='INTAKE-TODAY-IPEA'
                    || $request->bucket_type=='INTAKE-CANDIDATE-STARTS'
                    || $request->bucket_type=='INTAKE-PENDING-CANDIDATE-STARTS' )) {
                    $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                    $updateRecruiter['aoc_intake_id'] = abs($recruitertracker->aoc_intake_id)*-1;

                    if(isset($request->intake_recruiter) && $request->intake_recruiter){
                        $recruiter = H::getHelper('ConstantHelper')->getUserById($request->intake_recruiter);
                        $updateRecruiter['Recruiter'] = $recruiter;
                    }
                }

            //update common fields
            $updateRecruiter['updated_at'] = date('Y-m-d H:i:s');
            $updateRecruiter['updated_by'] = Auth::user()->id;
            if ($request->note != '' && $request->note != $originalNote) {
                $updateRecruiter['Candidate_Notes'] = $request->note;
            }
            if ($cbucket == 'IPEA - Scheduled') {
                $updateRecruiter['IPEA_Outcome'] = $request->outcome;
            }
            if ($cbucket == 'PEPI - Scheduled') {
                $updateRecruiter['PEPI-Outcome'] = $request->outcome;
            }
            if ($cbucket == 'WEPI - Scheduled') {
                $updateRecruiter['WEPI-Outcome'] = $request->outcome;
            }
            if ($request->schedule_type == 'IPEA') {
                if (!empty($appointment_date)) {
                    //$updateRecruiter['IPEA_Scheduled'] = date('Y-m-d H:i:s');
                    $updateRecruiter['IPEA_Date'] = $appointment_date;
                    $updateRecruiter['IPEA_Time'] = $appointment_time;
                    $updateRecruiter['Candidate_Current_Status'] = 'IPEA - Scheduled';
                    $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                    $updateRecruiter['IPEA_Outcome'] = '';
                }
                    if($request->bucket_type=='INTAKE-SCHEDULE-IPEA'){
                        $updateRecruiter['intake_in_home'] = $request->intake_in_home;

                        if(empty($recruitertracker->Recruiter)){
                            $recruiter = H::getHelper('ConstantHelper')->getUserById($recruitertracker->user_id);
                            $updateRecruiter['Recruiter'] = $recruiter;
                        }

                    }
                $updateRecruiter['is_rehire'] = $request->is_rehire;
                    $updateRecruiter['new_grad'] = $request->new_grad;
                $updateRecruiter['why_nurse_chosen'] = $request->why_nurse_chosen;
            } elseif ($request->schedule_type == 'PEPI') {
                if (!empty($appointment_date)) {
                    //$updateRecruiter['PEPI_Scheduled'] = date('Y-m-d H:i:s');
                    if($needsCCR==0){
                        $updateRecruiter['PEPI_Date'] = $appointment_date;
                        $updateRecruiter['PEPI_Time'] = $appointment_time;
                        $updateRecruiter['Candidate_Current_Status'] = 'PEPI - Scheduled';
                        $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                        $updateRecruiter['PEPI-Outcome'] = '';
                    }else{
                        $updateRecruiter['proposed_ns_date'] = $appointment_date;
                        $updateRecruiter['proposed_ns_time'] = $appointment_time;
                        $updateRecruiter['proposed_next_step'] = 'PEPI';
                        $updateRecruiter['Candidate_Current_Status'] = 'Clinical Competency Review';
                        $skipScheduling = 1;
                    }
                }
            } elseif ($request->schedule_type == 'WEPI') {
                if (!empty($appointment_date)) {
                    if($needsCCR==0){
                        //$updateRecruiter['WEPI_Scheduled'] = date('Y-m-d H:i:s');
                        $updateRecruiter['WEPI_Date'] = $appointment_date;
                        $updateRecruiter['WEPI_Time'] = $appointment_time;
                        $updateRecruiter['Candidate_Current_Status'] = 'WEPI - Scheduled';
                        $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                        $updateRecruiter['WEPI-Outcome'] = '';
                    }else{
                        $updateRecruiter['proposed_ns_date'] = $appointment_date;
                        $updateRecruiter['proposed_ns_time'] = $appointment_time;
                        $updateRecruiter['proposed_next_step'] = 'WEPI';
                        $updateRecruiter['Candidate_Current_Status'] = 'Clinical Competency Review';
                        $skipScheduling = 1;
                    }
                }
            } elseif ($request->schedule_type == 'CCR') {
                if (!empty($appointment_date)) {
                    $updateRecruiter['Candidate_Current_Status'] = 'Clinical Competency Review';
                    $updateRecruiter['Clinical_Competency_Review'] = $appointment_date;
                }
            } elseif ($request->schedule_type == 'Training Shift') {
                if (!empty($appointment_date)) {
                    if($needsCCR==0){
                        $updateRecruiter['Training_Shift'] = $appointment_date;
                        H::ExcelLog('time: ' . $appointment_time,'testdate.log');
                        $updateRecruiter['Training_Shift_Time'] = $appointment_time;
                        $updateRecruiter['Candidate_Current_Status'] = 'Training Shift Scheduled';
                        $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                    }else{
                        $updateRecruiter['proposed_ns_date'] = $appointment_date;
                        $updateRecruiter['proposed_ns_time'] = $appointment_time;
                        $updateRecruiter['proposed_next_step'] = 'Training Shift';
                        $updateRecruiter['Candidate_Current_Status'] = 'Clinical Competency Review';
                        $skipScheduling = 1;
                    }
                }
            } elseif ($request->schedule_type == 'Candidate Start') {
                if (!empty($appointment_date)) {
                    if($needsCCR==0){
                        $updateRecruiter['Candidate_Start_Date'] = $appointment_date;
                        $updateRecruiter['Candidate_Start_Time'] = $appointment_time;
                        $updateRecruiter['Candidate_Current_Status'] = 'Client Match';
                        $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                    }else{
                        $updateRecruiter['proposed_ns_date'] = $appointment_date;
                        $updateRecruiter['proposed_ns_time'] = $appointment_time;
                        $updateRecruiter['proposed_next_step'] = 'Candidate Start';
                        $updateRecruiter['Candidate_Current_Status'] = 'Clinical Competency Review';
                        $skipScheduling = 1;
                    }
                }
            } elseif ($request->schedule_type == 'School Set Up') {
                if (!empty($appointment_date)) {
                    $updateRecruiter['School_Set_Up_Due_Date'] = $appointment_date;
                }
            }
            //if the outcome is set to schedule something,  but nothing is scheduled, set status to Need to be scheduled by RA
            if (
                $request->scheduling == '1'
                && $request->schedule_type == ''
                && $request->outcome !== ScheduleNextStepOutcomesEnum::INACTIVATE_ON_TRACKER->value
            ) {
                $updateRecruiter['Candidate_Current_Status'] = 'Need to be Scheduled by RA';
                $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
            }


                if($request->outcome=='Candidate NCNS to first shift'){
                    $lastScheduleData = RecruitingSchedule::where(['type_id' => $schedTypesId, 'tracker_id' => $trackerId])->orderBy('created_at', 'desc')->first();
                    if ($lastScheduleData) {
                        if ($request->attampted_to_contact == 1) {

                            $patientsDetails = MssqlPatients::select('ccc_id',DB::raw('concat(Patient_FirstName," ", Patient_LastName) as ClientName'))->find($recruitertracker->aoc_patient_id);

                            $data['user_id'] = $recruitertracker->user_id;
                            $data['title'] = 'Candidate '.$recruitertracker->Candidate.' did not show to first shift with client '.$patientsDetails->ClientName.'.';
                            $data['type'] = 'trackerDetails';
                            $data['related_item'] = $trackerId;
                            $data['notes'] = '';
                            Notifications::addNotification($data);

                            $data['user_id'] = $patientsDetails->ccc_id;
                            Notifications::addNotification($data);

                            $data['user_id'] = H::getRecruiterAdmin($recruitertracker->user_id,true);
                            Notifications::addNotification($data);

                            $scheduleData['attempt_count'] = ($lastScheduleData->attempt_count + 1);
                            $scheduleData['outcome'] = "NCNS attempt ".$scheduleData['attempt_count'];
                            $scheduleData['updated_at'] = date('Y-m-d H:i:s');
                            $scheduleData['updated_by'] = Auth::user()->id;
                            H::ExcelLog('updating sched: ' . $lastScheduleData->id . ' - ' . print_r($scheduleData,true),'updateSchedules.log');

                            RecruitingSchedule::where('id', $lastScheduleData->id)->update($scheduleData);

                        }
                    }
                }

            //set whiteboard row
            $whiteBoard = H::getHelper('RecruiterTrackerHelper')->getWhiteboardRow($userId, $aocFacilityId, $skillSet);
            $updateRecruiter['whiteboard_row'] = $whiteBoard;
                if ($request->attampted_to_contact == 1) {
                    $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                    $lastScheduleData = RecruitingSchedule::where(['type_id' => $schedTypesId, 'tracker_id' => $trackerId])->orderBy('created_at', 'desc')->first();
                    if ($lastScheduleData) {
                        if($lastScheduleData->attempt_count>=3) {
                            $updateRecruiter['Candidate_Current_Status'] = 'Inactive';
                            $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                            $noteData = [];
                            $noteData['entity'] = 'tracker-activity';
                            $noteData['entity_id'] = $trackerId;
                            $noteData['notes'] ='Candidate Was set to Inactive after 3rd attempt to contact';
                            H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                        }
                    }
                }


            if(LD::checkFeatureFlag('add-new-outcomes-to-new-to-review-new-clients-bucket')) {
                if ($cbucket == 'Need to Review New Clients' &&  in_array($request->outcome, ['Attempt 1', 'Attempt 2', 'Attempt 3'])) {
                    $msgText = '';
                    $updateRecruiter['attempt_count'] = ($recruitertracker->attempt_count + 1);
                    $msgText = Auth::user()->name.' attempted to contact candidate ('.$request->outcome.')';
                    if($recruitertracker->attempt_count >= 2) {
                        $updateRecruiter['Candidate_Current_Status'] = 'Inactive';
                        $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                        $msgText = 'Unable to contact - Tracker record moved to Inactive';
                    }
                    $noteData = [];
                    $noteData['entity'] = 'tracker-activity';
                    $noteData['entity_id'] = $trackerId;
                    $noteData['notes'] = $msgText . ' <br/>'. $activityNote;
                    H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                    $activityNote = '';
                }
            }

            if(LD::checkFeatureFlag('add-attempts-outcome-schedule-by-ra-widget')) {
                if ($cbucket == 'Need to be Scheduled by RA' &&  in_array($request->outcome, ['Attempt 1', 'Attempt 2', 'Attempt 3'])) {
                    $msgText = '';
                    $updateRecruiter['schedule_by_ra_attempt_count'] = ($recruitertracker->schedule_by_ra_attempt_count + 1);
                    $msgText = Auth::user()->name.' attempted to contact candidate ('.$request->outcome.')';
                    if($recruitertracker->schedule_by_ra_attempt_count >= 2) {
                        $updateRecruiter['Candidate_Current_Status'] = 'Inactive';
                        $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                        $msgText = 'Unable to contact - Tracker record moved to Inactive';
                    }
                    $noteData = [];
                    $noteData['entity'] = 'tracker-activity';
                    $noteData['entity_id'] = $trackerId;
                    $noteData['notes'] = $msgText . ' <br/>'. $activityNote;
                    H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                    $activityNote = '';
                }
            }

            if(LD::checkFeatureFlag('add-new-outcome-to-ipea-widget-sns-form')) {
                if ($cbucket == 'IPEA - Scheduled' &&  in_array($request->outcome, ['Attempt 1', 'Attempt 2', 'Attempt 3'])) {
                    $msgText = '';
                    $updateRecruiter['ipea_attempt_count'] = ($recruitertracker->ipea_attempt_count + 1);
                    $msgText = Auth::user()->name.' attempted to contact candidate ('.$request->outcome.')';
                    if($recruitertracker->ipea_attempt_count >= 2) {
                        $updateRecruiter['Candidate_Current_Status'] = 'Inactive';
                        $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                        $msgText = 'Unable to contact - Tracker record moved to Inactive';
                    }
                    $noteData = [];
                    $noteData['entity'] = 'tracker-activity';
                    $noteData['entity_id'] = $trackerId;
                    $noteData['notes'] = $msgText . ' <br/>'. $activityNote;
                    H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                    $activityNote = '';
                }
            }

            if(LD::checkFeatureFlag('add-ccr-outcome-enhancements')) {
                if (in_array($request->outcome, ['Pending Parent Attempt 1', 'Pending Parent Attempt 2', 'Pending Parent Attempt 3','Pending Candidate Attempt 1', 'Pending Candidate Attempt 2', 'Pending Candidate Attempt 3'])) {
                    if(strpos($request->outcome, 'Pending Parent Attempt') !== false){
                        $updateRecruiter['pending_parent_attempt_count'] = ($recruitertracker->pending_parent_attempt_count + 1);
                        if($updateRecruiter['pending_parent_attempt_count'] >= 3) {
                            $updateRecruiter['Candidate_Current_Status'] = 'Need to Review New Clients';
                            $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                        }else{
                            $updateRecruiter['Candidate_Current_Status'] = 'Clinical Competency Review';
                        }
                    }else{
                        $updateRecruiter['pending_candidate_attempt_count'] = ($recruitertracker->pending_candidate_attempt_count + 1);
                        if($updateRecruiter['pending_candidate_attempt_count'] >= 3) {
                            $updateRecruiter['Candidate_Current_Status'] = 'Inactive';
                            $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                        }else{
                            $updateRecruiter['Candidate_Current_Status'] = 'Clinical Competency Review';
                        }
                    }
                    $schedTypesId = H::getHelper('ConstantHelper')->getScheduleType('CCR');
                    $lastScheduleData = RecruitingSchedule::where(['type_id' => $schedTypesId, 'tracker_id' => $trackerId])->orderBy('created_at', 'desc')->first();

                    if ($lastScheduleData) {
                        $scheduleData['outcome'] = $request->outcome;
                        $scheduleData['updated_at'] = date('Y-m-d H:i:s');
                        $scheduleData['updated_by'] = Auth::user()->id;

                        H::ExcelLog('updating sched: ' . $lastScheduleData->id . ' - ' . print_r($scheduleData,true),'updateSchedules.log');
                        RecruitingSchedule::where('id', $lastScheduleData->id)->update($scheduleData);
                    }
                    $msgText = Auth::user()->name.' attempted to contact candidate ('.$request->outcome.')';

                    $noteData = [];
                    $noteData['entity'] = 'tracker-activity';
                    $noteData['entity_id'] = $trackerId;
                    $noteData['notes'] = $msgText . ' <br/>'. $activityNote;
                    H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                    $activityNote = '';
                }
            }
           // var_dump($updateRecruiter);

           // exit;
            H::ExcelLog('tracker.aoc_patient_id: ' . $updateRecruiter['aoc_patient_id'],'updateTracker.log');
            H::ExcelLog('updating tracker: ' . $trackerId . ' - ' . print_r($updateRecruiter,true),'updateTracker.log');


            if ( LD::checkFeatureFlag('auto-inactivate-recruiter-trackers')
                && isset($updateRecruiter['Candidate_Current_Status'])
                && $updateRecruiter['Candidate_Current_Status'] ===  MssqlRecreturTrackerStatus::NEE_TO_REVIEW_NEW_CLIENTS->value
                && !is_null($aocProviderId)
            ) {
                event(new InactivateNRNCTrackers($recruitertracker->id, $aocProviderId, $candidate_id));
            }

            $activeStatuses = [
                'Need to Review New Clients',
                'IPEA - Scheduled',
                'PEPI - Scheduled',
                'WEPI - Scheduled',
                'Training Shift Scheduled',
                'PRN',
                'IPEA - Pending Orientation',
                'Inactive',
            ];

            if (isset($updateRecruiter['Candidate_Current_Status'])) {
                if (in_array($updateRecruiter['Candidate_Current_Status'], $activeStatuses)) {
                    $provider?->update(['SMS_Status' => 'Active']);
                }
            }

            MssqlRecruitertracker::query()->where('id', $trackerId)->update($updateRecruiter);

            //This is a mass update (not $model->save()), so no Eloquent event/observer fires here.
            //Bonus_Status is only ever set to 'Pending Hours' in this one place in the app, so
            //this check is the unique signal that this tracker just became bonus-eligible —
            //catch a cross-recruiter duplicate immediately instead of waiting for the next
            //process:hoursworked run (see markCrossRecruiterDuplicatesIneligible).
            if (($updateRecruiter['Bonus_Status'] ?? null) === MssqlRecruiterTrackerBonusStatus::PENDING_HOURS->value) {
                H::getHelper('RecruiterTrackerHelper')->markCrossRecruiterDuplicatesIneligible();
            }
            //skipScheduling = 1 when we scheduled a CCR and proposed something else
            if($skipScheduling==1){
                $noteText = "Proposed Next Step:  " . $updateRecruiter['proposed_next_step'] . ' at ' . H::getFormatedDate($updateRecruiter['proposed_ns_date']).' '.H::getFormatedTime($updateRecruiter['proposed_ns_time']);
                H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'tracker-activity', 'entity_id' => $trackerId,  'notes' => $noteText]);
            }

            $oldValues = $recruitertracker->Agreed_Pay_Rate;
            H::ExcelLog('update Activity Note', 'updateTracker.log');
            H::getHelper('ActivityNotes')->addTrackerActivity($trackerId, 'Agreed_Pay_Rate', $request->pay_rate, 'update', $oldValues);

        } else {
            //New Tracker Record
            H::ExcelLog('Mode: Add','updateTracker.log');
            $patientName = '';
            $patientAccount = '';
            $patientID = 0;
            $clinicianID = '';

            $aocPatientId = $request->patient_id;
            $aocProviderId = $request->provider_id;
            $aocCandidateId = $request->candidate_id;
            $mssqlFacilityID = '';
            $aocFacilityId = '';
            $branchLocation = '';
            H::ExcelLog('aocPatientId: ' . $aocPatientId,'updateTracker.log');
            //if there is a patient_id,  get all client related data from mssql_patients.  This supersedes everything else
            if($aocPatientId!=0 && $aocPatientId!=''){
                $tmp = DB::table('mssql_patients')->join('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
                        ->select('mssql_patients.id', 'mssql_patients.Patient_FirstName', 'mssql_patients.Patient_LastName', 'mssql_facilities.FacilityName', 'mssql_patients.PatientAccount',
                            'mssql_patients.PatientID', 'mssql_patients.FacilityID', 'mssql_patients.aoc_facility_id')
                        ->where('mssql_patients.id', $aocPatientId)->first();
                if (!empty($tmp)) {
                    $patientID = $tmp->PatientID;  //only needed for MSSQL update.  We do not use this field
                    $patientAccount = $tmp->PatientAccount;   //this is what AOC consideres the ClientID
                    $patientName = $tmp->Patient_FirstName . ' ' . $tmp->Patient_LastName;
                    $aocFacilityId = $tmp->aoc_facility_id;  //this is our internal ID for the facility (ie branch) associated with this client
                    $mssqlFacilityID = $tmp->FacilityID;  //this is the ID that the mssql data has to link to facility.  This is only used in case mssql_patients.aoc_facility_id didn't get set on client
                    $branchLocation = $tmp->FacilityName; //set the facility name so that it can be added to mssql_recruitertracker.BranchLocation
                }
            }elseif(isset($request->branch_location) && $request->branch_location!=''){
                //if patient_id was empty but they selected branch from the drop down,  get aoc_facility_id from mssql_facilities using request->branch_location
                //this is only used when the client isn't in our system yet (due to 24 hour delay in data synced from their EMS system) - normally patient_id would exist in request
                $branchLocation = $request->branch_location;
                $tmp = DB::table('mssql_facilities')->where('FacilityName', $request->branch_location)->first();
                if (!empty($tmp)) {
                    $aocFacilityId = $tmp->id;
                }
            }
            if($aocFacilityId==''){
                //we need a valid id for mssql_recruitertracker.aoc_facility_id.  We might be missing it if for some error it wasn't in mssql_patients.aoc_facility_id
                //so we will use mssql_patients.FacilityID to get it (this comes from mssql so we know it will be there)
                $tmp = DB::table('mssql_facilities')->where('FacilityID', $mssqlFacilityID)->first();
                if (!empty($tmp)) {
                    $aocFacilityId = $tmp->id;
                    $branchLocation = $tmp->FacilityName;
                }
            }

            $candidateName = $request->candidate_name;
            //get clinicianID from providers table
            if($aocProviderId!=0 && $aocProviderId!=''){
                $tmp = DB::table('mssql_providers')->where('id', $aocProviderId)->first();
                if (!empty($tmp)) {
                    $clinicianID = $tmp->ClinicianID;
                    if($candidateName=='') {
                        $candidateName = $tmp->fullName;
                    }
                    //disable user from text blast map
                    DB::table('maps_textblast')->where('Clinician_ID',$clinicianID)->delete();
                }
            }
            if (
                $request->status == 'new' ||
                $request->status == 'prn'
            ) {
                H::ExcelLog('new tracker','updateCandidates.log');
                $noteText = 'Candidate added to Recruiter Tracker for client ' . $patientName;
                if(empty($candidate_id)) {
                    //create record in candidate table
                    H::ExcelLog('insert candidate: ' . $request->candidate_name,'updateCandidates.log');
                    $candidate = [];
                    $firstname = '';
                    $lastname = '';
                    if (!empty($request->candidate_name)) {
                        [$firstname, $lastname] = array_pad(explode(' ', $request->candidate_name), 2, null);
                    }
                    $candidate['email'] = $request->email;
                    $candidate['firstName'] = $firstname;
                    $candidate['lastName'] = $lastname;
                    $candidate['fullName'] = $request->candidate_name;
                    $candidate['title'] = $request->title;
                        $candidate['homePhone'] = $request->phone;
                    $candidate['bilingual'] = $request->bilingual;
                    if($request->schedule_type == 'IPEA'){
                        $candidate['appWorkFlowState'] = 'IPEA Scheduled';
                        $candidate['IPEAScheduledDate'] = date('Y-m-d H:i:s');
                    }else{
                        $candidate['appWorkFlowState'] = 'New';
                    }
                    $candidate['workFlowStateChangeDate'] = date('Y-m-d H:i:s');
                    $candidate['created_at'] = date('Y-m-d H:i:s');
                    $candidate['updated_at'] = date('Y-m-d H:i:s');
                    $candidate['created_by'] = Auth::user()->id;
                    $candidate['updated_by'] = Auth::user()->id;
                    $candidate['user_id'] = Auth::user()->id;

                        $candidate['address'] = $request->caddress;
                        $candidate['address2'] = $request->caddress2;
                        $candidate['city'] = $request->ccity;
                        $candidate['state'] = $request->cstate;
                        $candidate['postalCode'] = $request->cpostalCode;
                    if (LD::checkFeatureFlag('prn-changes-add-clinician')) {
                        $candidate['candidate_type'] = $request->status;
                    }

                    H::ExcelLog('insert candidate: ' . print_r($candidate,true),'updateCandidates.log');
                    $data = Candidates::create($candidate);
                    $currentCandidate = $data;
                    $candidate_id = $data->id;
                    $aocCandidateId = $candidate_id;

                    // Updating the conversation with new candidate id with phone matched
                        $this->updateOrphanedMessagesWithCandidateIfMatch($data);
                }else{
                    //update candidate record
                    H::ExcelLog('update candidate: ' . $request->candidate_name,'updateCandidates.log');
                    $jvc = Candidates::find($candidate_id);
                    $updateCandidate = ['workFlowStateChangeDate' =>  date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id];
                    $syncToJobvite = 0;
                    if($jvc->fullName!=$request->candidate_name){
                        if (!empty($request->candidate_name)) {
                            [$firstname, $lastname] = array_pad(explode(' ', $request->candidate_name), 2, null);
                            $updateCandidate['firstName'] = $firstname;
                            $updateCandidate['lastName'] = $lastname;
                        }
                        $updateCandidate['fullName'] = $request->candidate_name;
                        $syncToJobvite = 1;
                        $noteText .= '<br/>Candidate name changed to ' . $request->candidate_name;
                    }
                    if($jvc->email!=$request->email){
                        $updateCandidate['email'] = $request->email;
                        $syncToJobvite = 1;
                        $noteText .= '<br/>Email changed from ' . $jvc->email . ' to ' . $request->candidate_name;
                    }
                    if($jvc->title!=$request->title){
                        $updateCandidate['title'] = $request->title;
                        $syncToJobvite = 1;
                        $noteText .= '<br/>Title changed from ' . $jvc->title . ' to ' . $request->title;
                    }
                        if($jvc->homePhone!=$request->phone){
                            $updateCandidate['homePhone'] = $request->phone;
                            $syncToJobvite = 1;
                            $noteText .= '<br/>Phone changed from ' . $jvc->homePhone . ' to ' . $request->phone;
                        }
                    if($jvc->bilingual!=$request->bilingual){
                        $updateCandidate['bilingual'] = $request->bilingual;
                        if($request->bilingual==1){
                            $noteText .= '<br/>Candidate set to bilingual';
                        }else{
                            $noteText .= '<br/>Candidate set to not bilingual';
                        }
                    }
                    if($request->schedule_type == 'IPEA'){
                        $updateCandidate['appWorkFlowState'] = 'IPEA Scheduled';
                        $updateCandidate['IPEAScheduledDate'] = date('Y-m-d H:i:s');
                        $syncToJobvite = 1;
                    }
                    if($syncToJobvite==1){
                        $updateCandidate['syncToJobVite'] = 1;
                    }
                    $updateCandidate['user_id'] = Auth::user()->id;
                    H::ExcelLog('update candidate: ' . $candidate_id . ' - ' . print_r($updateCandidate, true),'updateCandidates.log');

                        $updateCandidate['address'] = $request->caddress;
                        $updateCandidate['address2'] = $request->caddress2;
                        $updateCandidate['city'] = $request->ccity;
                        $updateCandidate['state'] = $request->cstate;
                        $updateCandidate['postalCode'] = $request->cpostalCode;
                    if (LD::checkFeatureFlag('prn-changes-add-clinician')) {
                        $candidate['candidate_type'] = $request->status;
                    }
                    Candidates::where('id', $candidate_id)->update($updateCandidate);
                }
                H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'candidate', 'entity_id' => $candidate_id, 'tracker_id' => $trackerId, 'notes' => $noteText]);
            }else{
                //existing clinician
                $noteText = 'Clinician added to Recruiter Tracker for client ' . $patientName;
                H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'provider', 'entity_id' => $aocProviderId, 'tracker_id' => $trackerId, 'notes' => $noteText]);
            }
            $updateRecruiter = [];
            $updateRecruiter['is_active'] = 1;
            $updateRecruiter['updated_at'] = date('Y-m-d H:i:s');
            $updateRecruiter['created_at'] = date('Y-m-d H:i:s');
            $updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
            $updateRecruiter['application_date'] = date('Y-m-d H:i:s');
            $updateRecruiter['Date_Contacted'] = date('Y-m-d H:i:s');
            $updateRecruiter['Candidate'] = $candidateName;
            $updateRecruiter['Clinician_ID'] = $clinicianID;
            $updateRecruiter['Bilingual'] = $request->bilingual;
            $updateRecruiter['Skillset'] = $request->title;
            $skillSet = $request->title;
            $updateRecruiter['Total_Hours'] = $request->total_hours;
            $updateRecruiter['Client_Name'] = $patientName;
            $updateRecruiter['Client_ID'] = $patientID;
            $updateRecruiter['aoc_patient_id'] = $aocPatientId;
            $updateRecruiter['aoc_candidate_id'] = $aocCandidateId;
            $updateRecruiter['aoc_facility_id'] = $aocFacilityId;
            $updateRecruiter['aoc_provider_id'] = $aocProviderId;
            $updateRecruiter['Branch_Location'] = $branchLocation;
            $updateRecruiter['Payor_Source'] = $request->payer;
            $updateRecruiter['Agreed_Pay_Rate'] = $request->pay_rate;
            $updateRecruiter['GP'] = $gp;
            $updateRecruiter['Approved_By'] = $request->gp_approved_by;
            $updateRecruiter['Candidate_Notes'] = $request->note;
            if(LD::checkFeatureFlag("add-schedule-matching-the-nth-schedules")) {
                if (!empty($request->nth_schedule_ids)) {
                    $updateRecruiter['nth_schedule_ids'] = implode(",", $request->nth_schedule_ids);
                }
            } else {
                $updateRecruiter['Schedule'] = $request->schedule;
            }
            $updateRecruiter['is_rehire'] = $request->is_rehire;
            $updateRecruiter['why_nurse_chosen'] = $request->why_nurse_chosen;
            $updateRecruiter['Recruiter'] = Auth::user()->name;
            $updateRecruiter['user_id'] = Auth::user()->id;
            $updateRecruiter['created_by'] = Auth::user()->id;
            $updateRecruiter['updated_by'] = Auth::user()->id;
            $updateRecruiter['lead_id'] = $lead_id ?? null;
            $userId = Auth::user()->id;
            $recruiterName = Auth::user()->name;

            if (LD::checkFeatureFlag('prn-changes-add-clinician')) {
                if ($request->status == 'prn') {
                    $updateRecruiter['shift_type'] = implode(",", $request->shift_type??[]);
                    $updateRecruiter['nurse_status'] = 2;
                }
            }

            if ($request->schedule_type == 'IPEA') {
                //$updateRecruiter['IPEA_Scheduled'] = date('Y-m-d H:i:s');
                $updateRecruiter['IPEA_Date'] = $appointment_date;
                $updateRecruiter['IPEA_Time'] = $appointment_time;
                $updateRecruiter['Candidate_Current_Status'] = 'IPEA - Scheduled';
                    $updateRecruiter['referred_by'] = $request->referred_by ?? NULL;
                    $updateRecruiter['new_grad'] = $request->new_grad;
            } elseif ($request->schedule_type == 'PEPI') {
                //$updateRecruiter['PEPI_Scheduled'] = date('Y-m-d H:i:s');
                //$updateRecruiter['PEPI_Date'] = $appointment_date;
                //$updateRecruiter['PEPI_Time'] = $appointment_time;
                //$updateRecruiter['Candidate_Current_Status'] = 'PEPI - Scheduled';
                $updateRecruiter['proposed_ns_date'] = $appointment_date;
                $updateRecruiter['proposed_ns_time'] = $appointment_time;
                $updateRecruiter['proposed_next_step'] = 'PEPI';
                $updateRecruiter['Candidate_Current_Status'] = 'Clinical Competency Review';
                $skipScheduling = 1;
            } elseif ($request->schedule_type == 'WEPI') {
                //$updateRecruiter['WEPI_Scheduled'] = date('Y-m-d H:i:s');
                //$updateRecruiter['WEPI_Date'] = $appointment_date;
                //$updateRecruiter['WEPI_Time'] = $appointment_time;
                //$updateRecruiter['Candidate_Current_Status'] = 'WEPI - Scheduled';
                $updateRecruiter['proposed_ns_date'] = $appointment_date;
                $updateRecruiter['proposed_ns_time'] = $appointment_time;
                $updateRecruiter['proposed_next_step'] = 'WEPI';
                $updateRecruiter['Candidate_Current_Status'] = 'Clinical Competency Review';
                $skipScheduling = 1;
            } elseif ($request->schedule_type == 'CCR') {
                if (!empty($appointment_date)) {
                    $updateRecruiter['Candidate_Current_Status'] = 'Clinical Competency Review';
                    $updateRecruiter['Clinical_Competency_Review'] = $appointment_date;
                }
            } elseif ($request->schedule_type == 'Training Shift') {
                //$updateRecruiter['Training_Shift'] = $appointment_date;
                //$updateRecruiter['Training_Shift_Time'] = $appointment_time;
                //$updateRecruiter['Candidate_Current_Status'] = 'Training Shift Scheduled';
                $updateRecruiter['proposed_ns_date'] = $appointment_date;
                $updateRecruiter['proposed_ns_time'] = $appointment_time;
                $updateRecruiter['proposed_next_step'] = 'Training Shift';
                $updateRecruiter['Candidate_Current_Status'] = 'Clinical Competency Review';
                $skipScheduling = 1;
            } elseif ($request->schedule_type == 'Candidate Start') {
                //$updateRecruiter['Candidate_Start_Date'] = $appointment_date;
                //$updateRecruiter['Candidate_Current_Status'] = 'Client Match';
                $updateRecruiter['proposed_ns_date'] = $appointment_date;
                $updateRecruiter['proposed_ns_time'] = $appointment_time;
                $updateRecruiter['proposed_next_step'] = 'Candidate Start';
                $updateRecruiter['Candidate_Current_Status'] = 'Clinical Competency Review';
                $skipScheduling = 1;
            } elseif ($request->schedule_type == 'School Set Up') {
                $updateRecruiter['School_Set_Up_Due_Date'] = $appointment_date;
            } else {
                $updateRecruiter['Candidate_Current_Status'] = 'Need to be Scheduled by RA';
            }

            //When the client is not been selected then as per P1-4365 task we have to set the Candidate_Current_Status = 'Need to Review New Clients'
                if (LD::checkFeatureFlag('prn-changes-add-clinician')) {
                    if((!isset($aocPatientId) || $aocPatientId == 0 || $aocPatientId == '') && $request->status != 'prn'){
                        $updateRecruiter['Candidate_Current_Status'] = 'Need to Review New Clients';
                    }
                } else {
                    if(!isset($aocPatientId) || $aocPatientId == 0 || $aocPatientId == ''){
                        $updateRecruiter['Candidate_Current_Status'] = 'Need to Review New Clients';
                    }
                }



            if (LD::checkFeatureFlag('prn-changes-add-clinician')) {
                    //set whiteboard row
                $whiteBoard = H::getHelper('RecruiterTrackerHelper')->getWhiteboardRow($userId, $aocFacilityId, $skillSet);
                $updateRecruiter['whiteboard_row'] = $whiteBoard;
                if ($request->status != 'prn') {
                    //calculate bonus amount
                    if($aocPatientId!=0 && $aocPatientId!=''){
                        $updateRecruiter['bonus_amount'] = MssqlRecruitertracker::calculateBonusAmount($request->all());
                    }
                }
            } else {
                //set whiteboard row
                $whiteBoard = H::getHelper('RecruiterTrackerHelper')->getWhiteboardRow($userId, $aocFacilityId, $skillSet);
                $updateRecruiter['whiteboard_row'] = $whiteBoard;
                //calculate bonus amount
                if($aocPatientId!=0 && $aocPatientId!=''){
                    $updateRecruiter['bonus_amount'] = MssqlRecruitertracker::calculateBonusAmount($request->all());
                }
            }

            if (LD::checkFeatureFlag('block-tracker-creation-under-kantime-weekly-hours')
                && !empty($aocPatientId)
                && !empty($aocProviderId)
            ) {
                $hoursEligibilityResult = $trackerHoursEligibilityService->checkEligibility(
                    (int) $aocProviderId,
                    (int) $aocPatientId,
                    (float) $updateRecruiter['Total_Hours']
                );

                if ($hoursEligibilityResult->status === TrackerHoursEligibilityResultDTO::STATUS_BLOCKED) {
                    return response()->json([
                        'error' => 'kantime_hours_exceeded',
                        'message' => 'This nurse was previously client matched for ' . $hoursEligibilityResult->highestWeeklyHours . ' hours. This nurse cannot be client matched again.',
                        'existing_tracker_id' => $hoursEligibilityResult->existingTrackerId,
                    ], Response::HTTP_CONFLICT);
                }
            }

            H::ExcelLog('tracker.aoc_patient_id: ' . $updateRecruiter['aoc_patient_id'],'updateTracker.log');
            H::ExcelLog('insert tracker: ' . print_r($updateRecruiter,true),'updateTracker.log');
            $data = MssqlRecruitertracker::create($updateRecruiter);
            $trackerId = $data->id;
            //Add Pay rate activity

            if (LD::checkFeatureFlag('auto-inactivate-recruiter-trackers')
                && $data->Candidate_Current_Status === MssqlRecreturTrackerStatus::NEE_TO_REVIEW_NEW_CLIENTS->value
            ) {
                event(new InactivateNRNCTrackers($trackerId, $data->aoc_provider_id));
            }

            if (isset($request->pay_rate) && $request->pay_rate != '') {
                H::ExcelLog('save Activity Note', 'updateTracker.log');
                H::getHelper('ActivityNotes')->addTrackerActivity($trackerId, 'Agreed_Pay_Rate', $request->pay_rate, 'new');
            }
            //skipScheduling = 1 when we scheduled a CCR and proposed something else
            if($skipScheduling==1){
                $noteText = "Proposed Next Step:  " . $updateRecruiter['proposed_next_step'] . ' at ' . H::getFormatedDate($updateRecruiter['proposed_ns_date']).' '.H::getFormatedTime($updateRecruiter['proposed_ns_time']);
                H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'tracker-activity', 'entity_id' => $trackerId,  'notes' => $noteText]);
            }
        }

        //Trach vent class request
        if (LD::checkFeatureFlag('schedule_trach_vent_tpn_class') && H::canPermissions('showscheduleclassform')) {
            $isRequestClass = LD::checkFeatureFlag('P1-3035-change-date-sort-trach-vent')
                ? $request->class_type === 'Request Class'
                : (isset($request->occurrence_id) && $request->occurrence_id == 'Request Class');

            if ($isRequestClass) {
                $this->notifyTrachVentClassRequested($request, $trackerId, $patientName ?? '', $candidateName ?? '');
            } elseif (!empty($request->occurrence_id) && $request->occurrence_id !== 'Request Class') {
                $this->createTrachVentClassRequest($request, $aocProviderId, $aocCandidateId, $aocPatientId, $trackerId ?? null);
            }
        }

        if(LD::checkFeatureFlag('email-when-ipea-with-outcome')){
            if($request->schedule_type=='IPEA' && (!isset($request->outcome) || $request->outcome != 'Rescheduled')) {
                H::getHelper('IpeaEmail')->processEmail($request,$trackerId);
            }
        }else{
            if($request->schedule_type=='IPEA' && !isset($request->outcome)) {
                H::getHelper('IpeaEmail')->processEmail($request,$trackerId);
            }

                if(LD::checkFeatureFlag('intake_schedule-ipea-without-brnach-fix')) {
                    if($request->bucket_type=='INTAKE-SCHEDULE-IPEA' && $request->outcome=='Schedule Next Step' && $request->schedule_type=='IPEA') {
                        H::getHelper('IpeaEmail')->processEmail($request,$trackerId);
                    }
                } else {
                    if($request->bucket_type=='INTAKE-SCHEDULE-IPEA' && $request->outcome=='Schedule Next Step' ) {
                        H::getHelper('IpeaEmail')->processEmail($request,$trackerId);
                    }
                }
        }

            if($request->schedule_type=='IPEA' && isset($request->outcome) && $request->outcome=='Rescheduled') {
                H::getHelper("IpeaEmail")->processEmail($request,$trackerId,true);
            }
        //schedule the appointment
        //create scheduleData record when scheduling a new appointment - TEST to make sure it's only created when it's supposed to
        //Nik I made this only consider $aapointment_date because a schedule might not have a fixed time
        //BUT I think we need to harden this to the outcome instead of only considering date
        H::ExcelLog('schedule type: ' . $request->schedule_type,'dbIpeaLocation.log');
        H::ExcelLog('ipea location: ' . $request->ipea_location_id,'dbIpeaLocation.log');
        if (!empty($appointment_date) && !in_array($request->outcome, ['NC/NS', 'Pending Nurse', 'Pending Parent'])) {
            H::ExcelLog('Scheduling','updateTracker.log');
            //if $skipScheduling = 1 then don't insert a schedule
            if($skipScheduling!=1){
                H::ExcelLog('Scheduling 2','updateTracker.log');
                //before we insert a schedule record,  find out if one exists for this tracker / client / type combination.
                $dbRecord = RecruitingSchedule::where(['type_id' => $scheduleTypes_id, 'tracker_id' => $trackerId, 'aoc_patient_id' => $aocPatientId])->orderBy('id')->first();

                if ($request->schedule_type == 'IPEA') {
                    $dbRecord = RecruitingSchedule::where(['type_id' => $scheduleTypes_id, 'tracker_id' => $trackerId])->orderBy('id')->first();
                }

                if (!empty($dbRecord)) {
                    //this is a reschedule - update appointment_date, appointment_time, updated_at, updated_by and insert comment into notes table
                    H::ExcelLog('original','testReschedule.log');
                    H::ExcelLog(print_r($dbRecord,true),'testReschedule.log');
                    $updateSchedule = json_decode(json_encode($dbRecord), true);
                    $updateSchedule['appointment_date'] = $appointment_date;
                    $updateSchedule['appointment_time'] = $appointment_time;
                    if ($request->schedule_type == 'IPEA') {
                        H::ExcelLog('is ipea - ' . $request->ipea_location_id, 'testipea.log');
                        $updateSchedule['ipea_location_id'] = $request->ipea_location_id;
                        $updateSchedule['is_virtual'] = $request->is_virtual ?? 0;
                    }
                    $updateSchedule['pay_rate'] = $request->pay_rate;
                    $updateSchedule['gp'] = $gp;
                    $updateSchedule['gp_approved_by'] = $request->gp_approved_by;
                    $updateSchedule['updated_at'] = date('Y-m-d H:i:s');
                    $updateSchedule['outcome'] = '';
                    $updateSchedule['updated_by'] = Auth::user()->id;
                    $updateSchedule['IPEA_clinician_arrived'] = 'NULL';
                    $updateSchedule['IPEA_lunch_preference'] = '';
                    $updateSchedule['IPEA_Confirmed'] = 'NULL';
                    H::ExcelLog(print_r($updateSchedule,true),'dbIpeaLocation.log');

                    RecruitingSchedule::where('id', $dbRecord->id)->update($updateSchedule);
                    $data2 = RecruitingSchedule::find($dbRecord->id);
                    //now insert a note
                    $myNote = $request->schedule_type . ' was rescheduled from ' . $dbRecord->appointment_date . ' at ' . $dbRecord->appointment_time . ' to ' . $appointment_date . ' at ' . $appointment_time . ' by ' . Auth::user()->name;
                    H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'tracker_activity', 'entity_id' => $trackerId, 'tracker_id' => $trackerId, 'notes' => $myNote]);
                }else{
                    H::ExcelLog('Scheduling 3','updateTracker.log');
                    if(LD::checkFeatureFlag('ccr-calendar-double-booking')){
                        $schedules = DB::table('recruiting_schedule')
                        ->leftJoin('mssql_recruitertracker', 'recruiting_schedule.tracker_id', '=', 'mssql_recruitertracker.id')->select('*','appointment_time as appointment_time_24',DB::raw('LOWER(TIME_FORMAT(appointment_time, "%h:%i%p")) as appointment_time'))->where(function($q){
                            $q->where(function($qq){
                                $qq->where('recruiting_schedule.type_id','2')->where('mssql_recruitertracker.assigned_ccr',537);
                            })->orWhere('recruiting_schedule.type_id','996');
                        })->where('appointment_date',date('Y-m-d',strtotime($appointment_date)))->where('appointment_time',date('H:i:s',strtotime($appointment_time)))->get();
                        if(count($schedules) > 0){
                            return response()->json(['success'=>'-1','msg'=>"CCR timeslot is already blocked recently"]);
                        }
                    }
                    //this is a new schedule - meaning this is the first one for this type/client/tracker combination
                    $scheduleData = [];
                    $scheduleData['tracker_id'] = $trackerId;
                    $scheduleData['type_id'] = $scheduleTypes_id;
                    $scheduleData['appointment_date'] = $appointment_date;
                    $scheduleData['appointment_time'] = $appointment_time;
                    $scheduleData['aoc_patient_id'] = $aocPatientId;
                    $scheduleData['aoc_candidate_id'] = $aocCandidateId;
                    $scheduleData['aoc_provider_id'] = $aocProviderId;
                    if ($request->schedule_type == 'IPEA') {
                        H::ExcelLog('is ipea - ' . $request->ipea_location_id, 'testipea.log');
                        $scheduleData['ipea_location_id'] = $request->ipea_location_id;
                        $scheduleData['is_virtual'] = $request->is_virtual ?? 0;
                    }
                    $scheduleData['pay_rate'] = $request->pay_rate;
                    $scheduleData['gp'] = $gp;
                    $scheduleData['gp_approved_by'] = $request->gp_approved_by;
                    $scheduleData['created_at'] = date('Y-m-d H:i:s');
                    $scheduleData['updated_at'] = date('Y-m-d H:i:s');
                    $scheduleData['scheduled_date'] = date('Y-m-d');
                    $scheduleData['outcome'] = '';
                    $scheduleData['candidate_type'] = $request->candidate_type ?? ScheduleCandidateType::NEW->value;
                    $scheduleData['created_by'] = Auth::user()->id;
                    $scheduleData['updated_by'] = Auth::user()->id;
                        $scheduleData['ccrNotes'] = $request->ccrNotes;
                        $tmp = DB::table('mssql_facilities')->where('FacilityName', $request->branch_location)->first();
                        if (!empty($tmp)) {
                            $scheduleData['aoc_facility_id'] = $tmp->id;
                        }
                        if($scheduleTypes_id==2){
                            $assign_id = '537';
                            if(LD::checkFeatureFlag('change-assign-ccr-process')){
                                $assign_id = H::getUserToAssignCcr($trackerId);
                            }
                            MssqlRecruitertracker::where('id', $trackerId)->update([
                                'assigned_ccr' => $assign_id,
                                'proposed_next_step'=>isset($request->proposed_next_step) ? $request->proposed_next_step : '',
                                'proposed_ns_date'=>isset($request->proposed_ns_date) ? date('Y-m-d',strtotime($request->proposed_ns_date)) : '',
                                'proposed_ns_time'=>isset($request->proposed_ns_time) ? date('H:i:s',strtotime($request->proposed_ns_time)) : '',
                            ]);
                        }
                    H::ExcelLog('schedule.aoc_patient_id: ' . $scheduleData['aoc_patient_id'],'updateSchedules.log');
                    H::ExcelLog(print_r($scheduleData,true),'updateSchedules.log');
                    $data2 = RecruitingSchedule::create($scheduleData);

                    //now we need to update the tracker record with the scheduled date
                    if($scheduleTypes_id==1) {
                        MssqlRecruitertracker::where('id', $trackerId)->update(['IPEA_Scheduled' => date('Y-m-d')]);
                    }
                    if($scheduleTypes_id==3) {
                        MssqlRecruitertracker::where('id', $trackerId)->update(['PEPI_Scheduled' => date('Y-m-d')]);
                    }
                    if($scheduleTypes_id==4) {
                        MssqlRecruitertracker::where('id', $trackerId)->update(['WEPI_Scheduled' => date('Y-m-d')]);
                    }
                    if($scheduleTypes_id==6) {
                        MssqlRecruitertracker::where('id', $trackerId)->update(['Client_Match_Date' => date('Y-m-d')]);
                    }

                    if ($request->schedule_type == 'IPEA') {

                        $linkedIPEA = $data2->id;
                        MssqlRecruitertracker::where('id', $trackerId)->update(['linked_IPEA' => $linkedIPEA]);
                        //send IPEA Notification
                            H::getHelper('EmailNotification')->sendIpeaNotificationEmail($request->candidate_name, $scheduleData);
                        //add notiication of IPEA
                        if (LD::checkFeatureFlag('add-ipea-notification')) {
                            $data = [];
                            $notifyUserId = H::getOrientationCoordinator($request->ipea_location_id);
                            if($notifyUserId!=''){
                                $branchName = H::getBranch($request->ipea_location_id);
                                $data['user_id'] = $notifyUserId;
                                $data['title'] = 'New IPEA Scheduled in ' . $branchName;
                                $data['type'] = 'ipea';
                                $data['related_item'] = $linkedIPEA;
                                $data['notes'] = '';
                                Notifications::addNotification($data);
                            }
                        }
                    }
                    if($request->schedule_type=='CCR') {
                        //send notification about CCR being assigned
                        $data = [];
                        if(!empty($trackerId) && !empty($aocPatientId)){
                            $tmp = MssqlRecruitertracker::select("mssql_patients.ccm_id","mssql_recruitertracker.assigned_ccr",DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"))
                            ->leftJoin("mssql_patients","mssql_patients.id","=","mssql_recruitertracker.aoc_patient_id")->where("mssql_recruitertracker.id",$trackerId)->first();
                            $user = 0;
                            $patientName = '';
                            if(!empty($tmp->assigned_ccr)) {
                                $user = $tmp->assigned_ccr;
                            } else if(!empty($tmp->ccm_id)) {
                                $user = $tmp->ccm_id;
                            }
                            if(isset($tmp->patient_name)) {
                                $patientName = $tmp->patient_name;
                            }
                            if($user) {
                                $data['user_id'] = $user;
                                $data['title'] = 'CCR for '.$patientName.' has been assigned to you.';
                                $data['type'] = 'trackerDetails';
                                $data['related_item'] = $trackerId;
                                $data['notes'] = '';
                                Notifications::addNotification($data);
                            }
                        }
                    }
                }


                if (LD::checkFeatureFlag("new-background-check")) {
                    $isCandidate = false;
                    $currentCandidate = MssqlProviders::find($data2->aoc_provider_id);

                    if (!empty($data2->aoc_candidate_id)) {
                        $isCandidate = true;
                        $currentCandidate = Candidates::find($data2->aoc_candidate_id);
                    }

                    if ($isCandidate) {
                        $searchIds = [$currentCandidate->id];
                        if (!empty($currentCandidate->email)) {
                            $emailSearch = Candidates::where('email', '=', $currentCandidate->email);
                            if (app()->environment('testing')) {
                                $emailSearch = $emailSearch->limit(self::EMAIL_DUPLICATE_SEARCH_LIMIT_TESTING);
                            }
                            $searchIds = $emailSearch->pluck('id')->toArray();
                        }
                    } else {
                        $searchIds = [$currentCandidate->id];
                        if (!empty($currentCandidate->AOC_Email)) {
                            $emailSearch = MssqlProviders::where('AOC_Email', '=', $currentCandidate->AOC_Email);
                            if (app()->environment('testing')) {
                                $emailSearch = $emailSearch->limit(self::EMAIL_DUPLICATE_SEARCH_LIMIT_TESTING);
                            }
                            $searchIds = $emailSearch->pluck('id')->toArray();
                        }
                        $currentCandidate->email = $currentCandidate->AOC_Email;
                    }

                    $branch = BranchLocations::where('id', $request->get('ipea_location_id'))->first();
                    $hasBackgroundCheck = FirstAdvantageCheck::whereIn('aoc_candidate_id', $searchIds)
                        ->whereNotIn('application_status', ['DealBroken', 'Cancelled', 'Deleted', 'Expired'])
                        ->where('type', $isCandidate ? 'candidate' : 'provider')
                        ->exists();


                    /**
                     * @todo
                     *
                     * Refactor this code on ticket https://support.dataart.com/browse/AOC-1868
                     */
                    //TODO this should be changed after mapping task are done, waiting FA to give proper data
                    $packageId = 2377;

                    $relatedIpeaBackgroundStatus = isset($relatedIPEA) ? $relatedIPEA->IPEA_background : null;

                    $inRequiredFaSkills = (
                        $skillSet === RecruitingScheduleEnum::DISCIPLINE_LVN->value ||
                        $skillSet === RecruitingScheduleEnum::DISCIPLINE_LPN->value ||
                        $skillSet === RecruitingScheduleEnum::DISCIPLINE_CNA->value ||
                        $skillSet === RecruitingScheduleEnum::DISCIPLINE_RN->value
                    );

                    if (
                        $request->schedule_type === ScheduleTypes::IPEA->name &&
                        $branch->cityOf(BranchLocations::CITY_DALLAS) &&
                        !$hasBackgroundCheck &&
                        LD::checkFeatureFlag("new-background-check") &&
                        $inRequiredFaSkills &&
                        $relatedIpeaBackgroundStatus === null
                    ) {


                        $log = [];
                        $log['request'] = $request->all();

                        $submittedRequests = FirstAdvantageCheck::whereIn('aoc_candidate_id', $searchIds)
                            ->where('type','=', $isCandidate ? 'candidate' : 'provider')
                            ->whereNotIn('application_status', ['DealBroken', 'Cancelled', 'Deleted', 'Expired'])
                            ->get();

                        if ($submittedRequests->isEmpty()) {
                            $fullName = $currentCandidate->fullName;
                            $fullName = (strpos($fullName, ' ') !== false) ? explode(' ', $fullName) : $fullName;

                            $firstName = $fullName[0] ?? '';
                            $lastName = $fullName[1] ?? '';

                            if (count($fullName) === 3) {
                                $firstName = sprintf('%s %s', $fullName[0], $fullName[1]);
                                $lastName = $fullName[2] ?? '';
                            }

                            $candidateData = [
                                'email' => $currentCandidate->email ?? $request->input('email'),
                                'override_candidate_data' => true,
                                'persons' => [
                                    [
                                        'given_name' => $firstName,
                                        'family_name' => $lastName,
                                        'type' => 'subject'
                                    ],
                                ],
                            ];


                            $this->fistAdvantageCheckValidation->checkForErrors([
                                'firstName' => $firstName,
                                'lastName' => $lastName,
                                'email' => $currentCandidate->email ?? $request->input('email'),
                                'city' => $branch->city ?? '',
                                'state' => 'TX',
                                'aoc_candidate_id' => $searchIds[0]
                            ]);

                            $log['candidateData'] = $candidateData;

                            $response = $advantageService->createCandidate($candidateData);

                            $log['createCandidate-response'] = $response;

                            if (!isset($response['error'])) {
                                $invite = [
                                    'candidate_id' => $response['candidate_id'],
                                    'package_id' => $packageId,
                                    'candidate_type' => 'PROFILE',
                                    'quote_backs' => [
                                        [
                                            'name' => 'candidate_id',
                                            'value' => $response['candidate_id']
                                        ],
                                        [
                                            'name' => 'aoc_clinician_id',
                                            'value' => $updateRecruiter['ClinicianID'] ?? ""
                                        ]
                                    ],
                                    'additional_attributes' => [
                                        'order_as' => [
                                            'account_id' => $advantageService->getAccountID("texas"),
                                            'user_id' => "XCHANGE", /*Auth::user()->id*/
                                            'email' => $currentCandidate->email ?? $request->input('email')
                                        ]
                                    ],
                                    'position_locations' => [
                                        [
                                            'country' => 'US',
                                            'city' => $branch->city ?? '',
                                            'state' => 'TX',
                                        ]
                                    ],
                                    'locale' => [
                                        'language_code' => 'en',
                                        'country_code' => 'us'
                                    ],
                                ];

                                $log['invite'] = $invite;
                                $intiveResponse = $advantageService->invite($invite);
                                $log['invite-response'] = $intiveResponse;
                            }

                            FirstAdvantageCheck::create([
                                'aoc_candidate_id' => $searchIds[0],
                                'fa_candidate_id' => $response['candidate_id'] ?? null,
                                'type' => $isCandidate ? 'candidate' : 'provider'
                            ]);

                            if (isset($response['error'])) {
                                $response['aoc_candidate_id'] = $searchIds[0];
                                $response['userId'] = auth()->user()->id;
                                $response['type'] = $isCandidate ? 'candidate' : 'provider';

                                FirstAdvantageErrors::create($response);
                            }

                            Log::channel('first_advantage')->error('First Advantage -> saveScheduleNext -> [LOG]', [
                                'dump' => $log
                            ]);
                        }
                    }
                }
            }
            //See if we need to schedule a CCR
            H::ExcelLog('in Schedule CCR.  needsCCR: ' . $needsCCR . ' - mode: ' . $request->mode,'updateSchedules.log');
            $schedule_CCR = 0;
            $is_arizona_ccr = false;

            if ($request->schedule_type !== 'IPEA' && $trackerId > 8564 && ($request->mode != 'Edit' || $needsCCR==1)) {
                if($request->mode != 'Edit'){
                    //only schedule CCR if this tracker doesn't have an IPEA schedule and doesn't already have a CCR scheduled
                    $scheduleTypes = DB::table('schedule_types')->where('type', 'IPEA')->first();
                    $tmpType_id = 0;
                    if (!empty($scheduleTypes)) {
                        $tmpType_id = $scheduleTypes->id;
                    }
                    $relatedIPEA = RecruitingSchedule::where(['type_id' => $tmpType_id, 'tracker_id' => $trackerId])->first();
                    if (!$relatedIPEA) {
                        H::ExcelLog('no IPEA found','updateSchedules.log');
                        $scheduleTypes = DB::table('schedule_types')->where('type', 'CCR')->first();
                        $tmpType_id = 0;
                        if (!empty($scheduleTypes)) {
                            $tmpType_id = $scheduleTypes->id;
                        }
                        $relatedCCR = RecruitingSchedule::where(['type_id' => $tmpType_id, 'tracker_id' => $trackerId])->first();
                        if (!$relatedCCR){
                            H::ExcelLog('no CCR found','updateSchedules.log');
                            $schedule_CCR = 1;
                        }
                    }
                }else{
                    //on Edits,  we already know if we need to schedul a CCR
                    H::ExcelLog('set schedule_CCR to ' . $needsCCR,'updateSchedules.log');
                    $schedule_CCR = $needsCCR;
                }
            } elseif (LD::checkFeatureFlag("automatically-create-ccr-for-arizona-ipea-scheduling")
                && $request->schedule_type === ScheduleTypes::IPEA->name
            ) {
                //Arizona state regulations require a CCR to be completed for every clinician scheduled for an IPEA
                $ipeaState = BranchLocations::where('id', $request->ipea_location_id)->value('state') ?? '';

                if ($ipeaState === config('constant.states.AZ')) {

                    $lastCCR = RecruitingSchedule::where(['type_id' => 2, 'tracker_id' => $trackerId])
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if (!$lastCCR || $lastCCR->outcome !== 'Completed') {
                        $is_arizona_ccr = true;
                        $schedule_CCR = 1;
                        Log::debug('Auto CCR created for Arizona IPEA scheduled for tracker: ' . $trackerId);
                    }
                }
            }

            if (($request->schedule_type === ScheduleTypes::IPEA->name)
                && LD::checkFeatureFlag('cross-state-dynamic-email')
            ) {
                $ipeaState = BranchLocations::where('id', $request->ipea_location_id)->value('state') ?? '';
                $clientStateAbbr = MssqlPatients::where('id', $request->patient_id)->value('Patient_State') ?? '';
                $clientState = config('constant.states.' . $clientStateAbbr, '');

                // compare IPEA and client states
                if ($crossStateHelper->isCrossState($ipeaState, $clientState)) {
                    $notes = $crossStateHelper->getIpeaCrossStateNotes($ipeaState, $clientState);

                    // add notes to IPEA entity
                    if ($notes != '') {
                        $notesData = [];
                        $notesData['entity'] = NotificationTypeEnum::TRACKER_ACTIVITY_TYPE->value;
                        $notesData['entity_id'] = $trackerId;
                        $notesData['notes'] = $notes;
                        $notesData['type'] = 'ipea';
                        $notesData['created_by'] = Auth::user()->id;
                        Notes::create($notesData);
                    }
                }
            }

            if ($schedule_CCR==1) {
                H::ExcelLog('Scheduling a CCR','updateSchedules.log');
                $ccr_date = $is_arizona_ccr ?
                    $appointment_date :
                    date('Y-m-d', strtotime('-1 day', strtotime($appointment_date)));
                if ($ccr_date < date('Y-m-d')) {
                    $ccr_date = date('Y-m-d');
                }
                $scheduleData = [];
                $scheduleData['tracker_id'] = $trackerId;
                $scheduleData['type_id'] = 2;
                $scheduleData['appointment_date'] = $ccr_date;
                    $scheduleData['ccrNotes'] = $request->ccrNotes;
                    $scheduleData['appointment_time'] = date('H:i:s',strtotime($ccr_date.' '.$appointment_time));
                    $tmp = DB::table('mssql_facilities')->where('FacilityName', $request->branch_location)->first();
                    if (!empty($tmp)) {
                        $scheduleData['aoc_facility_id'] = $tmp->id;
                    }
                $scheduleData['aoc_patient_id'] = $aocPatientId;
                $scheduleData['aoc_candidate_id'] = $aocCandidateId;
                $scheduleData['aoc_provider_id'] = $aocProviderId;
                $scheduleData['aoc_facility_id'] = $aocFacilityId;
                $scheduleData['pay_rate'] = $request->pay_rate;
                $scheduleData['gp'] = $gp;
                $scheduleData['gp_approved_by'] = $request->gp_approved_by;
                $scheduleData['created_at'] = date('Y-m-d H:i:s');
                $scheduleData['updated_at'] = date('Y-m-d H:i:s');
                $scheduleData['scheduled_date'] = date('Y-m-d');
                $scheduleData['outcome'] = '';
                $scheduleData['created_by'] = Auth::user()->id;
                $scheduleData['updated_by'] = Auth::user()->id;
                H::ExcelLog(print_r($scheduleData,true),'updateSchedules.log');
                $recruitingSchedule = RecruitingSchedule::create($scheduleData);
            }
        }

		//add the note to notes table

            if($request->bucket_type=='INTAKE-TODAY-IPEA' && $request->outcome=='NC/NS') {
                $attemptedContact = '';
                if($request->attampted_to_contact){
                    $attemptedContact = ', attempted to contact';
                }
                $activityNote = 'Clinician NCNS '.$attemptedContact.'. Additional comments:'.$request->note;
            }


        if ($activityNote != '' && $activityNote != $originalNote) {
            $notesData = [];
            $notesData['entity'] = 'tracker';
            $notesData['entity_id'] = $trackerId;
            $notesData['notes'] = $activityNote;
            $notesData['created_by'] = Auth::user()->id;
            $notesData['updated_by'] = Auth::user()->id;
            $notesData['completed'] = 1;
            $notesData['completed_at'] = date('Y-m-d H:i:s');
            $notesData['completed_by'] = Auth::user()->id;
            $notesData['priority'] = 0;
            $notesData['created_at'] = date('Y-m-d H:i:s');
            $notesData['updated_at'] = date('Y-m-d H:i:s');
            Notes::create($notesData);
        }

        return response()->json(['success'=>1, 'message'=>'Infomation saved successfully', 'linkedIPEA' => $linkedIPEA, 'candidate_id' => $candidate_id]);
    }

    private function notifyTrachVentClassRequested($request, $trackerId, string $patientName, string $candidateName): void
    {
        $bName = H::getBranch($request->location_id);
        $noteText = 'A Trach/Vent class was requested for '.$bName.': '.$request->trach_vent_requested_date_time;
        H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'tracker-activity', 'entity_id' => $trackerId, 'notes' => $noteText]);

        //Sending Mail to All Users who has permission
        $usersList = H::getHelper('UserPermissionResolver')->getUserByPermissionAndBranch('trachvent_class_request_notify', $request->location_id);
        $to = array_column($usersList, 'email');
        $subject = 'New Trach/Vent class request';
        $body = '<p>A Trach/Vent class has been requested by '.Auth::user()->name.' for '.$candidateName.' with '.$patientName.'.</p>  <p>'.$request->trach_vent_requested_date_time.'</p>';
        H::getHelper('EmailNotification')->sendEmailsWithoutAttach($to, $subject, $body, 'html');
    }

    private function createTrachVentClassRequest($request, $aocProviderId, $aocCandidateId, $aocPatientId, $trackerId = null): void
    {
        TrachVentClassRequest::create([
            'trach_vent_class_occurrence_id' => $request->occurrence_id,
            'provider_id' => $aocProviderId,
            'candidate_id' => $aocCandidateId,
            'client_id' => $aocPatientId,
            'tracker_id' => $trackerId ?: null,
            'discipline_snapshot' => $request->title,
            'phone_number_snapshot' => $request->phone,
            'created_by' => Auth::user()->id,
            'updated_by' => Auth::user()->id,
        ]);
    }

    public function updateOrphanedMessagesWithCandidateIfMatch($candidateData){
        if(isset($candidateData->homePhone) && $candidateData->homePhone != ''){
            $phoneNumber = preg_replace("/[^0-9]/","",$candidateData->homePhone);
            if(strlen($phoneNumber) == 10){
                $phoneNumber = '1'.$phoneNumber;
            }
            $conversation = DB::table('conversation')->select('id')
            ->where('aoc_provider_id',0)
            ->where('aoc_candidate_id',0)
            ->whereRaw('REGEXP_REPLACE(receiver_mobile, "[^0-9]", "") = ?',[$phoneNumber])->first();
            if(isset($conversation->id) && $conversation->id != '' && isset($candidateData->id) && $candidateData->id != ''){
                DB::table('conversation')->where('id',$conversation->id)->update(['aoc_candidate_id'=>$candidateData->id]);

                DB::table('conversation_reply')->where('aoc_provider_id',0)->where('aoc_candidate_id',0)->where('user_id',0)->where('conversation_id',$conversation->id)->update(['aoc_candidate_id'=>$candidateData->id,'isIncoming'=>1]);
            }
        }
    }

    public function getScheduleClient(Request $request)
    {
        $term = str_replace('  ', ' ', str_replace(',', '', $request->term));
        H::ExcelLog('term: ' . $term,'getScheduleClient.log');
        $clientlist = DB::table('mssql_patients')
        ->select(
            'mssql_patients.id',
            'mssql_patients.Patient_FirstName',
            'mssql_patients.Patient_LastName',
            'mssql_facilities.FacilityName',
            'mssql_facilities.BranchName',
            'mssql_patients.PatientAccount',
            'mssql_patients.Patient_City',
            'mssql_patients.Patient_State',
            'mssql_patients.Patient_Phone',
            'mssql_patients.Payer',
            'mssql_patientstatus.PatientStatus',
            'mssql_patients.CF_Skills',
			'mssql_patients.AdmitNo',
        )
        ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
        ->leftJoin('mssql_patientstatus', 'mssql_patientstatus.PatientStatusID', '=', 'mssql_patients.Patient_CurrentStatusID');
        if(empty($request->from)){
                $clientlist->whereNotIn(DB::raw('IFNULL(mssql_patients.Patient_CurrentStatusID,0)'), [
                    MssqlPatientStatus::INACTIVE,
                    MssqlPatientStatus::NON_ADMIT,
                    MssqlPatientStatus::DISCHARGED,
                ]);
        }else{
            if(isset($request->from) && $request->from == 'addnth'){
                if(LD::checkFeatureFlag('add-client-form-allow-discharged-as-new-client')) {
                    $clientlist->whereNotIn(DB::raw('IFNULL(mssql_patients.Patient_CurrentStatusID,0)'), [
                        MssqlPatientStatus::INACTIVE
                    ]);
                }else{
                    $clientlist->whereNotIn(DB::raw('IFNULL(mssql_patients.Patient_CurrentStatusID,0)'), [
                        MssqlPatientStatus::INACTIVE,
                        MssqlPatientStatus::NON_ADMIT,
                        MssqlPatientStatus::DISCHARGED,
                    ]);
                }
            }
        }
        if(!isset($request->from) || $request->from != 'addIntake'){
                $clientlist->whereNotIn(DB::raw('IFNULL(mssql_patients.LOBID,0)'), [1004,1006,1003,1005]);
        }
            $clientlist->where('mssql_patients.IsLatestAdmit', 1);
            $clientlist->addselect('mssql_patients.Patient_Address1',
            'mssql_patients.Patient_Address2',
            'mssql_patients.Patient_Zip',
            'mssql_patients.RespitePayer',
            'mssql_patients.aoc_facility_id',
            'mssql_patients.LOBID',
            'mssql_patients.CF_Specialized',
            'mssql_patients.ic_id');
            $clientlist->addselect('mssql_patients.CF_SchoolName',
                'mssql_patients.CF_SchoolDistrict');
        $clientlist = $clientlist->where(
            function ($query) use($term) {
                $query->where('mssql_patients.Search_Name', 'like', '%' . $term . '%')
                ->orWhere('mssql_patients.PatientAccount', 'like', '%' . $term . '%');
            }
        )
        ->limit(15)
        ->get();

        $clientlist = json_decode(json_encode($clientlist), true);

        $clientListData = [];
        $cnt = 0;
        foreach ($clientlist as $clientSubData) {
            //Added to get specialized skill
            $isSpecialized = 0;
            if (LD::checkFeatureFlag('schedule_trach_vent_tpn_class')) {
                if (H::canPermissions('showscheduleclassform')) {
                    if (!empty($clientSubData['CF_Skills'])) {
                        if(H::getHelper('NTHChecklist')->isSpecialized($clientSubData['CF_Skills'])) {
                            $isSpecialized = 1;
                        }
                    }
                }
            }
                $clientListData[$cnt] = ['id' => $clientSubData['id'], 'payer'=>$clientSubData['Payer'],
                'label' => $clientSubData['Patient_LastName'] . ',
                ' . $clientSubData['Patient_FirstName'] .' |
                '. $clientSubData['PatientAccount'] . ' |
                ' . $clientSubData['PatientStatus'] . ' |
                ' . $clientSubData['BranchName'] . ' |
				' . $clientSubData['AdmitNo'],
                'value' => $clientSubData['Patient_FirstName'] . ' ' . $clientSubData['Patient_LastName'],
                'branch_name' => $clientSubData['FacilityName'],
                'is_specialized' => $isSpecialized,
                'Patient_Address1' => $clientSubData['Patient_Address1'],
                'Patient_Address2' => $clientSubData['Patient_Address2'],
                'Patient_Zip' => $clientSubData['Patient_Zip'],
                'Patient_FirstName' => $clientSubData['Patient_FirstName'],
                'Patient_LastName' => $clientSubData['Patient_LastName'],
                'PatientAccount' => $clientSubData['PatientAccount'],
                'Patient_City' => $clientSubData['Patient_City'],
                'Patient_State' => $clientSubData['Patient_State'],
                'respiteTerminalPlayer'=>$clientSubData['RespitePayer'],
                'aoc_facility_id' => $clientSubData['aoc_facility_id'],
                'LOBID' => $clientSubData['LOBID'],
                'CF_Specialized' => $clientSubData['CF_Specialized'],
                'ic_id' => $clientSubData['ic_id'],
                'PatientStatus' => $clientSubData['PatientStatus']];
                $clientListData[$cnt]['SchoolName'] = $clientSubData['CF_SchoolName'];
                $clientListData[$cnt]['SchoolDistrict'] = $clientSubData['CF_SchoolDistrict'];
            $cnt++;
        }
        H::ExcelLog('clientlist: ' . print_r($clientListData, true),'getScheduleClient.log');
        return response()->json($clientListData);
    }

    public function getScheduleForm(Request $request, CrossStateHelper $crossStateHelper)
    {
        $branches = H::getHelper('ConstantHelper')->getBranches()->toArray();
        $branches = H::arrGroupBy($branches, 'state');
        $defaultOutcome = '';

        $now = Carbon::now();
        $target = Carbon::createFromFormat('d.m.Y', config('recruiterTracker.effectiveDate'));
        $isEffectiveDateGreaterThanNow = $now->gt($target);

        if (!empty($request->tracker_id)) {
            //use new form for existing tracker
            $schedule_type = '';
            $isOutcomeCompleted = 1;  //means CCR has been done, if needed (default to 1)
            $data = $request->all();
            $tracker_id = $request->tracker_id;
            $bucket = $request->bucket;
            $recruiterDetails = MssqlRecruitertracker::find($tracker_id);
            if(!isset($request->bucket)) {
                $tmpStatus = $recruiterDetails->Candidate_Current_Status;
                if(isset(config('constant')['bucketPerStatus'][$tmpStatus])){
                    $bucket = config('constant')['bucketPerStatus'][$tmpStatus];
                }
            }
            if ($bucket == 'IPEA' || $bucket == 'PEPI') {
                $schedule_type = 'PEPI';
            }
            if ($bucket == 'WEPI') {
                $schedule_type = 'WEPI';
            }
            if ($bucket == 'CANDIDATE STARTS') {
                $schedule_type = 'Candidate Start';
            }
            if ($bucket == 'NEED TO REVIEW NEW CLIENTS') {
                $schedule_type = 'PEPI';
            }

            if (LD::checkFeatureFlag('schedule-next-step-pending-outcomes')
                && $bucket == RecruitingBucket::TRAINING_SHIFT->value) {
                $schedule_type = ScheduleTypes::TRAINING_SHIFT->label();
            }

            if($bucket == 'IPEA') {
                if($recruiterDetails->live_ipea_status == 'Ready to schedule next steps' || $recruiterDetails->live_ipea_status == 'Ready to work') {
                    $defaultOutcome = 'Schedule Next Step';
                }
                if($recruiterDetails->live_ipea_status == 'Rescheduled') {
                    $defaultOutcome = 'Rescheduled';
                }
                if($recruiterDetails->live_ipea_status == 'Cancelled') {
                    $defaultOutcome = 'Cancelled';
                }
                if($recruiterDetails->live_ipea_status == 'NCNS') {
                    $defaultOutcome = 'NC/NS';
                }
                if($recruiterDetails->live_ipea_status == 'Need to review new clients') {
                    $defaultOutcome = 'Suggest Other Clients';
                }
            }
            $proposed_ns_date = $proposed_ns_time = '';
            if ($bucket == 'CCRS'){
                if ($recruiterDetails->id != '') {
                    H::ExcelLog('tracker: ' . $recruiterDetails->id,'dbworkshop.log');
                    $query = DB::select('select misc3 from notes where notes.entity_id=' . $recruiterDetails->id . ' and notes.entity = "tracker-activity"
                                and notes.misc1 = "CCR" and notes.misc2 = "Completed" order by notes.id desc limit 1');
                    $defaultOutcome = '';
                    foreach ($query as $schedules) {
                        if($schedules->misc3=='{"clinician_meeting":"Yes"}'){
                            $defaultOutcome = 'Ready to Schedule Next Steps';
                        }else{
                            $defaultOutcome = 'Suggest Other Clients';
                        }
                    }
                }
                //if (LD::checkFeatureFlag('no-automatic-recruiting-schedule')) {
                    $outcomeCompleted = DB::table('recruiting_schedule')->where('tracker_id',$tracker_id)->where('type_id',2)->latest('recruiting_schedule.id')->first();
                    if(!empty($outcomeCompleted)) {
                        if($outcomeCompleted->outcome != 'Completed') {
                            $isOutcomeCompleted = 0;
                        }
                    }
                //}

                $schedule_type = $recruiterDetails['proposed_next_step'];
                if (LD::checkFeatureFlag('tracker-sns-form-prepopulate-interview-type-date-time-ccrs-bucket')) {
                    $proposed_ns_date = $recruiterDetails->proposed_ns_date;
                    $proposed_ns_time = $recruiterDetails->proposed_ns_time;
                }
            }
            $providerDetails = [];
            if ($recruiterDetails->aoc_provider_id != '') {
                $providerDetails = MssqlProviders::find($recruiterDetails->aoc_provider_id);
            }
            if (LD::checkFeatureFlag('ipea-schedule-new-client')) {
                $aoc_patient_id = $recruiterDetails->aoc_patient_id;
                if($bucket == 'IPEA' && $recruiterDetails->live_ipea_status=='Need to review new clients'){
                    $aoc_patient_id = '';
                    $recruiterDetails->aoc_patient_id = '';
                    //$recruiterDetails->aoc_facility_id = '';
                    $recruiterDetails->Payor_Source = '';
                }
                $patientsDetails = [];
                if ($aoc_patient_id != '' && $aoc_patient_id !=0) {
                    $patientsDetails = MssqlPatients::find($recruiterDetails->aoc_patient_id);
                }
            }else{
                $patientsDetails = [];
                if ($recruiterDetails->aoc_patient_id != '') {
                    $patientsDetails = MssqlPatients::find($recruiterDetails->aoc_patient_id);
                }
            }

            // code for the schedule
            $nthSchedule = [];



            $branchDetails = [];
            if ($recruiterDetails->aoc_facility_id != '') {
                $branchDetails = MssqlFacilities::find($recruiterDetails->aoc_facility_id);
            }
            $candidateDetails = [];
            if ($recruiterDetails->aoc_candidate_id != '') {
                $candidateDetails = Candidates::find($recruiterDetails->aoc_candidate_id);
            }
            $schedules = [];
            if ($recruiterDetails->id != '') {
                $schedules = Schedule::where('tracker_id', '=', $recruiterDetails->id)->orderBy('created_at', 'desc')->get();
                $latest = [];
                $schedulesNew = [];
                foreach ($schedules as $key => $value) {
                    if (!in_array($value->type_id, $latest)) {
                        $schedulesNew[] = $value;
                        $latest[] = $value->type_id;
                    }
                }
                $schedules = $schedulesNew;
            }
            $scheduleType = ScheduleType::all()->toArray();
            usort($scheduleType, fn ($a, $b) => $a['sort_order'] - $b['sort_order']);
            $dataArr = [
                'tracker_id' => $tracker_id,
                'bucket' => $bucket,
                'schedule_type' => $schedule_type,
                'branches' => $branches,
                'recruiterDetails' => $recruiterDetails,
                'providerDetails' => $providerDetails,
                'patientsDetails' => $patientsDetails,
                'scheduleType' => $scheduleType,
                'schedules' => $schedules,
                'branchDetails' => $branchDetails,
                'candidateDetails' => $candidateDetails,
                'defaultOutcome' => $defaultOutcome,
                'nthSchedule' => $nthSchedule,
                'isOutcomeCompleted' => $isOutcomeCompleted,
                'proposed_ns_date' => $proposed_ns_date,
                'proposed_ns_time' => $proposed_ns_time,
                'classCategories' => $this->classCategoryReadRepository->orderedList()->pluck('name', 'id'),
                'crossState' => false,
            ];

            if(LD::checkFeatureFlag('client-match-sns-schedule')) {

                $nthScheduleGrid = [];

                if(isset($recruiterDetails->aoc_patient_id)) {
                    $aocPatient = $recruiterDetails->aoc_patient_id;
                    if($aocPatient!=0 && $aocPatient!='') {
                        $NTHSchedulesGridQuery = NeedToHireAccounts::select("NeedToHire_Schedules.*")->where("NeedToHire_Schedules.IsActive",1)->where("NeedToHire_Accounts.IsActive",1)->where("NeedToHire_Accounts.aoc_patient_id",$aocPatient);
                        $NTHSchedulesGridQuery->leftJoin("NeedToHire_Schedules", function($q){
                            $q->on("NeedToHire_Accounts.id","=","NeedToHire_Schedules.aoc_nthaccounts_id");
                        });
                        $NTHSchedulesGridQuery->orderBy("NeedToHire_Schedules.StartDayOfWeekInt","ASC");
                        $NTHSchedulesGridQuery->orderBy("NeedToHire_Schedules.StartHour","ASC");
                        $NTHSchedulesGridQuery->orderBy("NeedToHire_Schedules.StartMinute","ASC");
                        $schedulesG = $NTHSchedulesGridQuery->get();
                        foreach($schedulesG as $schedule) {

                            if($schedule->id) {
                                $nthScheduleGrid[] = $schedule;
                            }
                        }
                    }
                }

                $dataArr["schedulegrid"] = (count($nthScheduleGrid)) ?
                view('recruiterTracker._buildScheduleGrid',[
                    'nthschedule' => $nthScheduleGrid
                ])->render() : '';
            }

                if($bucket=='INTAKE-SCHEDULE-IPEA' || $bucket=='INTAKE-TODAY-IPEA' ) {
                    $dataArr["intake_recruiter"]=[];

                    if(!empty($recruiterDetails->Recruiter)){
                        $dataArr["intake_recruiter"] = $recruiterDetails->Recruiter;
                    }


                }

            if (LD::checkFeatureFlag("add-new-outcomes-to-new-to-review-new-clients-bucket")) {
                $dataArr["attemptCount"] = $recruiterDetails->attempt_count;
            }
            if (LD::checkFeatureFlag("add-attempts-outcome-schedule-by-ra-widget")) {
                $dataArr["scheduleByRaAttemptCount"] = $recruiterDetails->schedule_by_ra_attempt_count;
            }
            if (LD::checkFeatureFlag("add-new-outcome-to-ipea-widget-sns-form")) {
                $dataArr["ipeaAttemptCount"] = $recruiterDetails->ipea_attempt_count;
            }
                $clientEmergencyContact =$dataArr["clientEmergencyContact"] =  [];
                if ($recruiterDetails->aoc_patient_id != '') {
                    $clientEmergencyContactList = H::getHelper('ConstantHelper')
                    ->getClientEmergencyContactDetails($recruiterDetails->aoc_patient_id);
                    if(!empty($clientEmergencyContactList)) {
                        foreach($clientEmergencyContactList as $clientEmergencyContactData) {
                            $clientEmergencyContact[$clientEmergencyContactData->relation] =
                            $clientEmergencyContactData->telephone;
                        }
                    }
                }
                $dataArr["clientEmergencyContact"] = $clientEmergencyContact;

            $dataArr['isEffectiveDateGreaterThanNow'] = $isEffectiveDateGreaterThanNow;

            if (LD::checkFeatureFlag('schedule-next-step-pending-outcomes')
                && RecruitingBucket::supportsPendingOutcomes($bucket)
            ) {
                $dataArr['pending_reasons'] = [
                    ScheduleNextStepOutcomesEnum::PENDING_CLINICIAN_STATUS->value => PendingOutcomeReason::clinician(),
                    ScheduleNextStepOutcomesEnum::PENDING_CLIENT->value => PendingOutcomeReason::client(),
                ];
            }

            if (($bucket === 'IPEA')
                && LD::checkFeatureFlag('cross-state-dynamic-email')
            ) {
                $ipeaLocationId = $schedules[0]->ipea_location_id ?? 0;

                if ($ipeaLocationId) {
                    $ipeaState = BranchLocations::where('id', $ipeaLocationId)->value('state') ?? '';
                    $clientState = config('constant.states.' . $patientsDetails['Patient_State'] ?? '', '');

                    if ($crossStateHelper->isCrossState($ipeaState, $clientState)) {
                        $dataArr['crossState'] = true;
                    }
                }
            }

            return view('recruiterTracker.nextstep', $dataArr);
        } else {
            $data = $request->all();
            $tracker_id = $request->tracker_id;
            $bucket = $request->bucket;
            $clientId = $request->clientid;
            $show_save_next_btn = $request->show_save_next_btn;
            $candidatesDetails = [];
            $isNew = false;
            $isSpecialized = false;
            $isNthLeadInquiry = 0;
            if(strpos($bucket,'candidates') !== false){
                $candidateId = explode('##',$bucket)[1];
                if($candidateId != ''){
                    $candidatesDetails = Candidates::find($candidateId);
                    $isNew = true;
                }
                $bucket = '';
            }
            if (!empty($clientId)) {
                $patientsDetails = MssqlPatients::where(['PatientAccount' => $clientId, 'IsLatestAdmit' => '1'])->first();
                $branch = '';
                $pId = '';
                $clientName = '';
                $payerInfo = '';
                $CF_Skills = '';
                if (!empty($patientsDetails->id)) {
                    $pId = $patientsDetails->id;
                }
                if (!empty($patientsDetails->Payer)) {
                    $payerInfo = $patientsDetails->Payer;
                }
                if (!empty($patientsDetails->Patient_FirstName) && !empty($patientsDetails->Patient_LastName)) {
                    $clientName = $patientsDetails->Patient_FirstName . ' ' . $patientsDetails->Patient_LastName;
                }
                if (!empty($patientsDetails->FacilityID)) {
                    $branchDetails = MssqlFacilities::where(['FacilityID' => $patientsDetails->FacilityID])->first();
                    $branch = $branchDetails->FacilityName;
                }
                if (LD::checkFeatureFlag('schedule_trach_vent_tpn_class')) {
                    if (H::canPermissions('showscheduleclassform')) {
                        if (!empty($patientsDetails->CF_Skills)) {
                            $CF_Skills = $patientsDetails->CF_Skills;
                            $isSpecialized = H::getHelper('NTHChecklist')->isSpecialized($CF_Skills);
                        }
                    }
                }
                $client_data = ['ClientName' => $clientName, 'aoc_patient_id' => $pId, 'branch_location' => $branch, 'payer' => $payerInfo,'CF_Skills' => $CF_Skills,'isSpecialized' => $isSpecialized];
                if (LD::checkFeatureFlag('dashboard-nthalerts-inquiry-bucket')) {
                    if(strpos($bucket,'nthLeadInquiry') !== false){
                        $leadId = explode('##',$bucket)[1];
                        if($leadId != ''){
                            $leadsInquiriesDetail = DB::table('nth_inquiries')->select('nth_inquiries.*')->where('nth_inquiries.id',$leadId)->first();
                            $clinicianData = DB::table('mssql_providers')->select('mssql_providers.id','mssql_providers.FirstName','mssql_providers.LastName','mssql_providers.ClinicianID','mssql_providers.DisciplineName','mssql_providers.Phone','mssql_providers.Mobile','mssql_providers.External_Email')
                            ->where('ClinicianID', $leadsInquiriesDetail->ClinicianID)->first();
                            $client_data['candidate_name'] = $clinicianData->FirstName . ' ' . $clinicianData->LastName;
                            $client_data['phone'] = $clinicianData->Mobile;
                            $client_data['email'] = $clinicianData->External_Email;
                            $client_data['aoc_provider_id'] = $clinicianData->id;
                            $isNthLeadInquiry = 1;
                        }
                        // $bucket = '';
                    }
                }
            } else {
                $client_data = ['isSpecialized' => $isSpecialized];
            }
            $classCategories = $this->classCategoryReadRepository->orderedList()->pluck('name', 'id');
            return view('common.schedule_next_step', compact(
                'tracker_id',
                'client_data',
                'bucket',
                'branches',
                'candidatesDetails',
                'isNew',
                'show_save_next_btn',
                'isNthLeadInquiry',
                'isEffectiveDateGreaterThanNow',
                'classCategories'
            ));
        }
    }

    public function getCandidateName(Request $request)
    {
        $term = str_replace('  ', ' ', str_replace(',', '', $request->term));
        $status = 'current';
        if (!empty($request->status)) {
            $status = $request->status;
        }
        $filters = $request->filter??[];
        H::ExcelLog('status: ' . $status, 'addcandidate.log');
        if ($status == 'current') {
            if (LD::checkFeatureFlag('add-schedule-validation')) {
                $clientlist = DB::table('mssql_providers')
                    ->select(
                        'mssql_providers.id',
                        'mssql_providers.FirstName',
                        'mssql_providers.ClinicianID',
                        'mssql_providers.LastName',
                        'mssql_providers.External_Email',
                        'mssql_providers.DisciplineName',
                        'mssql_providers.Mobile',
                        'mssql_providers.City',
                        'mssql_providers.State',
                        'mssql_providers.Mobile',
                        'mssql_employmentstatus.EmploymentStatus',
                        'mssql_facilities.BranchName',
                        'mssql_providers.CF_NotEligibleForRehire',
                        DB::raw('(SELECT GROUP_CONCAT(CONCAT(mssql_recruitertracker.Recruiter,"#",IFNULL(recruiting_schedule.appointment_date, ""),"#", IFNULL(mssql_recruitertracker.Branch_Location, "")) separator "**") FROM mssql_recruitertracker LEFT JOIN recruiting_schedule ON recruiting_schedule.tracker_id = mssql_recruitertracker.id WHERE mssql_recruitertracker.aoc_provider_id = mssql_providers.id AND mssql_recruitertracker.is_active = 1 AND recruiting_schedule.type_id = 1 AND recruiting_schedule.appointment_date >= CURDATE() GROUP BY mssql_recruitertracker.aoc_provider_id ORDER BY mssql_recruitertracker.id DESC) AS ipea_schedule_ids'),
                        DB::raw('(SELECT GROUP_CONCAT(CONCAT(Recruiter,"#",aoc_patient_id,"#",nth_schedule_ids,"#",Client_Name,"#",IFNULL(Agreed_Pay_Rate, "")) separator "**") FROM mssql_recruitertracker WHERE mssql_recruitertracker.aoc_provider_id = mssql_providers.id AND mssql_recruitertracker.is_active = 1 AND mssql_recruitertracker.nth_schedule_ids IS NOT NULL GROUP BY mssql_recruitertracker.aoc_provider_id ORDER BY mssql_recruitertracker.id DESC) AS nth_schedule_ids')
                    )
                    ->join('mssql_employmentstatus', 'mssql_employmentstatus.EmploymentStatusID', '=', 'mssql_providers.EmploymentStatusID')
                    ->join('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')
                    ->where('mssql_providers.is_deleted',0)
                    ->where(
                        function ($query) use($term) {
                            $query->where('mssql_providers.Search_Name', 'like', '%' . $term . '%');
                            $query->orWhere('mssql_providers.ClinicianID', 'like', '%' . $term . '%');
                        }
                    )
                    ->where(
                        function ($query) use($filters) {
                            foreach($filters as $filterField => $filterValue) {
                                if (is_array($filterValue)) {
                                    $query->whereIn($filterField, $filterValue);
                                } else {
                                    $query->where($filterField, $filterValue);
                                }
                            }
                        }
                    );
                if (!empty($request->schoolsetup)) {
                    $clientlist->whereNotIn('mssql_providers.EmploymentStatusID', [0,12]);
                }
                $clientlist = $clientlist->limit(15)->get();
            } else {
                $clientlist = DB::table('mssql_providers')
                        ->select(
                            'mssql_providers.id',
                            'mssql_providers.FirstName',
                            'mssql_providers.ClinicianID',
                            'mssql_providers.LastName',
                            'mssql_providers.External_Email',
                            'mssql_providers.DisciplineName',
                            'mssql_providers.Mobile',
                            'mssql_providers.City',
                            'mssql_providers.State',
                            'mssql_providers.Mobile',
                            'mssql_employmentstatus.EmploymentStatus',
                            'mssql_facilities.BranchName',
                            'mssql_providers.CF_NotEligibleForRehire',
                        )
                        ->join('mssql_employmentstatus', 'mssql_employmentstatus.EmploymentStatusID', '=', 'mssql_providers.EmploymentStatusID')
                        ->join('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')
                        ->where('mssql_providers.is_deleted',0)
                        ->where(
                            function ($query) use($term) {
                                $query->where('mssql_providers.Search_Name', 'like', '%' . $term . '%');
                                $query->orWhere('mssql_providers.ClinicianID', 'like', '%' . $term . '%');
                            }
                        )
                        ->where(
                            function ($query) use($filters) {
                                foreach($filters as $filterField => $filterValue) {
                                    if (is_array($filterValue)) {
                                        $query->whereIn($filterField, $filterValue);
                                    } else {
                                        $query->where($filterField, $filterValue);
                                    }
                                }
                            }
                        );
                    if (!empty($request->schoolsetup)) {
                        $clientlist->whereNotIn('mssql_providers.EmploymentStatusID', [0,12]);
                    }
                    $clientlist = $clientlist->limit(15)->get();
            }
            $clientlist = json_decode(json_encode($clientlist), true);

            $clientListData = [];
            foreach ($clientlist as $clientSubData) {
                if (LD::checkFeatureFlag('add-schedule-validation')) {
                    $nthSchedulesIds = [];
                    $nthScheduleMsg = '';
                    $ipeamesg = '';
                    if (!empty($clientSubData['nth_schedule_ids'])) {
                        $nthRecords = explode("**", $clientSubData['nth_schedule_ids']);
                        foreach($nthRecords as $nthRecord) {
                            [$Recruiter, $aoc_patient_id, $nth_schedule_ids, $client_name, $pay_rate] = explode("#", $nthRecord);
                            $nthSchedulesIds = array_merge($nthSchedulesIds, explode(",", $nth_schedule_ids));
                            $nthScheduleMsg .= 'This candidate is being recruited by <b>'.$Recruiter.'</b> for '.$client_name.' at $'.$pay_rate.'/hr with the following schedules:<ul>';
                            $nthScheduleList = H::getHelper('RecruiterTrackerHelper')->getnthschedulebyclientid($aoc_patient_id, $nth_schedule_ids);
                            foreach($nthScheduleList as $nthSchedule) {
                                $nthScheduleMsg .= "<li>" . $nthSchedule['StartDayOfWeek'] . ' - ' . H::getHelper('ConstantHelper')->getSmallTimeFormat($nthSchedule['StartHour'] . ':' . $nthSchedule['StartMinute']) . ' - ' . H::getHelper('ConstantHelper')->getSmallTimeFormat($nthSchedule['EndHour'] . ':' . $nthSchedule['EndMinute']) . '</li>';
                            }
                            $nthScheduleMsg .= '</ul>';

                        }
                        $nthScheduleMsg .= '<p>Please do not recruit the candidate for conflicting schedules.</p>';
                    }
                    if(!empty($clientSubData['ipea_schedule_ids'])){
                        $ipeaRecords = explode("**", $clientSubData['ipea_schedule_ids']);
                        foreach($ipeaRecords as $ipeaRecord){
                            [$Recruiter, $appointment_date, $Branch_Location] = explode("#", $ipeaRecord);
                            $ipeamesg .= '<p>This candidate was scheduled for an IPEA on '.$appointment_date.' in '.$Branch_Location.' by '.$Recruiter.'.</p>';
                        }
                        $nthScheduleMsg .= $ipeamesg;
                    }
                    $clientListData[] = ['label' => $clientSubData['LastName'] . ', ' . $clientSubData['FirstName'] . ' | ' . $clientSubData['ClinicianID'] . ' | ' . $clientSubData['EmploymentStatus'] . ' | ' . $clientSubData['BranchName'],
                    'value' => $clientSubData['FirstName'] . ' ' . $clientSubData['LastName'], 'email' => $clientSubData['External_Email'], 'phone' => $clientSubData['Mobile'], 'title' => $clientSubData['DisciplineName'], 'id' => $clientSubData['id'], 'nthScheduleMsg' => $nthScheduleMsg, 'nthSchedulesIds' => implode(",",$nthSchedulesIds),'isIneligibleRehire' => isset($clientSubData['CF_NotEligibleForRehire']) ? $clientSubData['CF_NotEligibleForRehire'] :'','ClinicianID' => isset($clientSubData['ClinicianID']) ? $clientSubData['ClinicianID'] :''];
                } else {
                    $clientListData[] = ['label' => $clientSubData['LastName'] . ', ' . $clientSubData['FirstName'] . ' | ' . $clientSubData['ClinicianID'] . ' | ' . $clientSubData['EmploymentStatus'] . ' | ' . $clientSubData['BranchName'],
                    'value' => $clientSubData['FirstName'] . ' ' . $clientSubData['LastName'], 'email' => $clientSubData['External_Email'], 'phone' => $clientSubData['Mobile'], 'title' => $clientSubData['DisciplineName'], 'id' => $clientSubData['id'], 'isIneligibleRehire' => isset($clientSubData['CF_NotEligibleForRehire']) ? $clientSubData['CF_NotEligibleForRehire'] :'','ClinicianID' => isset($clientSubData['ClinicianID']) ? $clientSubData['ClinicianID'] :''];
                }

                    if (!empty($request->aoc_patient_id)) {
                        $date = \Carbon\Carbon::today()->subDays(30);
                        $today = \Carbon\Carbon::today();
                        $lastScheduleData = DB::table("mssql_schedulemaster");
                        $lastScheduleData->leftJoin("mssql_patients", function ($query) {
                            $query->on("mssql_patients.PatientID", "=", "mssql_schedulemaster.PatientID");
                        });
                        $lastScheduleData->leftJoin("mssql_providers", function ($query) {
                            $query->on("mssql_providers.ProviderID", "=", "mssql_schedulemaster.ProviderID");
                        });
                        $lastScheduleData->where("mssql_patients.id", $request->aoc_patient_id);
                        $lastScheduleData->where("mssql_providers.id", $clientSubData['id']);
                        $lastScheduleData->whereBetween("mssql_schedulemaster.ScheduleDate",[$date, $today]);
                        $clientListData[count($clientListData)-1]['scheduled'] = $lastScheduleData->count();
                    } else {
                        $clientListData[count($clientListData)-1]['scheduled'] = -1;
                    }
            }
        } else {
            if (LD::checkFeatureFlag('add-schedule-validation')) {
                    $clientlist = DB::table('candidates')->select(
                        'candidates.id',
                        'candidates.email',
                        'candidates.title',
                        'candidates.firstName',
                        'candidates.lastName',
                        'candidates.homePhone',
                        'candidates.city',
                        'candidates.stateCode',
                        'candidates.mobile',
                        'candidates.application_date',
                        'candidates.address',
                        'candidates.address2',
                        'candidates.state',
                        'candidates.postalCode',
                        DB::raw('(SELECT GROUP_CONCAT(CONCAT(mssql_recruitertracker.Recruiter,"#", IFNULL(recruiting_schedule.appointment_date, ""),"#", IFNULL(mssql_recruitertracker.Branch_Location, "")) separator "**") FROM mssql_recruitertracker LEFT JOIN recruiting_schedule ON recruiting_schedule.tracker_id = mssql_recruitertracker.id WHERE mssql_recruitertracker.aoc_candidate_id = candidates.id AND mssql_recruitertracker.is_active = 1 AND recruiting_schedule.type_id = 1 AND recruiting_schedule.appointment_date >= CURDATE() GROUP BY mssql_recruitertracker.aoc_provider_id ORDER BY mssql_recruitertracker.id DESC) AS ipea_schedule_ids'),
                        DB::raw('(SELECT GROUP_CONCAT(CONCAT(Recruiter,"#",aoc_patient_id,"#",nth_schedule_ids,"#",Client_Name,"#",IFNULL(Agreed_Pay_Rate, "")) separator "**") FROM mssql_recruitertracker WHERE mssql_recruitertracker.aoc_candidate_id = candidates.id AND mssql_recruitertracker.is_active = 1 AND mssql_recruitertracker.nth_schedule_ids IS NOT NULL GROUP BY mssql_recruitertracker.aoc_provider_id ORDER BY mssql_recruitertracker.id DESC) AS nth_schedule_ids')
                    )
                                ->where('candidates.Search_Name', 'like', '%' . $term . '%')
                                ->limit(15)
                                ->get();
            } else {
                    $clientlist = DB::table('candidates')->select(
                        'candidates.id',
                        'candidates.email',
                        'candidates.title',
                        'candidates.firstName',
                        'candidates.lastName',
                        'candidates.homePhone',
                        'candidates.city',
                        'candidates.stateCode',
                        'candidates.mobile',
                        'candidates.application_date',
                        'candidates.address',
                        'candidates.address2',
                        'candidates.state',
                        'candidates.postalCode',
                    )
                                ->where('candidates.Search_Name', 'like', '%' . $term . '%')
                                ->limit(15)
                                ->get();
            }
            $clientlist = json_decode(json_encode($clientlist), true);
            $clientListData = [];
            foreach ($clientlist as $clientSubData) {
                $phone = '';
                $mobile = '';
                    if (!empty($clientSubData['homePhone'])) {
                        $phone = $clientSubData['homePhone'];
                    }
                    if (!empty($clientSubData['mobile'])) {
                        $phone = $clientSubData['mobile'];
                    }
                if (LD::checkFeatureFlag('add-schedule-validation')) {
                    $nthSchedulesIds = [];
                    $nthScheduleMsg = '';
                    $ipeamesg = '';
                    if (!empty($clientSubData['nth_schedule_ids'])) {
                        $nthRecords = explode("**", $clientSubData['nth_schedule_ids']);
                        foreach($nthRecords as $nthRecord) {
                            [$Recruiter, $aoc_patient_id, $nth_schedule_ids, $client_name, $pay_rate] = explode("#", $nthRecord);
                            $nthSchedulesIds = array_merge($nthSchedulesIds, explode(",", $nth_schedule_ids));
                            $nthScheduleMsg .= 'This candidate is being recruited by <b>'.$Recruiter.'</b> for '.$client_name.' at $'.$pay_rate.'/hr with the following schedules:<ul>';
                            $nthScheduleList = H::getHelper('RecruiterTrackerHelper')->getnthschedulebyclientid($aoc_patient_id, $nth_schedule_ids);
                            foreach($nthScheduleList as $nthSchedule) {
                                $nthScheduleMsg .= "<li>" . $nthSchedule['StartDayOfWeek'] . ' - ' . H::getHelper('ConstantHelper')->getSmallTimeFormat($nthSchedule['StartHour'] . ':' . $nthSchedule['StartMinute']) . ' - ' . H::getHelper('ConstantHelper')->getSmallTimeFormat($nthSchedule['EndHour'] . ':' . $nthSchedule['EndMinute']) . '</li>';
                            }
                            $nthScheduleMsg .= '</ul>';

                        }
                        $nthScheduleMsg .= '<p>Please do not recruit the candidate for conflicting schedules.</p>';
                    }
                    if(!empty($clientSubData['ipea_schedule_ids'])){
                        $ipeaRecords = explode("**", $clientSubData['ipea_schedule_ids']);
                        foreach($ipeaRecords as $ipeaRecord){
                            [$Recruiter, $appointment_date, $Branch_Location] = explode("#", $ipeaRecord);
                            $ipeamesg .= '<p>This candidate was scheduled for an IPEA on '.$appointment_date.' in '.$Branch_Location.' by '.$Recruiter.'.</p>';
                        }
                        $nthScheduleMsg .= $ipeamesg;
                    }
                        $clientListData[] = [
                            'label' => $clientSubData['lastName'] . ', ' . $clientSubData['firstName'] . ' | ' . $clientSubData['city'] . ' | ' . $clientSubData['application_date'],
                            'value' => $clientSubData['firstName'] . ' ' . $clientSubData['lastName'],
                            'email' => $clientSubData['email'],
                            'phone' => $phone,
                            'id' => $clientSubData['id'],
                            'title' => $clientSubData['title'],
                            'nthScheduleMsg' => $nthScheduleMsg,
                            'nthSchedulesIds' => implode(",",$nthSchedulesIds),
                            'address' => $clientSubData['address'],
                            'address2' => $clientSubData['address2'],
                            'state' => $clientSubData['state'],
                            'postalCode' => $clientSubData['postalCode'],
                            'city' => $clientSubData['city'],
                        ];
                } else {
                        $clientListData[] = [
                            'label' => $clientSubData['lastName'] . ', ' . $clientSubData['firstName'] . ' | ' . $clientSubData['city'] . ' | ' . $clientSubData['application_date'],
                            'value' => $clientSubData['firstName'] . ' ' . $clientSubData['lastName'],
                            'email' => $clientSubData['email'],
                            'phone' => $phone,
                            'id' => $clientSubData['id'],
                            'title' => $clientSubData['title'],
                            'address' => $clientSubData['address'],
                            'address2' => $clientSubData['address2'],
                            'state' => $clientSubData['state'],
                            'postalCode' => $clientSubData['postalCode'],
                            'city' => $clientSubData['city'],
                        ];
                }
            }
        }
        return response()->json($clientListData);
    }

	public function removeActivity(Request $request) {
		try {
            if ($request->input('id') != '') {
                MssqlRecruitertracker::where('id', $request->input('id'))->update(['is_deleted' => '1', 'is_active' => '0', 'Candidate_Current_Status' => 'Deleted']);
				RecruitingSchedule::where('tracker_id', $request->input('id'))->update(['is_deleted' => '1']);
                return response()->json(['status' => true]);
            } else {
                return response()->json(['status' => false]);
            }
        }catch(\Exception $e){
            return response()->json(['status' => false]);
        }
	}

    public function uploadfiles(Request $request) {
        try {
            $file = $request->file('file');
            $timestamp = time();
            if (!empty($file)) {
                $destinationPath = storage_path() . '/profile';
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $timestamp . '_' . $file->getClientOriginalName());
                return response()->json(['status' => 'true', 'location' =>  env('APP_URL') . '/storage/profile/' . $timestamp . '_' . $file->getClientOriginalName()]);
            }
            return response()->json(['status' => 'false']);
        }catch(\Exception $e){
            return response()->json(['status' => 'false']);
        }
    }

    public function validateNthSchedules(Request $request) {
        try {
            $conflictData =  H::getHelper('RecruiterTrackerHelper')->validateNthSchedules($request->input('nth_schedule_ids'), $request->input('recruiter_nth_schedule_ids'));
            if (count($conflictData) > 0) {
                $conflictMsg = 'Your selections conflict with the following schedules this candidate is being recruited for:<ul>';
                foreach ($conflictData as $schedule) {
                    $conflictMsg .= '<li>'. $schedule->StartDayOfWeek . ' - ' . H::getHelper('ConstantHelper')->getSmallTimeFormat($schedule->StartHour . ':' . $schedule->StartMinute) . ' - ' . H::getHelper('ConstantHelper')->getSmallTimeFormat($schedule->EndHour . ':' . $schedule->EndMinute) . '</li>';
                }
                $conflictMsg .= '</ul>';
                return response()->json(['status' => 'true', 'conflictMsg' => $conflictMsg]);
            }
            return response()->json(['status' => 'false']);

        } catch(\Exception $e) {
            return response()->json(['status' => 'false']);
        }
    }

    public function getPatientState(Request $request) {

        return response()->json(['state' =>H::getHelper("ConstantHelper")->getStateByPatientId($request->patient_id)]);
    }

    public function getIpeaBranchNotes(Request $request)
    {
        $notes = '';
        $data = $request->all();
        $branchLocationDetails = BranchLocations::where('id',$data['ipea_location_id'])->first();
        if(!empty($branchLocationDetails)) {
            if($branchLocationDetails->IPEA_instructions != '') {
                $notes =  $notes.'<div><strong>'.$branchLocationDetails->branch.' Branch Notes:</strong></div>';
                $notes = $notes.'<div class="pt-2">'.$branchLocationDetails->IPEA_instructions.'</div>';
            }
        }
        return response()->json(['notes' => $notes]);
    }

    public function getIpeaNearestBranch(Request $request)
    {
        $address = $request->all();
        unset($address['_token']);

        $emptyAddress = true;
        foreach($address as $key => $value) {
            if ($key == 'country') {
                continue;
            }

            if (!empty($value)) {
                $emptyAddress = false;
            }
        }
        if (!$emptyAddress) {
            $geocoding = Geocoding::processAddress($address, 'api');
            if (!empty($geocoding['lat']) && !empty($geocoding['lng'])) {
                $data = DB::table('branch_locations')->select(DB::raw("(3959 * acos(cos(radians('".$geocoding['lat']."')) * cos(radians(lat)) * cos( radians(lng) - radians('".$geocoding['lng']."')) + sin(radians('".$geocoding['lat']."')) * sin(radians(lat)))) as distance"), 'id')->orderBy('distance', 'asc')->first();
                return response()->json(['status' => 'true', 'id' => $data->id]);
            }
        }
        return response()->json(['status' => 'false']);
    }

    public function getScheduleProvider(Request $request){

        $term = str_replace('  ', ' ', str_replace(',', '', $request->term));
        $providerlist = DB::table('mssql_providers')
        ->select(
            'mssql_providers.id',
            'mssql_providers.FirstName',
            'mssql_providers.ClinicianID',
            'mssql_providers.LastName',
            'mssql_employmentstatus.EmploymentStatus',
            'mssql_facilities.BranchName'
        )
        ->join('mssql_employmentstatus', 'mssql_employmentstatus.EmploymentStatusID', '=', 'mssql_providers.EmploymentStatusID')
        ->join('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')
        ->where('mssql_providers.Search_Name', 'like', '%' . $term . '%')
        ->orWhere('mssql_providers.ClinicianID', 'like', '%' . $term . '%')
        ->where('mssql_providers.is_deleted',0)
        ->limit(15)
        ->get();
        $providerlist = json_decode(json_encode($providerlist), true);
        $providerData = [];
        $cnt = 0;
        foreach ($providerlist as $providerSubData) {
            $providerData[] = ['label' => $providerSubData['LastName'] . ', ' . $providerSubData['FirstName'] . ' | ' . $providerSubData['ClinicianID'] . ' | ' . $providerSubData['EmploymentStatus'] . ' | ' . $providerSubData['BranchName'],
            'value' => $providerSubData['FirstName'] . ' ' . $providerSubData['LastName'],'id' => $providerSubData['id']];
            $cnt++;
        }

        return response()->json($providerData);
    }

    public function getPRNClient(Request $request){

        $term = $request->term;
        $patientslist = DB::table('mssql_patients')
        ->select(
            'mssql_patients.id',
            'mssql_patients.Patient_FirstName',
            'mssql_patients.PatientAccount',
            'mssql_patients.Patient_LastName',
        )
        ->where('mssql_patients.isPRN', 1)
        ->whereNotNull('mssql_patients.isPRN')
        ->where(
            function ($query) use($term) {
                $query->where('mssql_patients.Search_Name', 'like', '%' . $term . '%')
                ->orWhere('mssql_patients.PatientAccount', 'like', '%' . $term . '%');
            }
        )
        ->limit(15)
        ->get();
        $patientslist = json_decode(json_encode($patientslist), true);
        $providerData = [];
        $cnt = 0;
        foreach ($patientslist as $patientsData) {
            $providerData[] = ['label' => $patientsData['Patient_LastName'] . ', ' . $patientsData['Patient_FirstName'] . ' | ' . $patientsData['PatientAccount'],
            'value' => $patientsData['Patient_FirstName'] . ' ' . $patientsData['Patient_LastName'], 'id' => $patientsData['id'], 'PatientAccount' => $patientsData['PatientAccount']];
            $cnt++;
        }

        return response()->json($providerData);
    }

    public function getExceptionAddressed(Request $request) {
        $requestData = $request->all();
        return view('ccc.exceptionaddressedconfirmation',['requestData' => $requestData]);
    }

    public function saveExceptionAddressed(Request $request) {
        try {
            if ($request->input('id') != '') {
                    if ($request->input('bucket') == 'staffed_coverage_shifts_not_updated_in_kantime') {
                        DB::delete("DELETE FROM maps_coverageneeds WHERE id = ?", [$request->input('id')]);
                    } else {
                        if ($request->input('bucket') == 'ccc_intake_coverage_shift_covered_by_perm_nurse' ||
                        $request->input('bucket') == 'ccc_intake_coverage_shift_before_goal_soc_date') {
                            DB::table("coverage_tracker")->where("id", $request->input('id'))->update(['intake_been_addressed' => 1,
                            'intake_addressed_by' => Auth::user()->id,
                            'intake_addressed_at' => date('Y-m-d H:i:s')]);
                        }else {
                            DB::table("mssql_patients")->where("id", $request->input('id'))->update(['exception_addressed_date' => date('Y-m-d H:i:s')]);
                        }
                    }
                return response()->json(["success" => true]);
            } else {
                return response()->json(['status' => false]);
            }
        }catch(\Exception $e){
            return response()->json(['status' => false]);
        }
    }

    public function getApprovedPayRatebyclientid(Request $request){
        $client_id = $request->client_id;
        $getclientdata = DB::table('mssql_patients')->where('id', $client_id)->first();
        $state = $getclientdata->Patient_State;
        $payer = $getclientdata->Payer;
        $TerminalPayerID = $getclientdata->TerminalPayerID;
        $payer_group = $getclientdata->payer_group;
        $service = DB::table('lob')->where('LOBID', $getclientdata->LOBID)->value('LOBName');
        $paysourse_id = DB::table('payment_source')->where('payersource_id', $TerminalPayerID)->value('payersource_id');
        $payerGroup_id = DB::table('payment_source')->where('payersource_id', $TerminalPayerID)->value('payergroup_id');
        $payer_level = NULL;
        if($paysourse_id){
            $payer_level = DB::table('gp_escalations')->select('Level1', 'Level2', 'Level3', 'Level4', 'Level5', 'Level6', 'Level7', 'Level8', 'Level9', 'Level10', 'Level11', 'Level12')->where('payer_id',$paysourse_id)->where('service', $service)->first();
        }
        if(empty($payer_level) && $payerGroup_id){
            $payer_level = DB::table('gp_escalations')->select('Level1', 'Level2', 'Level3', 'Level4', 'Level5', 'Level6', 'Level7', 'Level8', 'Level9', 'Level10', 'Level11', 'Level12')->where('payer_group', $payerGroup_id)->where('service', $service)->first();
        }
        if(empty($payer_level)){
            $payer_level = DB::table('gp_escalations')->select('Level1', 'Level2', 'Level3', 'Level4', 'Level5', 'Level6', 'Level7', 'Level8', 'Level9', 'Level10', 'Level11', 'Level12')->where('state', $state)->where('service', $service)->first();
        }
        return json_encode($payer_level);
    }
    public function getClinicianByPatientId(Request $request)
    {
        try {
            if(!empty($request->input('aoc_patient_id'))) {
            $patientsDetails = MssqlPatients::find($request->input('aoc_patient_id'));
            $clinicianDetails = DB::table("mssql_providers")
            ->select(
                'mssql_providers.id',
                'mssql_providers.FirstName',
                'mssql_providers.LastName',
                'mssql_providers.ClinicianID'
            )
            ->leftJoin('mssql_schedulemaster', 'mssql_schedulemaster.ProviderID', '=', 'mssql_providers.ProviderID')
            ->where('mssql_schedulemaster.ScheduleDate', '>', \Carbon\Carbon::now()->format('Y-m-d'))
            ->where('mssql_schedulemaster.PatientID', $patientsDetails->PatientID)
            ->groupBy('mssql_providers.ClinicianID')
            ->get()
            ->toArray();
            return response()->json($clinicianDetails);
            }
        } catch (\Throwable $th) {
            H::ExcelLog('getClinicianByPatientId Throwable='.$th->getMessage(),'getClinicianByPatientId.log');
        }
    }

    public function status(): Response
    {
        try {
            DB::connection()->getPdo();

            return response()->noContent(Response::HTTP_OK);
        } catch (Exception $e) {

            return response()->noContent(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
