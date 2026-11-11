<?php

namespace App\Http\Controllers;

use App\Events\NTH\NTHScheduleChanged;
use App\Helpers\ConstantHelper;
use App\Helpers\UserPermissionResolver;
use App\Http\Requests\Client\ClientScheduleDropdownRequest;
use App\Http\Resources\Client\ClientScheduleDropdownResource;
use App\Repositories\CustomFields\CustomFieldsReadRepository;
use App\Services\CCC\MonthlyCallsRecalculatorService;
use App\Services\Client\ClientScheduleService;
use Exception;
use Illuminate\Http\Request;
use App\Helpers\H;
use App\Helpers\LD;
use DB;
use App\User;
use App\MssqlPatients;
use App\NeedToHireAccounts;
use App\CustomFields;
use Illuminate\Support\Facades\Auth;
use App\Intake;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use JsonSerializable;

class ClientController extends Controller
{
    /**
     * @param ClientScheduleService $clientScheduleService
     */
    public function __construct(
        protected ClientScheduleService $clientScheduleService,
        private CustomFieldsReadRepository $customFieldsReadRepository,
        private ConstantHelper $constantHelper
    )
    {
    }

    public function showBuildScheduleGrid(Request $request): View
    {
        $nthaccountid = $request->input("nthaccountid");
        $nthSchedules = DB::table("NeedToHire_Schedules")
        ->select("NeedToHire_Schedules.*", 'mssql_patients.is_intake')
        ->leftjoin('NeedToHire_Accounts',
        'NeedToHire_Accounts.id',
        'NeedToHire_Schedules.aoc_nthaccounts_id')
        ->leftjoin('mssql_patients',
        'mssql_patients.id',
        'NeedToHire_Accounts.aoc_patient_id')
        ->where("NeedToHire_Schedules.IsActive",1)
        ->where("aoc_nthaccounts_id",$nthaccountid)
        ->orderByRaw("NeedToHire_Schedules.StartDayOfWeekInt=1, NeedToHire_Schedules.StartDayOfWeekInt")
        ->orderBy("NeedToHire_Schedules.StartHour","ASC")
        ->orderBy("NeedToHire_Schedules.StartMinute","ASC")->get();
        $is_readmit = false;
        if(LD::checkFeatureFlag('disply-accept-intake-coverage-checkbox-on-add-nth-from')){
            $patientId = DB::table("NeedToHire_Schedules")
            ->select('NeedToHire_Accounts.aoc_patient_id')
            ->leftjoin('NeedToHire_Accounts',
            'NeedToHire_Accounts.id',
            'NeedToHire_Schedules.aoc_nthaccounts_id')
            ->where('aoc_nthaccounts_id', '=', $nthaccountid)->first();
            if(!empty($patientId)){
                $id = $patientId->aoc_patient_id;
                $status = DB::table('mssql_patients')
                ->select('mssql_patientstatus.PatientStatus')
                ->leftjoin('mssql_patientstatus', 'mssql_patients.Patient_CurrentStatusID', 'mssql_patientstatus.PatientStatusID')
                ->where('mssql_patients.id', $id)
                ->first();
                if(!empty($status) && ($status->PatientStatus == 'Intake' || $status->PatientStatus == 'Non-Admit' || $status->PatientStatus == 'Discharged')){
                    $is_readmit = true;
                }
            }
        }
        return view('client._buildScheduleGrid',['nthschedule'=>$nthSchedules, 'readmit' => $is_readmit]);
    }

    public function validateSchedule(Request $request) {
        $reqData = $request->all();
        H::ExcelLog(print_r($reqData,true),'dbValidate.log');
        $time = $this->constantHelper->getTimeDifference($reqData['startTime'], $reqData['endTime']);
        $isConflict = false;
        H::ExcelLog('checking ' . $reqData['nthaccountId'],'dbValidate.log');
        foreach($reqData['selectdays'] as $key => $day) {
            H::ExcelLog($day . ' ' . $reqData['startTime'] . ' - ' . $reqData['endTime'],'dbValidate.log');
            switch($day) {
                case "Sunday":
                    $tmp = 1;
                    break;
                case "Monday":
                    $tmp = 2;
                    break;
                case "Tuesday":
                    $tmp = 3;
                    break;
                case "Wednesday":
                    $tmp = 4;
                    break;
                case "Thursday":
                    $tmp = 5;
                    break;
                case "Friday":
                    $tmp = 6;
                    break;
                case "Saturday":
                    $tmp = 7;
                    break;
                default:
                    $tmp = 0;
                    break;
            }
            $startAt = $tmp . $reqData['startTime'];
            if($reqData['endTime'] < $reqData['startTime']){
                $tmp = $tmp + 1;
                if($tmp > 7) $tmp = 1;
            }
            $endAt = $tmp . $reqData['endTime'];
            $itemExclude = "";
            $itemIdArr= [];
            if(!empty($reqData['itemId'])) {
                $itemIdArr = explode(",",$reqData['itemId']);
                $questionMarkString = H::generateQuestionMarksArray($itemIdArr);
                $itemExclude=' and id not in('.$questionMarkString.')';
            }
            $query = "with cte as
                        (SELECT StartDayOfWeek, StartDayOfWeekInt, StartHour, StartMinute, EndHour, EndMinute,
                        concat(right(concat('0',StartHour),2),':',right(concat('0',StartMinute),2)) as StartTime,
                        concat(StartDayOfWeekInt,concat(right(concat('0',StartHour),2),':',
                        right(concat('0',StartMinute),2))) as StartAt,
                        concat(right(concat('0',EndHour),2),':',right(concat('0',EndMinute),2)) as ending,
                        case when StartAndEndOnSameDay then
                            concat(StartDayOfWeekInt,concat(right(concat('0',EndHour),2),':',
                            right(concat('0',EndMinute),2)))
                        else
                            case when StartDayOfWeekInt = 7 then
                                    concat('1',concat(right(concat('0',EndHour),2),':',right(concat('0',EndMinute),2)))
                            else
                                concat(StartDayOfWeekInt+1,concat(right(concat('0',EndHour),2),':',
                                right(concat('0',EndMinute),2)))
                            end
                        end as endAt

                        FROM `NeedToHire_Schedules`
                        WHERE aoc_nthaccounts_id = ? ".$itemExclude." and IsActive = 1)
                        select * from cte where StartAt < ? and endAt > ?;";
            H::ExcelLog($query,'dbValidate.log');
            if($itemExclude != "") {
                $dbConflict = DB::select($query,array_merge([$reqData['nthaccountId']],$itemIdArr,[$endAt,$startAt]));
            } else {
                $dbConflict = DB::select($query,[$reqData['nthaccountId'],$endAt,$startAt]);
            }
            foreach ($dbConflict as $found) {
                H::ExcelLog(print_r($found,true),'dbValidate.log');
                H::ExcelLog('conflict with ' . $found->StartDayOfWeek . ' ' . $found->StartTime . ' - ' . $found->ending,'dbValidate.log');
                return response()->json(['isConflict'=>1]);
            }
        }
        return response()->json(['isConflict'=>0]);
    }

    public function saveBuildScheduleForm(Request $request) {
        $rq = $request->all();
        $selectDays = [];
        $startDayOfWeekInt = ['Sunday'=>'1','Monday'=>2,'Tuesday'=>3,'Wednesday'=>4,'Thursday'=>5,'Friday'=>6,'Saturday'=>7];
        $startTime = '';
        $endTime = '';
        $scheduleType = '';
        $address = '';
        $reasonCategory = '';
        $reason = '';
        $aoc_nthaccounts_id='';
        $itemId = '';
        $mode = 'add';
        $removeallschedules = 0;
        $scheduleType = 0;
        foreach($rq['d'] as $k => $val) {
            if($val['name']=='selectdays[]') {
                $selectDays[] = $val['value'];
            }
            if($val['name']=='start_time') {
                $startTime = $val['value'];
            }
            if($val['name']=='end_time') {
                $endTime = $val['value'];
            }
            if($val['name']=='address') {
                $address = $val['value'];
            }
            if($val['name']=='reason_category') {
                $reasonCategory = $val['value'];
            }
            if($val['name']=='reason') {
                $reason = $val['value'];
            }
            if($val['name']=='aoc_nthaccounts_id') {
                $aoc_nthaccounts_id = $val['value'];
            }
            if($val['name']=='removeallschedules') {
                $removeallschedules = $val['value'];
            }
            if($val['name']=='item_id') {
                $itemId = $val['value'];
            }
            if($val['name']=='mode') {
                $mode = $val['value'];
            }
            if($val['name']=='flexibleschedule' && !empty($val['value'])) {
                $scheduleType = 1;
            }
        }

        event(new NTHScheduleChanged($mode, $aoc_nthaccounts_id));

        if($reasonCategory!='') {
            $reason = $reasonCategory . ' - ' . $reason;
        }
        if(LD::checkFeatureFlag('add-notification-for-NTH-Schedule-change')){
            if(!empty($aoc_nthaccounts_id)){
                H::getHelper('H')->createNotificationForNth($aoc_nthaccounts_id);
            }
        }
		H::ExcelLog('mode -'.$mode,'NthSchedules.log');
        if($mode=='editall' || $mode=='edit'){
            $time = $this->constantHelper->getTimeDifference($startTime, $endTime);

            $loggedInUser = H::getLoggedInUser();
            $items = explode(",",$itemId);

            foreach($items as $item) {
                $currentData = DB::table('NeedToHire_Schedules')->where("id",$item)->first();

                $shiftType = $this->constantHelper->getShiftType($currentData->StartDayOfWeek, $time['startAMPM'], $time['endAMPM']);
                $data = [];
                $data['aoc_nthaccounts_id'] = $aoc_nthaccounts_id;
                $data['StartHour'] = $time['starthour'];
                $data['StartMinute'] = $time['startminute'];
                $data['EndHour'] = $time['endhour'];
                $data['EndMinute'] = $time['endminute'];

                $data['StartAndEndOnSameDay'] = $time['StartAndEndOnSameDay'];
                $data['ModifiedOn'] = date('Y-m-d');
                $data['ModifiedBy'] = $loggedInUser['email'];
                $data['updated_at'] = date('Y-m-d H:i:s');
                $data['is_updated'] = 1;
                $data['ModificationReason'] = $reason;
                $data['updated_by'] = Auth::user()->id;
               /* if($time['totalminutes']==0) {
                    $data['IsActive']=0;

                    $checkSchedulesExistingRecords = DB::table('NeedToHire_Schedules')->where("IsActive",1)->where("aoc_nthaccounts_id", $aoc_nthaccounts_id)->count();
                    if ($checkSchedulesExistingRecords == 1) {
                        NeedToHireAccounts::where('id', $aoc_nthaccounts_id)->update(["updated_at"=>date('Y-m-d H:i:s'),'IsActive' => '0','updated_by'=>Auth::user()->id]);
                        DB::table("maps_nth")->where("aoc_nthaccounts_id", $aoc_nthaccounts_id)->update(['IsActive' => '0']);
                    }
                } */
                $data['ShiftHours'] = $time['hours'];
                $data['ShiftTypeDescription'] = $shiftType->ShiftTypeDescription;
                $data['ScheduleType'] = $scheduleType;
                DB::table('NeedToHire_Schedules')->where("id",$item)->update($data);
                H::ExcelLog('edit | editall'.print_r($data,true),'NthSchedules.log');
                $this->constantHelper->setNTHAuditRecord($item, 'edit', $currentData);
            }
            //Added activity history
            $notes = H::getLoggedInUser()['name'].' modified schedules on the NTH for reason: '.$reason;
            $notesData = ['entity' => 'nthAccount', 'entity_id' => $aoc_nthaccounts_id, 'notes' =>$notes, 'completed' => 1];
            H::getHelper('ActivityNotes')->setActivityNotes($notesData);

        } else if($mode=='removeall') {
            $items = explode(",",$itemId);
            foreach($items as $item) {
				H::ExcelLog('NeedToHire_Schedules ID -'.$item,'NthSchedules.log');
                $currentData = DB::table('NeedToHire_Schedules')->where("id",$item)->first();

                DB::table("NeedToHire_Schedules")->where("id",$item)->update(["updated_at"=>date('Y-m-d H:i:s'),"IsActive"=>"0",'RemovalReason'=>$reason,'updated_by'=>Auth::user()->id]);
                H::ExcelLog('removeall '.print_r(["updated_at"=>date('Y-m-d H:i:s'),"IsActive"=>"0",'RemovalReason'=>$reason,'updated_by'=>Auth::user()->id],true),'NthSchedules.log');
                $this->constantHelper->setNTHAuditRecord($item, 'remove', $currentData);
                H::getHelper('NTHChecklist')->ignoreNTHScheduleReason($reason,$currentData);
            }
            /*if ($removeallschedules == 1) {
                NeedToHireAccounts::where('id', $aoc_nthaccounts_id)->update(["updated_at"=>date('Y-m-d H:i:s'),'IsActive' => '0','updated_by'=>Auth::user()->id]);
                DB::table("maps_nth")->where("aoc_nthaccounts_id", $aoc_nthaccounts_id)->update(['IsActive' => '0']);
            }*/
            //Added activity history
            $notes = H::getLoggedInUser()['name'].' removed schedules on the NTH for reason: '.$reason;
            $notesData = ['entity' => 'nthAccount', 'entity_id' => $aoc_nthaccounts_id, 'notes' =>$notes, 'completed' => 1];
            H::getHelper('ActivityNotes')->setActivityNotes($notesData);
        } else if($mode!='remove') {
            $time = $this->constantHelper->getTimeDifference($startTime, $endTime);
            $loggedInUser = H::getLoggedInUser();
            foreach($selectDays as $day) {

                $shiftType = $this->constantHelper->getShiftType($day, $time['startAMPM'], $time['endAMPM']);
                $data = [];
                $data['aoc_nthaccounts_id'] = $aoc_nthaccounts_id;
                $data['StartDayOfWeek'] = $day;
                $data['StartDayOfWeekInt'] = $startDayOfWeekInt[$day];
                $data['StartHour'] = $time['starthour'];
                $data['StartMinute'] = $time['startminute'];
                $data['EndHour'] = $time['endhour'];
                $data['EndMinute'] = $time['endminute'];
                $data['ShiftType'] = $shiftType->ShiftType;
                $data['StartAndEndOnSameDay'] = $time['StartAndEndOnSameDay'];
                $data['CreatedOn'] = date('Y-m-d');
                $data['CreatedBy'] = $loggedInUser['email'];
                $data['IsActive'] = '1';
                $data["updated_at"]= date('Y-m-d H:i:s');
                $data['updated_by']=Auth::user()->id;
                if(empty($itemId)) {
                    $data['AdditionReason'] = $reason;
                } else {
                    $data['ModificationReason'] = $reason;
                }
                $data['ShiftHours'] = $time['hours'];
                $data['ShiftTypeDescription'] = $shiftType->ShiftTypeDescription;
                $data['ScheduleType'] = $scheduleType;
                /*if($time['totalminutes']==0) {
                    $data['IsActive']=0;

                    $checkSchedulesExistingRecords = DB::table('NeedToHire_Schedules')->where("IsActive",1)->where("aoc_nthaccounts_id", $aoc_nthaccounts_id)->count();
                    if ($checkSchedulesExistingRecords == 1) {
                        NeedToHireAccounts::where('id', $aoc_nthaccounts_id)->update(["updated_at"=>date('Y-m-d H:i:s'),'IsActive' => '0','updated_by'=>Auth::user()->id]);
                        DB::table("maps_nth")->where("aoc_nthaccounts_id", $aoc_nthaccounts_id)->update(['IsActive' => '0']);
                    }
                }*/

                if(!empty($itemId)) {
                    DB::table('NeedToHire_Schedules')->where("id",$itemId)->update($data);
                    H::ExcelLog('remove '.print_r($data,true),'NthSchedules.log');
                    $this->constantHelper->setNTHAuditRecord($itemId, 'edit');
                } else {
                    $data["created_at"]= date('Y-m-d H:i:s');
                    $data['created_by']=Auth::user()->id;
                    $newItemId = DB::table('NeedToHire_Schedules')->insertGetId($data);
                    H::ExcelLog('Added - ID -'.$newItemId.'='.print_r($data,true),'NthSchedules.log');
                    $this->constantHelper->setNTHAuditRecord($newItemId, 'add', []);

                    //NeedToHireAccounts::where('id', $aoc_nthaccounts_id)->update(["updated_at"=>date('Y-m-d H:i:s'),'IsActive' => '1','updated_by'=>Auth::user()->id]);
                    //DB::table("maps_nth")->where("aoc_nthaccounts_id", $aoc_nthaccounts_id)->update(['IsActive' => '1']);

                    //Added activity history
                    $notes = H::getLoggedInUser()['name'].' added schedules on the NTH for reason: '.$reason;
                    $notesData = ['entity' => 'nthAccount', 'entity_id' => $aoc_nthaccounts_id, 'notes' =>$notes, 'completed' => 1];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                }
            }
        } else {
			H::ExcelLog('NeedToHire_Schedules ID -'.$itemId,'NthSchedules.log');
            $currentData = DB::table('NeedToHire_Schedules')->where("id",$itemId)->first();

            DB::table("NeedToHire_Schedules")->where("id",$itemId)->update(["updated_at"=>date('Y-m-d H:i:s'),"IsActive"=>"0",'RemovalReason'=>$reason]);
            H::ExcelLog('remove '.print_r(["updated_at"=>date('Y-m-d H:i:s'),"IsActive"=>"0",'RemovalReason'=>$reason],true),'NthSchedules.log');
            $this->constantHelper->setNTHAuditRecord($itemId, 'remove', $currentData);
            H::getHelper('NTHChecklist')->ignoreNTHScheduleReason($reason,$currentData);
            /*if ($removeallschedules == 1) {
                NeedToHireAccounts::where('id', $aoc_nthaccounts_id)->update(['IsActive' => '0']);
                DB::table("maps_nth")->where("aoc_nthaccounts_id", $aoc_nthaccounts_id)->update(['IsActive' => '0']);
            }*/
            //Added activity history
            $notes = H::getLoggedInUser()['name'].' removed schedules on the NTH for reason: '.$reason;
            $notesData = ['entity' => 'nthAccount', 'entity_id' => $aoc_nthaccounts_id, 'notes' =>$notes, 'completed' => 1];
            H::getHelper('ActivityNotes')->setActivityNotes($notesData);
        }

        if(empty($aoc_nthaccounts_id) && !empty($currentData->aoc_nthaccounts_id ?? null)) {
            $aoc_nthaccounts_id = $currentData->aoc_nthaccounts_id;
            H::ExcelLog('FALLBACK applied, aoc_nthaccounts_id='.$aoc_nthaccounts_id, 'NthSchedules.log');
            H::ExcelLog('FALLBACK applied, mode='.$mode, 'NthSchedules.log');
            H::ExcelLog('FALLBACK applied, itemId='.$itemId, 'NthSchedules.log');
        }

        if($aoc_nthaccounts_id) {
			H::ExcelLog('aoc_nthaccounts_id -'.$aoc_nthaccounts_id,'NthSchedules.log');
            H::getHelper("NTHChecklist")->setNTHScheduleToMap($aoc_nthaccounts_id);
            H::getHelper("NTHChecklist")->setClientAccountStatus($aoc_nthaccounts_id);

        }

    }
    public function getBuildScheduleForm(Request $request) {
        $clientDetails = MssqlPatients::select("mssql_patients.*")
        ->leftJoin('NeedToHire_Accounts', 'mssql_patients.id', '=', 'NeedToHire_Accounts.aoc_patient_id')
        ->where("NeedToHire_Accounts.id",$request->input('id'))->first();
        $patientName = $clientDetails->PatientName;
        return view('client.buildschedule', compact('patientName'));
    }

    public function getClientDetails(Request $request, UserPermissionResolver $permissionResolver) {
        $canView = true;
        if (Str::contains($request->server('HTTP_REFERER'), 'notification/list')
            && $request->input('defaultTab') === 'nthScheduleChange') {
            $canView = $permissionResolver->isRoleHasPermissionNew('notification.list');
        }

        try {
            if ($request->input('id') != '') {
                 $clientDetails = MssqlPatients::select("mssql_patients.*","mssql_facilities.FacilityName",DB::raw("CONCAT(FLOOR((TIMESTAMPDIFF(MONTH, mssql_patients.Patient_DOB, CURDATE()) / 12)), ' Years ') AS Patient_age"))
                ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
                ->where("mssql_patients.id",$request->input('id'))->first();

                if($clientDetails->PatientName == ''){
                    $clientDetails->PatientName = $clientDetails->Patient_FirstName.' '.$clientDetails->Patient_LastName;
                }
				$intakeExists = Intake::where('aoc_patient_id', $request->input('id'))->first();
				if ($intakeExists) {
					$clientDetails->setAttribute('intake_bucket', $intakeExists->intake_bucket);
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

                if (isset($clientDetails->aoc_cf_male_nurses) && isset($clientDetails->aoc_cf_female_nurses)) {
                    if ($clientDetails->aoc_cf_female_nurses === 'No') {
                        $genderPref = 'Male Only';
                    } elseif ($clientDetails->aoc_cf_male_nurses === 'No') {
                        $genderPref = 'Female Only';
                    }
                } else {
                    $preferenceList = strtolower($clientDetails->PreferenceList);

                    if (str_contains($preferenceList, 'does not accept female nurses')) {
                        $genderPref = 'Male Only';
                    } elseif (!str_contains($preferenceList, 'male nurse accepted')) {
                        $genderPref = 'Female Only';
                    }
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
                $needToHireChecklistDetails= $recruiter_data = [];
                $needToHireQuery = DB::table('NeedToHire_Accounts')->select("NeedToHire_Checklist.*",'NeedToHire_Accounts.*', DB::raw("DATEDIFF(now(),NeedToHire_Accounts.created_at) as daysInNTH"),DB::raw("NeedToHire_Accounts.id as needToHireId"),DB::raw("NeedToHire_Checklist.id as needToHireChecklistId"))
                ->leftJoin('NeedToHire_Checklist', 'NeedToHire_Checklist.aoc_nthaccounts_id', '=', 'NeedToHire_Accounts.id');

                $nthAccountId = $request->input('nthAccountId');
                if (!empty($nthAccountId)) {
                    $needToHireChecklistDetails = $needToHireQuery->where('NeedToHire_Accounts.id', $nthAccountId)->first();
                } else {
                    $needToHireChecklistDetails = $needToHireQuery->where("NeedToHire_Accounts.aoc_patient_id",$request->input('id'))->orderBy('NeedToHire_Accounts.IsActive','desc')->orderBy('NeedToHire_Accounts.id','desc')->first();
                }
                $totalShiftHours = 0;
                $nthScheduleTable = $needToHireAuditList = $socialMediaAdDetail = $recruitingDetails = [];
                $emergencyContacts = $this->constantHelper->getClientEmergencyContactDetails($request->input('id'))->toArray();
                $clientDetails['EmergencyContact'] = $emergencyContacts;
                if(!empty($needToHireChecklistDetails)) {
                    $nthScheduleTable = DB::table("NeedToHire_Schedules")->select("*")->where("IsActive",1)->where("aoc_nthaccounts_id",$needToHireChecklistDetails->needToHireId)->orderByRaw("NeedToHire_Schedules.StartDayOfWeekInt=1, NeedToHire_Schedules.StartDayOfWeekInt")->orderBy("NeedToHire_Schedules.StartHour","ASC")->orderBy("NeedToHire_Schedules.StartMinute","ASC")->get();
                    $nthScheduleTableArray = json_decode(json_encode($nthScheduleTable), true );
                    $totalShiftHours = array_sum(array_column($nthScheduleTableArray, 'ShiftHours'));
                    if (LD::checkFeatureFlag('nth-audit')) {
                    $needToHireAuditList = DB::table("NeedToHire_Audit")->select("users.name",
                        "NeedToHire_Audit.created_at as ChangedOn",
                        DB::raw("DATE_FORMAT(NeedToHire_Audit.created_at,'%m/%d/%Y %h:%i %p') AS created_at"),
                        "users.name as ChangedBy",
                        "NeedToHire_Audit.StartDayOfWeek as Day",
                        DB::raw("TIME_FORMAT(concat(StartHour,StartMinute),'%h:%i %p') AS StartTime"),
                        DB::raw("TIME_FORMAT(concat(EndHour,EndMinute),'%h:%i %p') AS EndTime"),
                        DB::raw("CASE WHEN NeedToHire_Audit.Type = 'M' THEN 'Modified'
                        WHEN NeedToHire_Audit.Type = 'A' THEN 'Added'
                        WHEN NeedToHire_Audit.Type ='R' THEN 'Removed'  END as DisplayType"),
                        "NeedToHire_Audit.ShiftHours",
                        "NeedToHire_Audit.Type",
                        "NeedToHire_Audit.Reason",
                        "NeedToHire_Audit.NetChangeInHours",
                        DB::raw("IF(NeedToHire_Audit.NetChangeInHours > 0,concat('+',NetChangeInHours),NetChangeInHours) as NetChangeInHours")
                        )->leftJoin("users","NeedToHire_Audit.created_by", "=", "users.id")->where("NeedToHire_Audit.aoc_nthaccounts_id",$needToHireChecklistDetails->needToHireId)->orderBy('NeedToHire_Audit.created_at','desc')->get();
                        $needToHireAuditList = H::objToArr($needToHireAuditList);
                    }
                    if(LD::checkFeatureFlag('add-recruiter-data-client-care')){
                        if(!empty($needToHireChecklistDetails->user_id)){
                            $user = User::find($needToHireChecklistDetails->user_id);
                            if(!empty($user)){
                                $recruiter_data['designation'] = 'Recruiter';
                                $recruiter_data['profilePic'] = $user->profile_picture;
                                $recruiter_data['name'] = $user->name;
                            }
                            if(LD::checkFeatureFlag('add-recruiting-team-lead-and-recruiting-admin-in-care-team')){
                                $recruitingDetail = NeedToHireAccounts::select('users.recruiting_teamleader',
                                'users.recruiting_admin', 'rtl.profile_picture as rtl_pc', 'rtl.name as rtl_name',
                                 'ra.profile_picture as ra_pc', 'ra.name as ra_name')
                                ->leftJoin('users', 'users.id', '=', 'NeedToHire_Accounts.user_id')
                                ->leftJoin('users as rtl', 'rtl.id', '=', 'users.recruiting_teamleader')
                                ->leftJoin('users as ra', 'ra.id', '=', 'users.recruiting_admin')
                                ->where('NeedToHire_Accounts.aoc_patient_id', '=', $request->input('id'))
                                ->where('NeedToHire_Accounts.isActive', 1)
                                ->get();
                                if(!empty($recruitingDetail)){
                                    if(isset($recruitingDetail[0]['rtl_name'])){
                                        $recruitingDetails[] =
                                        [
                                            'designation' => 'Recruiting Team Leader',
                                            'profilePic' => $recruitingDetail[0]['rtl_pc'],
                                            'name' => $recruitingDetail[0]['rtl_name'],
                                        ];
                                    }
                                    if(isset($recruitingDetail[0]['ra_name'])){
                                        $recruitingDetails[] =
                                        [
                                            'designation' => 'Recruiting Admin',
                                            'profilePic' => $recruitingDetail[0]['ra_pc'],
                                            'name' => $recruitingDetail[0]['ra_name'],
                                        ];
                                    }
                                }
                            }
                        }
                    }
                    if(LD::checkFeatureFlag('sponsored-ad-for-nth-client')) {
                        $socialMediaAdDetail = H::getHelper("SocialMedia")->getSocialMediaForPatient($needToHireChecklistDetails->needToHireId);
                    }
                }
               /* $recruitingActivity = [];
                $recruitingActivity = DB::table('recruiting_schedule')
                ->leftJoin('schedule_types', 'recruiting_schedule.type_id', '=', 'schedule_types.id')
                ->leftJoin('mssql_recruitertracker', 'mssql_recruitertracker.aoc_patient_id', '=', 'recruiting_schedule.aoc_patient_id')
                ->select('schedule_types.type as type','recruiting_schedule.*','mssql_recruitertracker.Candidate','mssql_recruitertracker.Skillset')->where('recruiting_schedule.aoc_patient_id',$clientDetails->id)->whereDate('appointment_date','<',date('Y-m-d'))->orderBy('appointment_date','desc')->get();
                */
                $upcomingAppointments = [];
                $upcomingAppointments = DB::table('recruiting_schedule')
                ->leftJoin('schedule_types', 'recruiting_schedule.type_id', '=', 'schedule_types.id')
                ->leftJoin('mssql_recruitertracker', 'mssql_recruitertracker.id', '=', 'recruiting_schedule.tracker_id')
                ->select('schedule_types.type as type','recruiting_schedule.*','mssql_recruitertracker.Candidate','mssql_recruitertracker.Skillset', 'mssql_recruitertracker.Schedule')->where('recruiting_schedule.aoc_patient_id',$clientDetails->id)->whereDate('appointment_date','>=',date('Y-m-d'))->orderBy('appointment_date','asc')->get();

                $pastAppointments = [];
                if(LD::checkFeatureFlag('client-profile-add-past-appointments')){
                    $pastAppointments = DB::table('recruiting_schedule')
                    ->leftJoin('schedule_types', 'recruiting_schedule.type_id', '=', 'schedule_types.id')
                    ->leftJoin('mssql_recruitertracker', 'mssql_recruitertracker.id', '=', 'recruiting_schedule.tracker_id')
                    ->select('schedule_types.type as type','recruiting_schedule.*','mssql_recruitertracker.Candidate','mssql_recruitertracker.Skillset')->where('recruiting_schedule.aoc_patient_id',$clientDetails->id)->whereDate('appointment_date','<',date('Y-m-d'))->orderBy('appointment_date','desc')->get();
                }
                $careTeamIds = [
					'auth_nurse_id' => 'Authorization Clinician',
					'dbo_id' => 'Director of Branch Operations',
					'ccc_id' => 'Client Care Coordinator',
					'ic_id' => 'Intake Coordinator',
					'r_id' => 'Recruiter',
					'ccm_id' => 'Clinical Care Manager',
					'bd_id' => 'Business Development Rep'
				];
                $careTeam = [];
                $i=0;
                foreach($careTeamIds as $k => $v){
                    if($clientDetails->{$k} != '' && $clientDetails->{$k} != 1){
                        $user = User::find($clientDetails->{$k});
                        if(!empty($user)){
                            $careTeam[$i]['designation'] = $v;
                            $careTeam[$i]['profilePic'] = $user->profile_picture;
                            $careTeam[$i]['name'] = $user->name;
                            $i++;
                        }
                    }
                }

				$dbo_ids = H::getHelper("UserPermissionResolver")
                            ->getUserIdByFacilityAndRole($clientDetails->aoc_facility_id, 'Director of Branch Operations');
                if (count($dbo_ids) > 0) {
                    foreach($dbo_ids as $rdo_id) {
                        $user = User::find($rdo_id);
                        if(!empty($user)){
                            $careTeam[$i]['designation'] = 'Director of Branch Operations';
                            $careTeam[$i]['profilePic'] = $user->profile_picture;
                            $careTeam[$i]['name'] = $user->name;
                            $i++;
                        }
                    }
                }

                $rdn_ids = H::getHelper("UserPermissionResolver")
                            ->getUserIdByFacilityAndRole($clientDetails->aoc_facility_id, 'Director of Nursing');
                if (count($rdn_ids) > 0) {
                    foreach($rdn_ids as $rdn_id) {
                        $user = User::find($rdn_id);
                        if(!empty($user)){
                            $careTeam[$i]['designation'] = 'Director of Nursing';
                            $careTeam[$i]['profilePic'] = $user->profile_picture;
                            $careTeam[$i]['name'] = $user->name;
                            $i++;
                        }
                    }
                }

                if(LD::checkFeatureFlag('add-recruiter-data-client-care')){
                    if(!empty($recruiter_data)){
                        $careTeam =  array_merge($careTeam,[$recruiter_data]);
                        $i = $i + 1;
                    }
                }
                if(LD::checkFeatureFlag('add-recruiting-team-lead-and-recruiting-admin-in-care-team')){
                    if(!empty($recruitingDetails)){
                        $careTeam =  array_merge($careTeam, $recruitingDetails);
                        $i = $i + count($recruitingDetails);
                    }
                }
                $halos= [];
                if(LD::checkFeatureFlag('halos-tab-in-client-profile')){
                    //Fetching the halos details
                    $halos = DB::table('notes')
                        ->leftJoin('users', 'users.id', '=', 'notes.created_by')
                        ->select(
                            'notes.id',
                            'notes.notes',
                            'notes.misc3',
                            'notes.misc4',
                            'users.name as created_by_name',
                            'notes.created_at'
                        )
                        ->where('notes.entity', 'HALOS Moment')
                        ->where('notes.entity_id', $request->input('id'))
                        ->get();
                    foreach ($halos as $key => $h) {
                        $h->path = env('MOBILE_API_URL'). '/storage/' . $h->misc4;
                    }
                }
                $mobileExistingUser = [];
                if(LD::checkFeatureFlag('add-mobile-app-user')){
                    if(H::canPermissions('mobile.addnewuser')){
                        $mobileExistingUser = DB::table('external_users')->select("external_users.email","external_users.mobile_number","external_users.name","external_user_patients.aoc_patient_id","external_users.id")->join('external_user_patients','external_users.id','=','external_user_patients.external_user_id')->where('external_user_patients.aoc_patient_id',$clientDetails->id)->where('external_users.status', 1)->where('external_user_patients.status', 1)->get();
                    }
                }
                $ReferringProviders  = [];
                if(LD::checkFeatureFlag('clinical-tab-in-client-profile')){
                    if($clientDetails->PrimarycarePhysicianID > 0) {
                        $ReferringProviders = DB::table('ReferringProviders')->select(DB::raw("CONCAT(ReferringProviders.FirstName,' ',ReferringProviders.LastName) as fullName"))->where('ReferringProviders.BillingReferringProviderID',$clientDetails->PrimarycarePhysicianID)->first();
                    }
                }
                $feature_photos = [];
                if(LD::checkFeatureFlag('show_feature_photo_client')){
                    $feature_photos = DB::table('aoc_documents')->where('entity_type', 'mobileapp')->where('document_type', 'feature_photos')->where('entity_id', $request->input('id'))->get();
                    foreach($feature_photos as $key => $fphotos){
                        $filename = $fphotos->document_filename;
                        if(!empty($filename)  && file_exists(storage_path('feature_photos/' . $filename))){
                            $fphotos->document_filename = env('APP_URL').'/storage/feature_photos/'.$filename;
                        }else if($fphotos->document_link != ''){
                            $filename = $this->constantHelper->convertBase64ToImageAndStore($fphotos->document_link, $fphotos->id, 'feature_photos');
                            if($filename != ''){
                                $fphotos->document_filename = env('APP_URL').'/storage/feature_photos/'.$filename;
                            }else{
                                $fphotos->document_filename = env('APP_URL').'/storage/feature_photos/feature_photos.png';
                            }
                        }
                    }
                }
                $intakeData = $newrefarray = [];
                if(LD::checkFeatureFlag('add-intake-tab-in-client-profile')){
                    $intakelob = DB::table('mssql_patients')
                    ->leftJoin('intake', 'intake.aoc_patient_id', '=', 'mssql_patients.id')
                    ->leftJoin('lob', function ($join) {
                        $join->on('lob.LOBID', '=', DB::raw('COALESCE(intake.LOBID, mssql_patients.LOBID)'));
                    })
                    ->select(
                        'intake.id',
                        DB::raw('COALESCE(lob.LOBName, \'Unknown\') as LOBName')
                    )
                    ->where('mssql_patients.id', '=', $request->input('id'))
                    ->orderBy('intake.id')
                    ->get();
                    foreach($intakelob as $data){
                        $newrefarray[$data->id] = $data->LOBName;
                    }

                    $requestedLobId = $request->input('lobId');
                    $lobSource = $request->input('lobSource');
                    $firstId = '';

                    $lobResolutionEnabled = $lobSource === 'intake_dashboard'
                        || LD::checkFeatureFlag('national-intake-report-open-to-lob');

                    if (!empty($requestedLobId) && $lobResolutionEnabled) {
                        $firstId = Intake::where('aoc_patient_id', $request->input('id'))
                            ->where('LOBID', $requestedLobId)
                            ->orderBy('created_at', 'desc')
                            ->value('id');
                    }

                    if (empty($firstId) && !empty($newrefarray)) {
                        $firstId = array_key_first($newrefarray);
                    }

                    $newref = false;
                    if (!empty($firstId)) {
                        $newref = $this->constantHelper->checkNewRefOnLob($firstId);
                    }

                    $intakeData = Intake::select(
                        (
                            LD::checkFeatureFlag('move-first-billable-shift-fields-to-intake-table') ?
                            DB::raw("(CASE
                                        -- Check if a valid ScheduleDate exists in mssql_schedulemaster
                                            WHEN EXISTS (
                                                SELECT 1
                                                FROM mssql_schedulemaster sm
                                                WHERE intake.intake_admission_visit = sm.CGTaskID
                                                    AND sm.ScheduleDate IS NOT NULL
                                                    AND sm.ScheduleDate != ''
                                                    AND DATE(sm.ScheduleDate) != '0000-00-00'
                                            )
                                            THEN (
                                                SELECT sm.ScheduleDate
                                                FROM mssql_schedulemaster sm
                                                WHERE intake.intake_admission_visit = sm.CGTaskID
                                                LIMIT 1
                                            )
                                            -- Use admission_visit_date if ScheduleDate is invalid or not found
                                            WHEN intake.admission_visit_date IS NOT NULL
                                                AND intake.admission_visit_date != ''
                                                AND DATE(intake.admission_visit_date) != '0000-00-00'
                                            THEN intake.admission_visit_date
                                            -- Default to ActualIntakeDate
                                            ELSE mssql_patients.ActualIntakeDate
                                        END
                                    ) AS intake_created_date") :
                            DB::raw("(CASE
                                        -- Check if a valid ScheduleDate exists in mssql_schedulemaster
                                            WHEN EXISTS (
                                                SELECT 1
                                                FROM mssql_schedulemaster sm
                                                WHERE mssql_patients.intake_admission_visit = sm.CGTaskID
                                                    AND sm.ScheduleDate IS NOT NULL
                                                    AND sm.ScheduleDate != ''
                                                    AND DATE(sm.ScheduleDate) != '0000-00-00'
                                            )
                                            THEN (
                                                SELECT sm.ScheduleDate
                                                FROM mssql_schedulemaster sm
                                                WHERE mssql_patients.intake_admission_visit = sm.CGTaskID
                                                LIMIT 1
                                            )
                                            -- Use admission_visit_date if ScheduleDate is invalid or not found
                                            WHEN intake.admission_visit_date IS NOT NULL
                                                AND intake.admission_visit_date != ''
                                                AND DATE(intake.admission_visit_date) != '0000-00-00'
                                            THEN intake.admission_visit_date
                                            -- Default to ActualIntakeDate
                                            ELSE mssql_patients.ActualIntakeDate
                                        END
                                    ) AS intake_created_date")
                        ),
                        'lob.LOBName',
                        'intake.AttendingPhysicianID AS ReferringPhysician',
                        'intake.ReferralDate',
                        'intake.ReferralSourceID',
                        'intake.location',
                        'intake.intake_nurse_id',
                        'intake.kantime_url',
                        'mssql_patients.PatientID',
                        'mssql_patients.AttendingPhysicianID AS acuAttendingPhysicianID',
                        'mssql_patients.PrimarycarePhysicianID',
                        'mssql_patients.MedicaidNumber',
                        'mssql_patients.transport_level_assistance',
                        'mssql_patients.client_classification',
                        'mssql_patients.marital_status',
                        'mssql_patients.Ethnicity',
                        'mssql_patients.Languages',
                        'mssql_patients.PlaceOfService',
                        'mssql_patients.living_situation',
                        'mssql_patients.CF_Specialized',
						'mssql_patients.LastMDVisit',
						'mssql_patients.NextMDVisit',
                        'intake.id',
                        'intake.notes',
                        'intake.aoc_patient_id',
                        'intake.CF_GoalSOCDate',
                        'intake.intake_pending_reason',
                        DB::raw($newref
                            ? 'intake.IntakeStatus AS IntakeStatus'
                            : 'mssql_patients.IntakeStatus AS IntakeStatus'),
                        DB::raw($newref
                            ? 'intake.BD_Rep AS BD_Rep'
                            : 'mssql_patients.BD_Rep AS BD_Rep'),
                        DB::raw($newref
                            ? 'intake.living_situation AS living_situation'
                            : 'mssql_patients.living_situation AS living_situation'),
                        DB::raw($newref
                            ? 'intake.ic_id AS ic_id'
                            : 'mssql_patients.ic_id AS ic_id'),
                        DB::raw($newref
                            ? 'intake.CF_ReferralType AS CF_ReferralType'
                            : 'mssql_patients.CF_ReferralType AS CF_ReferralType'),
                        DB::raw($newref
                            ? 'intake.auth_nurse_id AS auth_nurse_id'
                            : 'mssql_patients.auth_nurse_id AS auth_nurse_id'),
						'intake.LOBID',
                    )
                    ->leftJoin('mssql_patients',
                    'mssql_patients.id', 'intake.aoc_patient_id')
                    ->when($newref, function ($query) {
                        $query->leftJoin('lob', 'lob.LOBID', '=', 'intake.LOBID');
                    }, function ($query) {
                        $query->leftJoin('lob', 'lob.LOBID', '=', 'mssql_patients.LOBID');
                    })
                    ->where('intake.aoc_patient_id', $request->input('id'))
                    ->where('intake.id', $firstId)
                    ->orderby('intake.created_at', 'desc')
                    ->first();
                    if(!empty($intakeData)){
                        $intakeData['client_email'] = DB::table('client_emergency_contacts')
                        ->where('priority', 'Primary')
                        ->where('intake_id', $intakeData->id)
                        ->value('email');
                        $intakeData['insuranc'] = H::getHelper('H')->getClientInsuranceById($clientDetails);
                        $intakeData['EmergencyContact'] = $emergencyContacts;
                        $intakeDetails = [];
                        if(LD::checkFeatureFlag('change-soc-in-care-team')){
                            if (LD::checkFeatureFlag('move-first-billable-shift-fields-to-intake-table')) {
                                $soc = DB::table('mssql_schedulemaster')
                                ->select('users.profile_picture','mssql_providers.fullName')
                                ->leftjoin('intake','intake.intake_admission_visit','=','mssql_schedulemaster.CGTaskID')
                                ->leftJoin('mssql_providers', 'mssql_providers.ProviderID', 'mssql_schedulemaster.ProviderID')
                                ->leftJoin('users', 'users.email', '=', 'mssql_providers.AOC_Email')
                                ->where('intake.aoc_patient_id', $intakeData->aoc_patient_id)
                                ->orderby('mssql_schedulemaster.ScheduleDate','desc')
                                ->first();
                            } else {
                                $soc = DB::table('mssql_schedulemaster')
                                ->select('users.profile_picture','mssql_providers.fullName')
                                ->leftjoin('mssql_patients','mssql_patients.intake_admission_visit','=','mssql_schedulemaster.CGTaskID')
                                ->leftJoin('mssql_providers', 'mssql_providers.ProviderID', 'mssql_schedulemaster.ProviderID')
                                ->leftJoin('users', 'users.email', '=', 'mssql_providers.AOC_Email')
                                ->where('mssql_patients.id', $intakeData->aoc_patient_id)
                                ->orderby('mssql_schedulemaster.ScheduleDate','desc')
                                ->first();
                            }
                            if(!empty($soc)){
                                $intakeDetails[] =
                                [
                                    'designation' => 'Start of Care Clinician',
                                    'profilePic' => $soc->profile_picture,
                                    'name' => $soc->fullName,
                                ];
                            }
                        }else{
                            if(!empty($intakeData['LOBID']) && !empty($intakeData['ic_id'])){
                                $clinicain = H::getUserById($intakeData['ic_id']);
                                $intakeDetails[] =
                                [
                                    'designation' => 'Start of Care Clinician',
                                    'profilePic' => $clinicain->profile_picture,
                                    'name' => $clinicain->name,
                                ];
                            }
                        }

                        $careTeam =  array_merge($careTeam, $intakeDetails);
                        $i = $i + count($intakeDetails);
                        $intakeCustomFields = [];
                        $customFieldsList = CustomFields::where('is_deleted', 0)
                        ->where('entity', 'client')
                        ->where('category', 'Intake')
                        ->orderBy('category')
                        ->orderBy('sort')
                        ->get()
                        ->toArray();
                        foreach($customFieldsList as $key => $field) {
                            $category = $field['category'];
                            if (empty($category)) {
                                $category = 'Default';
                            }
                            $intakeCustomFields[$category][] = view('ManagingCustomFields.customfields', [
                                'field' => $field,
                                'details' => $clientDetails,
                                'id' => $clientDetails->id
                            ])->render();
                        }
                        $intakeData['customefileds'] = $intakeCustomFields;
                    }
                }
                $isSOCExists = array_search('Start of Care Clinician', array_column($careTeam, 'designation'));
                if ($isSOCExists === false && !empty($clientDetails->SOCClinicianID)) {
                    $user = DB::table('mssql_providers')
                                ->select('users.profile_picture', 'users.name')
                                ->join('users', 'users.email', '=', 'mssql_providers.AOC_Email')
                                ->where('mssql_providers.ProviderID', $clientDetails->SOCClinicianID)
                                ->first();
                    if(!empty($user)){
                        $careTeam[$i]['designation'] = 'Start of Care Clinician';
                        $careTeam[$i]['profilePic'] = $user->profile_picture;
                        $careTeam[$i]['name'] = $user->name;
                        $i++;
                    }
                }
                $customFields = [];
                if(LD::checkFeatureFlag('update-custom-fields-on-client-profile')) {
                    $customFieldsList = CustomFields::where('is_deleted', 0)
                                        ->where('entity', 'client')
                                        ->orderBy('category')
                                        ->orderBy('sort')
                                        ->get()
                                        ->toArray();
                    foreach($customFieldsList as $key => $field) {
                        $category = $field['category'];
                        if (empty($category)) {
                            $category = 'Default';
                        }
                        $customFields[$category][] = view('ManagingCustomFields.customfields', [
                            'field' => $field,
                            'details' => $clientDetails,
                            'id' => $clientDetails->id
                        ])->render();
                    }
                }
                $dischargedRecords = [];
                $clientDetailTemplateName = 'client.details';
                if(LD::checkFeatureFlag('client-new-changes')) {
                    $dischargedRecords = MssqlPatients::select("mssql_patients.id","mssql_patients.AdmitNo")
                    ->where("mssql_patients.PatientAccount",$clientDetails->PatientAccount)
                    ->where("mssql_patients.is_deleted", '=', 0)
                    ->orderByRaw('mssql_patients.IsLatestAdmit ASC, mssql_patients.created_at ASC')->get();
                    if(!empty($request->input('refSource')) && $request->input('refSource') == 'discharge') {
                        $clientDetailTemplateName = 'client.detailsInner';
                    }
                }

                if (!$canView) {
                    $clientDetailTemplateName = 'client.no-access';
                }

                $twilioErrorData = [];

                if (
                    LD::checkFeatureFlag('display-twilio-error-on-profile-page') &&
                    env('APP_ENV') === 'production'
                ) {
                    $candidateReceiversNumber = H::getHelper('MassMessageWidget')
                    ->getFormatPhoneForMessage($clientDetails->Patient_MobilPhone);
                    $twilioErrorData = DB::table('twilio_error_logs')
                        ->where('phone_number',$candidateReceiversNumber)
                        ->first();
                }

                $coverageUpcomingAppoinments = '';
                $coveragePastAppoinments = '';
                if(LD::checkFeatureFlag('add-section-to-upcoming-and-previous-appointments-tab-in-client-profile')){
                    $coverageUpcomingAppoinments = DB::table('coverage_tracker')
                    ->select('coverage_tracker.*', 'mssql_providers.fullName')
                    ->leftJoin('mssql_providers', 'mssql_providers.id', '=', 'coverage_tracker.aoc_provider_id')
                    ->where('coverage_tracker.aoc_patient_id', '=', $clientDetails->id)
                    ->whereDate('coverage_tracker.shift_date','>=',date('Y-m-d'))->orderBy('shift_date','asc')->get();
                    $coveragePastAppoinments = DB::table('coverage_tracker')
                    ->select('coverage_tracker.*', 'mssql_providers.fullName')
                    ->leftJoin('mssql_providers', 'mssql_providers.id', '=', 'coverage_tracker.aoc_provider_id')
                    ->where('coverage_tracker.aoc_patient_id', '=', $clientDetails->id)
                    ->whereDate('coverage_tracker.shift_date','<',date('Y-m-d'))->orderBy('shift_date','asc')->get();
                }
                return view($clientDetailTemplateName, [
                    'clientDetails'=>$clientDetails,
                    'upcomingAppointments'=>$upcomingAppointments,
                    'careTeam'=>$careTeam,
                    'needToHireChecklistDetails'=>$needToHireChecklistDetails,
                    'nthScheduleTable'=>$nthScheduleTable,
                    'totalShiftHours'=>$totalShiftHours,
                    'needToHireAuditList'=>$needToHireAuditList,
                    'pastAppointments'=>$pastAppointments,
                    'halos'=>$halos,
                    'mobileExistingUser'=>$mobileExistingUser,
                    'ReferringProviders'=>$ReferringProviders,
                    'socialMediaAdDetail'=>$socialMediaAdDetail,
                    'feature_photos' => $feature_photos,
                    'customFields' => $customFields,
                    'dischargedRecords' => $dischargedRecords,
                    'intakeData' => $intakeData,
                    'defaultTab' => $request->input('defaultTab'),
                    'twilioErrorData' => $twilioErrorData,
                    'newrefarray' => $newrefarray,
                    'coverageUpcomingAppoinments' => $coverageUpcomingAppoinments,
                    'coveragePastAppoinments' => $coveragePastAppoinments,
                ]);
            }
        } catch (\Exception $e) {
            H::ExcelLog($e->getMessage(),'clientcontroller.log');
            return false;
        }
    }
    /**
     * The function for client listing
     *
     * @author Nayan
     */
    public function clientListing(Request $request)
    {
        $columns = [
            ['name' => 'patientFullName', 'label' => 'Client'],
            ['name' => 'PatientAccount', 'label' => 'Client ID'],
            ['name' => 'LOBName', 'label' => 'LOB'],
            ['name' => 'PatientStatus', 'label' => 'Status'],
            ['name' => 'FacilityName', 'label' => 'Branch'],
            ['name' => 'IntakeStatus', 'label' => 'Intake Status'],
            ['name' => 'Patient_Phone', 'label' => 'Phone'],
        ];
        if ($request->ajax()) {
            $filters = H::getFilterInputArr($request->all());

            $search = $request->input('search.value');
            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')]['name'];
            $dir = $request->input('order.0.dir');

            $query = DB::table('mssql_patients')
                ->leftJoin('mssql_patientstatus', 'mssql_patientstatus.id', '=',
                    'mssql_patients.Patient_CurrentStatusID')
                ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=',
                    'mssql_patients.aoc_facility_id')
                ->leftJoin('lob', 'lob.LOBID', '=',
                    'mssql_patients.LOBID')
                ->select('mssql_patients.id',
                    'mssql_patients.PatientName', 'mssql_facilities.FacilityName',
                    'mssql_patients.PatientAccount', 'mssql_patients.Patient_State',
                    'mssql_patients.Patient_Phone', 'mssql_patientstatus.PatientStatus',
                    'mssql_patients.IntakeStatus', 'lob.LOBName',
                    DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',
            mssql_patients.Patient_LastName) as patientFullName"),
                    'mssql_patients.IsLatestAdmit');

            if(LD::checkFeatureFlag('client-new-changes')){
                $query->where('mssql_patients.IsLatestAdmit', 1);
            }

            $query = $query->where('mssql_patients.is_deleted', '=', 0);

            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $query->where(function($query) use ($search) {
                    $query->where('mssql_patients.PatientName', 'LIKE', "%{$search}%")->orWhere('mssql_patients.PatientAccount', 'LIKE', "%{$search}%")->orWhere('mssql_facilities.FacilityName', 'LIKE', "%{$search}%")->orWhere('lob.LOBName', 'LIKE', "%{$search}%")->orWhere('mssql_patients.Search_Name', 'LIKE', "%{$search}%");
                });
                $totalFiltered = $query->count();
            }
            $filters = [];
            if(isset($request['filters'])) {
                foreach($request['filters'] as $fl) {
                    if(!empty($fl['value'])) {
                        $filters[$fl['name']][] = $fl['value'];
                    }
                }
                foreach($filters as $fkey => $fval) {
                    if($fkey=='state') {
                        $query->where(function($query) use ($fval) {
                            foreach($fval as $val) {
                                $query->orWhere("mssql_patients.Patient_State",'=',$val);
                            }
                        });
                    }elseif($fkey=='aoc_facility_id') {
                        $query->where(function($query) use ($fval) {
                            foreach($fval as $val) {
                                $query->orWhere("mssql_patients.aoc_facility_id",'=',$val);
                            }
                        });
                    }elseif($fkey=='Patient_CurrentStatusID') {
                        $query->where(function($query) use ($fval) {
                            foreach($fval as $val) {
                                $query->orWhere("mssql_patients.Patient_CurrentStatusID",'=',$val);
                            }
                        });
                    }elseif($fkey=='LOBID') {
                        $query->where(function($query) use ($fval) {
                            foreach($fval as $val) {
                                $query->orWhere("mssql_patients.LOBID",'=',$val);
                            }
                        });
                    }elseif($fkey=='is_intake') {
                        $query->where('mssql_patients.is_intake', $fval[0]==='true'?1:0);
						if($fval[0]=='true') {
							$query->whereNotIn('mssql_patients.Patient_CurrentStatusID', [2, 3, 5]);
						}
                    }elseif(LD::checkFeatureFlag('payer-filters-on-client-listing')){
                        if($fkey=='client-payer'){
                            $query->where(function($query) use ($fval) {
                                foreach($fval as $val) {
                                    $query->orWhere("mssql_patients.Payer",'=',$val);
                                }
                            });
                        }elseif($fkey=='payer_group'){
                            $query->where(function($query) use ($fval) {
                                foreach($fval as $val) {
                                    $query->orWhere("mssql_patients.payer_group",'=',$val);
                                }
                            });
                        }
                    }

                    if (LD::checkFeatureFlag('add-me-mode-client-nth-list-page')) {
                        if ($fkey == 'default') {
                            $query->leftJoin('NeedToHire_Accounts',function($join){
                                $join->on("NeedToHire_Accounts.aoc_patient_id","=","mssql_patients.id")
                                ->where('NeedToHire_Accounts.isactive', '=', 1);
                            });
                            $query->where(function($query) {
                                $query->orWhere("mssql_patients.ccc_id", '=', Auth::user()->id);
                                $query->orWhere("mssql_patients.ccm_id", '=', Auth::user()->id);
                                $query->orWhere("NeedToHire_Accounts.user_id", '=', Auth::user()->id);
                            });
                        }
                    }
                }
                $totalFiltered = $query->count();
            }
            H::ExcelLog('clientListing: ' . $query->toSql(), 'clientListing.log');
            $data = $query->offset($start)->limit($limit)->orderBy($order, $dir)->get();

            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($totalData),
                'recordsFiltered' => intval($totalFiltered),
                'data'            => $data,
            ]);
        }
        return view('client.grid', ['columns' => $columns]);
    }

    public function exportClientData(Request $request){


        $columns = [
            ['name' => 'patientFullName', 'label' => 'Client'],
            ['name' => 'PatientAccount', 'label' => 'Client ID'],
            ['name' => 'LOBName', 'label' => 'LOB'],
            ['name' => 'PatientStatus', 'label' => 'Status'],
            ['name' => 'FacilityName', 'label' => 'Branch'],
            ['name' => 'IntakeStatus', 'label' => 'Intake Status'],
            ['name' => 'Patient_Phone', 'label' => 'Phone']
        ];
        $dataTableFields = [
            'patientFullName' => 'Client',
            'PatientAccount' => 'Client ID',
            'LOBName' => 'LOB',
            'PatientStatus' => 'Status',
            'State' => 'State',
            'FacilityName' => 'Branch',
            'payer' => 'Payer',
            'payer_group' => 'Special Payer Group',
            'AuthorizedWeeklyHours' => 'Authorized PDN Hours',
            'CF_RespWeeklyHrs' => 'Authorized Respite Hours',
            'CNAAuthorizedWeeklyHours' => 'Authorized CNA Hours',
            'updated_at' => 'On NTH?',
            'created_at' => 'Date Added to NTH',
            'Patient_DOB' => 'DOB',
            'MedicaidNumber' => 'Medicaid Number'
        ];
        if(LD::checkFeatureFlag('add-hh-tag-client-profile')){
            $dataTableFields['hh_chartid'] = 'Health Home Services';
        }
        if(LD::checkFeatureFlag('client-payer-source-eligibility-tab')){
            $dataTableFields['medicaid_type'] = 'Medicaid Type';
            $dataTableFields['eligibility_end_date'] = 'Eligibility End Date';
            $dataTableFields['time_renew_date'] = 'Time to Renew Date';
        }


        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=clientlist.csv",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

            $filters = H::getFilterInputArr($request->all());

            $search = $request->search['value'];

            $query = DB::table('mssql_patients')
            ->leftJoin('mssql_patientstatus', 'mssql_patientstatus.id', '=',
                'mssql_patients.Patient_CurrentStatusID')
            ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=',
                'mssql_patients.aoc_facility_id')
            ->leftJoin('lob', 'lob.LOBID', '=', 'mssql_patients.LOBID')
            ->leftJoin('NeedToHire_Accounts',function($join){
                $join->on("NeedToHire_Accounts.aoc_patient_id","=",
                    "mssql_patients.id")
                    ->where("NeedToHire_Accounts.IsActive","=",'1');
            })
            ->select('mssql_patients.id',
                'mssql_patients.PatientName', 'mssql_facilities.FacilityName',
                'mssql_patients.PatientAccount', 'mssql_patients.Patient_State',
                'mssql_patients.Patient_Phone', 'mssql_patientstatus.PatientStatus',
                'mssql_patients.IntakeStatus', 'lob.LOBName','mssql_patients.AuthorizedWeeklyHours',
                'mssql_patients.CNAAuthorizedWeeklyHours','mssql_patients.CF_RespWeeklyHrs',
                'NeedToHire_Accounts.created_at','NeedToHire_Accounts.updated_at',
                'mssql_patients.payer','mssql_patients.payer_group',
                'mssql_facilities.State','mssql_patients.hh_chartid',
                DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',
            mssql_patients.Patient_LastName) as patientFullName"),
                'mssql_patients.Patient_DOB', 'mssql_patients.MedicaidNumber',
                'mssql_patients.IsLatestAdmit');
            if(LD::checkFeatureFlag('client-payer-source-eligibility-tab')){
                $query->addSelect('mssql_patients.medicaid_type', 'mssql_patients.eligibility_end_date', 'mssql_patients.time_renew_date');
            }
            if(LD::checkFeatureFlag('client-new-changes')){
                $query->where('mssql_patients.IsLatestAdmit', 1);
            }
            $fieldName = [];
            if(LD::checkFeatureFlag('export-custom-field-clients')){

                if (!empty($request->cf_ids) && is_array($request->cf_ids)) {

                    $customFields = $this->customFieldsReadRepository->getCustomFieldsByIds($request->cf_ids);

                    foreach ($customFields as $cf) {
                        $dataTableFields[$cf->db_field_name] = $cf->field_name;
                        $fieldName[] = $cf->db_field_name;
                    }
                }

                if($fieldName) {
                    $query->addSelect($fieldName);
                }

            }

            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $q = $query->where('mssql_patients.PatientName', 'LIKE', "%{$search}%")->orWhere('mssql_patients.PatientAccount', 'LIKE', "%{$search}%")->orWhere('mssql_facilities.FacilityName', 'LIKE', "%{$search}%")->orWhere('lob.LOBName', 'LIKE', "%{$search}%")->orWhere('mssql_patients.Search_Name', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }
            $filters = [];
            if(isset($request['filters'])) {
                foreach($request['filters'] as $fl) {
                    if(!empty($fl['value'])) {
                        $filters[$fl['name']][] = $fl['value'];
                    }
                }
                foreach($filters as $fkey => $fval) {
                    if($fkey=='state') {
                        $query->where(function($query) use ($fval) {
                            foreach($fval as $val) {
                                $query->orWhere("mssql_patients.Patient_State",'=',$val);
                            }
                        });
                    }elseif($fkey=='aoc_facility_id') {
                        $query->where(function($query) use ($fval) {
                            foreach($fval as $val) {
                                $query->orWhere("mssql_patients.aoc_facility_id",'=',$val);
                            }
                        });
                    }elseif($fkey=='Patient_CurrentStatusID') {
                        $query->where(function($query) use ($fval) {
                            foreach($fval as $val) {
                                $query->orWhere("mssql_patients.Patient_CurrentStatusID",'=',$val);
                            }
                        });
                    }elseif($fkey=='LOBID') {
                        $query->where(function($query) use ($fval) {
                            foreach($fval as $val) {
                                $query->orWhere("mssql_patients.LOBID",'=',$val);
                            }
                        });
                    }elseif($fkey=='is_intake') {
                        $query->where('mssql_patients.is_intake', $fval[0]==='true'?1:0);
                    }elseif(LD::checkFeatureFlag('payer-filters-on-client-listing')){
                        if($fkey=='client-payer'){
                            $query->where(function($query) use ($fval) {
                                foreach($fval as $val) {
                                    $query->orWhere("mssql_patients.Payer",'=',$val);
                                }
                            });
                        }elseif($fkey=='payer_group'){
                            $query->where(function($query) use ($fval) {
                                foreach($fval as $val) {
                                    $query->orWhere("mssql_patients.payer_group",'=',$val);
                                }
                            });
                        }
                    }
                    if (LD::checkFeatureFlag('add-me-mode-client-nth-list-page')) {
                        if ($fkey == 'default') {
                            $query->where(function($query) {
                                $query->orWhere("mssql_patients.ccc_id", '=', Auth::user()->id);
                                $query->orWhere("mssql_patients.ccm_id", '=', Auth::user()->id);
                                $query->orWhere("NeedToHire_Accounts.user_id", '=', Auth::user()->id);
                            });
                        }
                    }
                }
                $totalFiltered = $query->count();
            }

            H::ExcelLog('clientListing: ' . $query->toSql(), 'clientListing.log');
            if (isset($request['order'][0]) && $request['order'][0] != '') {
                $data = $query->orderby($columns[intval($request['order'][0]['column'])], $request['order'][0]['dir']);
            }
            $data = $query->get();
            $columns = [];

            foreach ($dataTableFields as $k => $v) {
                $columns[] = $v;
            }
            if(LD::checkFeatureFlag('export-custom-field-clients')){
            $callback = function () use ($data, $columns, $dataTableFields,$fieldName) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns);

                foreach ($data as $tableData) {
                    $cntr = 0;
                    $csvData = [];
                    foreach ($dataTableFields as $fields => $val) {
                        if($fieldName) {
                            if(in_array($fields,$fieldName)) {
                                $csvData[] = $tableData->{$fields};
                            }
                        }

                        if($fields == 'patientFullName'){
                            $csvData[] = $tableData->{$fields};
                        }
                        if($fields == 'PatientAccount'){
                            $csvData[] = $tableData->{$fields};
                        }
                        if($fields == 'LOBName'){
                            $csvData[] = $tableData->{$fields};
                        }
                        if($fields == 'PatientStatus'){
                            $csvData[] = $tableData->{$fields};
                        }
                        if($fields == 'State'){
                            $csvData[] = $tableData->{$fields};
                        }
                        if($fields == 'FacilityName'){
                            $csvData[] = $tableData->{$fields};
                        }
                        if($fields == 'payer'){
                            $csvData[] = $tableData->{$fields};
                        }
                        if($fields == 'payer_group'){
                            $csvData[] = $tableData->{$fields};
                        }
                        if($fields == 'AuthorizedWeeklyHours'){
                            $csvData[] = $tableData->{$fields};
                        }
                        if($fields == 'CF_RespWeeklyHrs'){
                            $csvData[] = $tableData->{$fields};
                        }
                        if($fields == 'CNAAuthorizedWeeklyHours'){
                            $csvData[] = $tableData->{$fields};
                        }
                        if($fields == 'updated_at'){
                            $csvData[] = ($tableData->{$fields}) ? 'Yes' : 'No';
                        }
                        if($fields == 'medicaid_type'){
                            $csvData[] = $tableData->{$fields};
                        }
                        if($fields == 'created_at' || $fields == 'eligibility_end_date' || $fields == 'time_renew_date'){
                            $csvData[] = H::getFormatedDate($tableData->{$fields});
                        }
                        if(LD::checkFeatureFlag('add-hh-tag-client-profile')){
                            if($fields == 'hh_chartid'){
                                $csvData[] = ($tableData->{$fields}) ? 'Yes' : 'No';
                            }
                        }
                        if($fields == 'Patient_DOB'){
                            $csvData[] = H::getFormatedDate($tableData->{$fields});
                        }
                        if($fields == 'MedicaidNumber'){
                            $csvData[] = $tableData->{$fields};
                        }
                        $cntr++;
                    }

                    fputcsv($file, $csvData);
                }
                fclose($file);
            };
            } else {
                $callback = function () use ($data, $columns, $dataTableFields) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);

                    foreach ($data as $tableData) {
                        $cntr = 0;
                        $csvData = [];
                        foreach ($dataTableFields as $fields => $val) {


                            if($fields == 'patientFullName'){
                                $csvData[] = $tableData->{$fields};
                            }
                            if($fields == 'PatientAccount'){
                                $csvData[] = $tableData->{$fields};
                            }
                            if($fields == 'LOBName'){
                                $csvData[] = $tableData->{$fields};
                            }
                            if($fields == 'PatientStatus'){
                                $csvData[] = $tableData->{$fields};
                            }
                            if($fields == 'State'){
                                $csvData[] = $tableData->{$fields};
                            }
                            if($fields == 'FacilityName'){
                                $csvData[] = $tableData->{$fields};
                            }
                            if($fields == 'payer'){
                                $csvData[] = $tableData->{$fields};
                            }
                            if($fields == 'payer_group'){
                                $csvData[] = $tableData->{$fields};
                            }
                            if($fields == 'AuthorizedWeeklyHours'){
                                $csvData[] = $tableData->{$fields};
                            }
                            if($fields == 'CF_RespWeeklyHrs'){
                                $csvData[] = $tableData->{$fields};
                            }
                            if($fields == 'CNAAuthorizedWeeklyHours'){
                                $csvData[] = $tableData->{$fields};
                            }
                            if($fields == 'updated_at'){
                                $csvData[] = ($tableData->{$fields}) ? 'Yes' : 'No';
                            }
                            if($fields == 'medicaid_type'){
                                $csvData[] = $tableData->{$fields};
                            }
                            if($fields == 'created_at' || $fields == 'eligibility_end_date' || $fields == 'time_renew_date'){
                                $csvData[] = H::getFormatedDate($tableData->{$fields});
                            }
                            if(LD::checkFeatureFlag('add-hh-tag-client-profile')){
                                if($fields == 'hh_chartid'){
                                    $csvData[] = ($tableData->{$fields}) ? 'Yes' : 'No';
                                }
                            }
                            if($fields == 'Patient_DOB'){
                                $csvData[] = H::getFormatedDate($tableData->{$fields});
                            }
                            if($fields == 'MedicaidNumber'){
                                $csvData[] = $tableData->{$fields};
                            }
                            $cntr++;
                        }

                        fputcsv($file, $csvData);
                    }
                    fclose($file);
                };

            }

            return response()->stream($callback, 200, $headers);

    }

    public function saveClinicalWellChild(Request $request) {
        $last_visit = date('Y-m-d', strtotime( $request->last_visit));
        try {
            DB::table('patient_well_child_visits')->insert(['primary_care_physician_id' => $request->primary_care_physician_id,'last_visit' => $last_visit,'aoc_patient_id' => $request->aoc_patient_id,'created_by'=>Auth::user()->id]);
            return response()->json(['success'=>1, 'message'=>'Well child details has been added successfully']);
        } catch (\Throwable $th) {
            return response()->json(['success'=>0, 'message'=>'Something went wrong.'.$th->getMessage()]);
        }
    }
    public function getClinicalWellChild(Request $request) {
        $clinicalWellChildList = DB::table('patient_well_child_visits')
        ->select("patient_well_child_visits.id",
        "patient_well_child_visits.aoc_patient_id",
        "patient_well_child_visits.primary_care_physician_id",
        "patient_well_child_visits.last_visit",
        DB::raw("CONCAT(ReferringProviders.FirstName,' ',ReferringProviders.LastName) as fullName"))
        ->leftjoin('ReferringProviders',
        'ReferringProviders.BillingReferringProviderID','=',
        'patient_well_child_visits.primary_care_physician_id')
        ->where('patient_well_child_visits.aoc_patient_id',$request->aoc_patient_id)
        ->orderBy('patient_well_child_visits.created_at','DESC')->get();
        return view('client.tabs._clinicalWellChildList', ['clinicalWellChildList'=>$clinicalWellChildList]);
    }

    public function exportWellChildVisitData(Request $request){
        $dataTableFields = [
            'patientFullName' => 'Client Name',
            'PatientAccount' => 'Client ID',
            'LOBName' => 'LOB',
            'PatientStatus' => 'Status',
            'State' => 'State',
            'FacilityName' => 'Branch',
            'payer' => 'Payer',
            'payer_group' => 'Special Payer Group',
            'providerFullName' => 'Primary Care Physician',
            'last_visit' => 'Well Child Visit Date'
        ];

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=wellchildvisitdata.csv",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];
        $query = DB::table('patient_well_child_visits')
        ->leftJoin('mssql_patients', 'mssql_patients.id', '=', 'patient_well_child_visits.aoc_patient_id')
        ->leftJoin('mssql_patientstatus', 'mssql_patientstatus.id', '=', 'mssql_patients.Patient_CurrentStatusID')
        ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
        ->leftJoin('lob', 'lob.LOBID', '=', 'mssql_patients.LOBID')
        ->leftjoin('ReferringProviders','ReferringProviders.BillingReferringProviderID','=','patient_well_child_visits.primary_care_physician_id')
        ->select('mssql_patients.id', 'mssql_patients.PatientName', 'mssql_facilities.FacilityName', 'mssql_patients.PatientAccount', 'mssql_patients.Patient_State', 'mssql_patients.Patient_Phone', 'mssql_patientstatus.PatientStatus', 'mssql_patients.IntakeStatus', 'lob.LOBName','mssql_patients.AuthorizedWeeklyHours','mssql_patients.CNAAuthorizedWeeklyHours','mssql_patients.CF_RespWeeklyHrs','mssql_patients.payer','mssql_patients.payer_group','mssql_facilities.State','patient_well_child_visits.primary_care_physician_id','patient_well_child_visits.last_visit',
        DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) as patientFullName"),DB::raw("CONCAT(ReferringProviders.FirstName,' ',ReferringProviders.LastName) as providerFullName"));

        H::ExcelLog('clientListing: ' . $query->toSql(), 'clientListing.log');
        $data = $query->get();
        $columns = [];

        foreach ($dataTableFields as $k => $v) {
            $columns[] = $v;
        }
        $callback = function () use ($data, $columns, $dataTableFields) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($data as $tableData) {
                $cntr = 0;
                $csvData = [];
                foreach ($dataTableFields as $fields => $val) {
                    if($fields == 'last_visit'){
                        $csvData[] =  H::getFormatedDate($tableData->{$fields});
                    } else {
                        $csvData[] = $tableData->{$fields};
                    }
                    $cntr++;
                }
                fputcsv($file, $csvData);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
}

    public function mergingClients(Request $request){
        H::ExcelLog('START Merging ONE Duplicate Clients','ClientMerge.log');
        $patientAccount = $request->input('patientAccount');
        H::ExcelLog('Start process to find duplicate client for :'.$patientAccount,'ClientMerge.log');
        if(isset($patientAccount)){
            $this->mergingClientProcess($patientAccount);
            $request->session()->flash('success', 'Client has been merged successfully!');
            H::ExcelLog('END process to find duplicate client for :'.$patientAccount,'ClientMerge.log');
            return response()->json(['status'=>true]);
        }else{
            $request->session()->flash('success', 'Missing Patient Account');
            return response()->json(['status'=>false]);
        }
    }
    public function mergingAllDuplicateClients(Request $request){
        H::ExcelLog('START Merging All Duplicate Clients','ClientMerge.log');
        $duplicateClientList = DB::table('mssql_patients')->select('PatientAccount', DB::raw('count(id) as cnt'))
        ->groupBy('PatientAccount')->havingRaw("cnt > 1")->get();
        H::ExcelLog('All Duplicate Clients','ClientMerge.log');
        H::ExcelLog(print_r($duplicateClientList, true),'ClientMerge.log');
        if(!empty($duplicateClientList)) {
            foreach($duplicateClientList as $duplicateClientData) {
                $patientAccount = $duplicateClientData->PatientAccount;
                H::ExcelLog('#########Finding duplicate client start for :'.$patientAccount,'ClientMerge.log');
                if(isset($patientAccount)){
                    H::ExcelLog('Passing patientAccount to process the duplicate client for :'.$patientAccount,'ClientMerge.log');
                    $this->mergingClientProcess($patientAccount);
                }else{
                    H::ExcelLog('Duplicate Patient not found OR already Merged:'.$patientAccount,'ClientMerge.log');
                }
            }
            $request->session()->flash('success', 'Client(s) has been merged successfully!');
            H::ExcelLog('END Merging All Duplicate Clients','ClientMerge.log');
            return redirect('/client/merge-duplicate')->with('success', 'Client(s) has been merged successfully!');
        }else{
            H::ExcelLog('No Duplicate Patient Found','ClientMerge.log');
            $request->session()->flash('success', 'No Duplicate Patients Found');
            return redirect('/client/merge-duplicate')->with('success', 'No Duplicate Patients Found');
        }
    }
    public function mergingClientProcess($patientAccount){
        try{
            $patients = MssqlPatients::select("mssql_patients.*")->where("mssql_patients.PatientAccount",$patientAccount)->orderBy('mssql_patients.created_at')->get();
            if(count($patients) > 1){
                $goodClientId = $patients[0]->id;
                $badClientId = $patients[1]->id;


                H::ExcelLog('Merging duplicate mssql_patient records for '.$patientAccount.'.','ClientMerge.log');
                //Step:2 Merging Client

                //logging bad need to hire account
                $badNeedToHireAccount = DB::table('NeedToHire_Accounts')->select(DB::raw('GROUP_CONCAT(id) as needToHireAccountIds'))->where('aoc_patient_id',$badClientId)->get();
                if(isset($badNeedToHireAccount[0]) && isset($badNeedToHireAccount[0]->needToHireAccountIds)){
                    H::ExcelLog('Records from NeedToHire_Accounts  moved from '.$badClientId.' to '.$goodClientId.' during Merge Client process','ClientMerge.log');

                    DB::table('NeedToHire_Accounts')->where('aoc_patient_id',$badClientId)->update(['aoc_patient_id'=>$goodClientId]);

                    H::ExcelLog('Records from NeedToHire_Accounts_Audit  moved from '.$badClientId.' to '.$goodClientId.' during Merge Client process','ClientMerge.log');

                    DB::table('NeedToHire_Accounts_Audit')->where('aoc_patient_id',$badClientId)->update(['aoc_patient_id'=>$goodClientId]);
                }else{
                    H::ExcelLog('No records found in NeedToHire_Accounts and NeedToHire_Accounts_Audit to move from '.$badClientId.' to '.$goodClientId.' during Merge Client process','ClientMerge.log');
                }

                //logging bad ids of notes
                $badIds = DB::table('notes')->select(DB::raw('GROUP_CONCAT(id) as id'))->where('patient_id',$badClientId)->get();
                if(isset($badIds[0]) && isset($badIds[0]->id)){
                    H::ExcelLog('Records from notes (patient_id) moved from '.$badClientId.' to '.$goodClientId.' during Merge Client process','ClientMerge.log');
                    DB::table('notes')->where('patient_id',$badClientId)->update(['patient_id'=>$goodClientId]);
                }else{
                    H::ExcelLog('No records found in notes (patient_id) to move from '.$badClientId.' to '.$goodClientId.' during Merge Client process','ClientMerge.log');
                }

                //logging bad ids of notes where entity is client
                $badIds = DB::table('notes')->select(DB::raw('GROUP_CONCAT(id) as id'))->where('entity_id',$badClientId)->where('entity','client')->get();
                if(isset($badIds[0]) && isset($badIds[0]->id)){
                    H::ExcelLog('Records from notes (client entity) moved from '.$badClientId.' to '.$goodClientId.' during Merge Client process','ClientMerge.log');
                    DB::table('notes')->where('entity_id',$badClientId)->where('entity','client')->update(['entity_id'=>$goodClientId]);
                }else{
                    H::ExcelLog('No records found in notes (client entity) to move from '.$badClientId.' to '.$goodClientId.' during Merge Client process','ClientMerge.log');
                }

                //logging bad ids of Notification where type is client
                $badIds = DB::table('notifications')->select(DB::raw('GROUP_CONCAT(id) as id'))->where('related_item',$badClientId)->where('type','client')->get();
                if(isset($badIds[0]) && isset($badIds[0]->id)){
                    H::ExcelLog('Records from notifications  moved from '.$badClientId.' to '.$goodClientId.' during Merge Client process','ClientMerge.log');
                    DB::table('notifications')->where('related_item',$badClientId)->where('type','client')->update(['related_item'=>$goodClientId]);
                }else{
                    H::ExcelLog('No records found in notifications to move from '.$badClientId.' to '.$goodClientId.' during Merge Client process','ClientMerge.log');
                }

                // logging bad ids of mssql_recruitertracker where type is client
                $badIds = DB::table('mssql_recruitertracker')->select(DB::raw('GROUP_CONCAT(id) as id'))->where('aoc_patient_id',$badClientId)->get();
                if(isset($badIds[0]) && isset($badIds[0]->id)){
                    H::ExcelLog('Records from mssql_recruitertracker  moved from '.$badClientId.' to '.$goodClientId.' during Merge Client process','ClientMerge.log');
                    DB::table('mssql_recruitertracker')->where('aoc_patient_id',$badClientId)->update(['aoc_patient_id'=>$goodClientId]);
                }else{
                    H::ExcelLog('No records found in mssql_recruitertracker to move from '.$badClientId.' to '.$goodClientId.' during Merge Client process','ClientMerge.log');
                }

                // logging bad ids of recruiting_schedule where type is client
                $badIds = DB::table('recruiting_schedule')->select(DB::raw('GROUP_CONCAT(id) as id'))->where('aoc_patient_id',$badClientId)->get();
                if(isset($badIds[0]) && isset($badIds[0]->id)){
                    H::ExcelLog('Records from recruiting_schedule  moved from '.$badClientId.' to '.$goodClientId.' during Merge Client process','ClientMerge.log');
                    DB::table('recruiting_schedule')->where('aoc_patient_id',$badClientId)->update(['aoc_patient_id'=>$goodClientId]);
                }else{
                    H::ExcelLog('No records found in recruiting_schedule to move from '.$badClientId.' to '.$goodClientId.' during Merge Client process','ClientMerge.log');
                }
                //Step 3: adding activity for good client
                $noteData = [];
                $noteData['entity'] = 'client';
                $noteData['entity_id'] = $goodClientId;
                $noteData['notes'] ='Activity from a duplicate account ('.$badClientId.') was merged into this account.';
                H::getHelper("ActivityNotes")->setActivityNotes($noteData);

                //Inactivating the bad client
                DB::table('mssql_patients')->where('id',$badClientId)->delete();
            }else{
                H::ExcelLog('Duplicate Patient not found OR already Merged:'.$patientAccount,'ClientMerge.log');
            }
        } catch (\Exception $e) {
            H::ExcelLog('Exception:'.$e->getMessage(),'ClientMerge.log');
        }
    }
    public function mergeClient(Request $request) {
        return view('client.mergeClient');
    }
    public function searchingDuplicatedClient(Request $request){
        $patientAccount = $request->input('patientAccount');
        if(isset($patientAccount)){
            $patients = MssqlPatients::select("mssql_patients.*")->where("mssql_patients.PatientAccount",$patientAccount)->orderBy('mssql_patients.created_at')->get();
            return view('client.mergeClientView',['patients'=>$patients]);
        }
    }

    public function saveAcceptsCoverage(Request $request) {
        try {
            DB::table('NeedToHire_Schedules')->where('id', $request->schedule_id)->update(['AcceptsCoverage' => $request->accepts_coverage]);
            return response()->json(['success' => 1]);
        } catch (\Throwable $th) {
            return response()->json(['success' => 0]);
        }
    }
    public function saveEligibility(Request $request) {
        try {
            $data = [];
            $oldData = MssqlPatients::where('id', $request->aoc_patient_id)->first();
            if (
                in_array('Traditional Medicaid (fee for service)', $request->medicaid_type) ||
                in_array('STAR', $request->medicaid_type) ||
                in_array('STAR Kids', $request->medicaid_type) ||
                in_array('STAR+PLUS', $request->medicaid_type) ||
                in_array('STAR Health', $request->medicaid_type)
            ) {
                $data = [
                    'medicaid_type' => implode(",", $request->medicaid_type),
                    'eligibility_end_date' => !empty($request->eligibility_end_date) ? date('Y-m-d', strtotime($request->eligibility_end_date)) : '',
                    'time_renew_date' => !empty($request->time_renew_date) ? date('Y-m-d', strtotime($request->time_renew_date)) : ''
                ];
            } else {
                $data = [
                    'medicaid_type' => implode(",", $request->medicaid_type),
                    'eligibility_end_date' => '',
                    'time_renew_date' => ''
                ];
            }
            MssqlPatients::where('id', $request->aoc_patient_id)
            ->update($data);

            $eligibilityEndDateNoteMsg = '';
            if ((empty($oldData->eligibility_end_date) || $oldData->eligibility_end_date == '0000-00-00') && !empty($data['eligibility_end_date'])) {
                $eligibilityEndDateNoteMsg = Auth::user()->name . ' added Eligibility End Date to ' . H::getFormatedDate($data['eligibility_end_date']) . '.';
            } else if (
                !empty($oldData->eligibility_end_date) &&
                !empty($data['eligibility_end_date']) &&
                $oldData->eligibility_end_date != $data['eligibility_end_date']
            ) {
                $eligibilityEndDateNoteMsg = Auth::user()->name . ' edited Eligibility End Date from ' . H::getFormatedDate($oldData->eligibility_end_date) . ' to ' . H::getFormatedDate($data['eligibility_end_date']) . '.';
            } else if ((!empty($oldData->eligibility_end_date) && $oldData->eligibility_end_date != '0000-00-00') && empty($data['eligibility_end_date'])) {
                $eligibilityEndDateNoteMsg = Auth::user()->name . ' removed Eligibility End Date from ' . H::getFormatedDate($oldData->eligibility_end_date) . '.';
            }
            if (!empty($eligibilityEndDateNoteMsg)) {
                $noteData = [];
                $noteData['entity'] = 'client';
                $noteData['entity_id'] = $request->aoc_patient_id;
                $noteData['notes'] = $eligibilityEndDateNoteMsg;
                H::getHelper("ActivityNotes")->setActivityNotes($noteData);
            }

            $timeRenewDateNoteMsg = '';
            if ((empty($oldData->time_renew_date) || $oldData->time_renew_date == '0000-00-00') && !empty($data['time_renew_date'])) {
                $timeRenewDateNoteMsg = Auth::user()->name . ' added Time to Renew Date to ' . H::getFormatedDate($data['time_renew_date']) . '.';
            } else if (
                !empty($oldData->time_renew_date) &&
                !empty($data['time_renew_date']) &&
                $oldData->time_renew_date != $data['time_renew_date']
            ) {
                $timeRenewDateNoteMsg = Auth::user()->name . ' edited Time to Renew Date from ' . H::getFormatedDate($oldData->time_renew_date) . ' to ' . H::getFormatedDate($data['time_renew_date']) . '.';
            } else if ((!empty($oldData->time_renew_date) && $oldData->time_renew_date != '0000-00-00') && empty($data['time_renew_date'])) {
                $timeRenewDateNoteMsg = Auth::user()->name . ' removed Time to Renew Date from ' . H::getFormatedDate($oldData->time_renew_date) . '.';
            }
            if (!empty($timeRenewDateNoteMsg)) {
                $noteData = [];
                $noteData['entity'] = 'client';
                $noteData['entity_id'] = $request->aoc_patient_id;
                $noteData['notes'] = $timeRenewDateNoteMsg;
                H::getHelper("ActivityNotes")->setActivityNotes($noteData);
            }
            return response()->json(['success' => 1]);
        } catch (\Throwable $th) {
            return response()->json(['success' => 0]);
        }
    }

    public function deleteClinicalWellChild(Request $request) {
        try {
            $ids = explode(",",$request->ids);
            foreach($ids as $id){
                $clinicalWellChildData = DB::table('patient_well_child_visits')->select("patient_well_child_visits.last_visit","patient_well_child_visits.aoc_patient_id",DB::raw("CONCAT(ReferringProviders.FirstName,' ',ReferringProviders.LastName) as fullName"))->leftjoin('ReferringProviders','ReferringProviders.ReferringProviderID','=','patient_well_child_visits.primary_care_physician_id')->where('id',$id)->first();
                $activeNote =  Auth::user()->name.' deleted a Well Child Visit with '.$clinicalWellChildData->fullName.' on '.H::getFormatedDate($clinicalWellChildData->last_visit).'.';
                H::getHelper("ActivityNotes")->setActivityNotes(["entity"=>"client","entity_id"=>$clinicalWellChildData->aoc_patient_id,"type"=>"well_child","notes"=>$activeNote]);
                DB::table('patient_well_child_visits')->where('id',$id)->delete();
            }
            return response()->json(['success'=>1, 'message'=>'Well child details has been deleted successfully']);
        } catch (\Throwable $th) {
            return response()->json(['success'=>0, 'message'=>'Something went wrong.'.$th->getMessage()]);
        }
    }

    public function saveChangeCallFrequency(Request $request, MonthlyCallsRecalculatorService $callsRecalculatorService) {
        try {
            $data = $request->all();
            $id = $data['aoc_patient_id'];
            unset($data['aoc_patient_id']);
            unset($data['_token']);
            MssqlPatients::where('id',$id)->update($data);

            $callsRecalculatorService->recalculateForPatient($id, (int)$request->get('ccc_client_call_frequency'), Auth::id());

            return response()->json(['success'=>1, 'message'=>'<div class="alert alert-success">
                <button type="button" class="close" data-dismiss="alert">×</button>
                Frequency changed successfully.
                </div>
            ']);
        } catch (\Throwable $th) {
            return response()->json(['success'=>0, 'message'=>'<div class="alert alert-danger">
                <button type="button" class="close" data-dismiss="alert">×</button>
                    Something went wrong.
                </div>'
            ]);
        }
    }

    public function getIntakeData(Request $request){
        $patientId  = $request->aoc_patient_id;
        $intakeId = $request->intakeId;
        $newref = $this->constantHelper->checkNewRefOnLob($intakeId);
        $intakelob = DB::table('mssql_patients')
        ->leftJoin('intake', 'intake.aoc_patient_id', '=', 'mssql_patients.id')
        ->leftJoin('lob', function ($join) {
            $join->on('lob.LOBID', '=', DB::raw('COALESCE(intake.LOBID, mssql_patients.LOBID)'));
        })
        ->select(
            'intake.id',
            DB::raw('COALESCE(lob.LOBName, \'Unknown\') as LOBName')
        )
        ->where('mssql_patients.id', '=', $patientId)
        ->orderBy('intake.id')
        ->get();
        $newrefarray = [];
        foreach($intakelob as $data){
            $newrefarray[$data->id] = $data->LOBName;
        }
        if(!empty($patientId)){
            $intakeData = Intake::select(
                (
                    LD::checkFeatureFlag('move-first-billable-shift-fields-to-intake-table') ?
                    DB::raw("(CASE
                                -- Check if a valid ScheduleDate exists in mssql_schedulemaster
                                    WHEN EXISTS (
                                        SELECT 1
                                        FROM mssql_schedulemaster sm
                                        WHERE intake.intake_admission_visit = sm.CGTaskID
                                            AND sm.ScheduleDate IS NOT NULL
                                            AND sm.ScheduleDate != ''
                                            AND DATE(sm.ScheduleDate) != '0000-00-00'
                                    )
                                    THEN (
                                        SELECT sm.ScheduleDate
                                        FROM mssql_schedulemaster sm
                                        WHERE intake.intake_admission_visit = sm.CGTaskID
                                        LIMIT 1
                                    )
                                    -- Use admission_visit_date if ScheduleDate is invalid or not found
                                    WHEN intake.admission_visit_date IS NOT NULL
                                        AND intake.admission_visit_date != ''
                                        AND DATE(intake.admission_visit_date) != '0000-00-00'
                                    THEN intake.admission_visit_date
                                    -- Default to ActualIntakeDate
                                    ELSE mssql_patients.ActualIntakeDate
                                END
                            ) AS intake_created_date") :
                    DB::raw("(CASE
                                -- Check if a valid ScheduleDate exists in mssql_schedulemaster
                                    WHEN EXISTS (
                                        SELECT 1
                                        FROM mssql_schedulemaster sm
                                        WHERE mssql_patients.intake_admission_visit = sm.CGTaskID
                                            AND sm.ScheduleDate IS NOT NULL
                                            AND sm.ScheduleDate != ''
                                            AND DATE(sm.ScheduleDate) != '0000-00-00'
                                    )
                                    THEN (
                                        SELECT sm.ScheduleDate
                                        FROM mssql_schedulemaster sm
                                        WHERE mssql_patients.intake_admission_visit = sm.CGTaskID
                                        LIMIT 1
                                    )
                                    -- Use admission_visit_date if ScheduleDate is invalid or not found
                                    WHEN intake.admission_visit_date IS NOT NULL
                                        AND intake.admission_visit_date != ''
                                        AND DATE(intake.admission_visit_date) != '0000-00-00'
                                    THEN intake.admission_visit_date
                                    -- Default to ActualIntakeDate
                                    ELSE mssql_patients.ActualIntakeDate
                                END
                            ) AS intake_created_date")
                ),
                'lob.LOBName',
                'intake.AttendingPhysicianID AS ReferringPhysician',
                'intake.ReferralDate',
                'intake.ReferralSourceID',
                'intake.location',
                'intake.intake_nurse_id',
                'intake.kantime_url',
                'mssql_patients.PatientID',
                'mssql_patients.AttendingPhysicianID AS acuAttendingPhysicianID',
                'mssql_patients.PrimarycarePhysicianID',
                'mssql_patients.MedicaidNumber',
                'mssql_patients.transport_level_assistance',
                'mssql_patients.client_classification',
                'mssql_patients.marital_status',
                'mssql_patients.Ethnicity',
                'mssql_patients.Languages',
                'mssql_patients.PlaceOfService',
                'mssql_patients.living_situation',
                'mssql_patients.CF_Specialized',
                'mssql_patients.LastMDVisit',
				'mssql_patients.NextMDVisit',
                'intake.id',
                'intake.notes',
                'intake.aoc_patient_id',
                'intake.CF_GoalSOCDate',
                'intake.intake_pending_reason',
                DB::raw($newref
                    ? 'intake.IntakeStatus AS IntakeStatus'
                    : 'mssql_patients.IntakeStatus AS IntakeStatus'),
                DB::raw($newref
                    ? 'intake.BD_Rep AS BD_Rep'
                    : 'mssql_patients.BD_Rep AS BD_Rep'),
                DB::raw($newref
                    ? 'intake.living_situation AS living_situation'
                    : 'mssql_patients.living_situation AS living_situation'),
                DB::raw($newref
                    ? 'intake.ic_id AS ic_id'
                    : 'mssql_patients.ic_id AS ic_id'),
                DB::raw($newref
                    ? 'intake.CF_ReferralType AS CF_ReferralType'
                    : 'mssql_patients.CF_ReferralType AS CF_ReferralType'),
                DB::raw($newref
                ? 'intake.auth_nurse_id AS auth_nurse_id'
                : 'mssql_patients.auth_nurse_id AS auth_nurse_id'),
            )
            ->leftJoin('mssql_patients',
            'mssql_patients.id', 'intake.aoc_patient_id')
            ->when($newref, function ($query) {
                $query->leftJoin('lob', 'lob.LOBID', '=', 'intake.LOBID');
            }, function ($query) {
                $query->leftJoin('lob', 'lob.LOBID', '=', 'mssql_patients.LOBID');
            })
            ->where('intake.aoc_patient_id', $patientId)
            ->where('intake.id', $intakeId)
            ->orderby('intake.created_at', 'desc')
            ->first();
            $clientDetails = MssqlPatients::where('id', $patientId)->first();
            if(!empty($intakeData)){
                $intakeData['insuranc'] = H::getHelper('H')->getClientInsuranceById($clientDetails);
                $emergencyContactsCollection = $this->constantHelper->getClientEmergencyContactDetails($patientId);
                $intakeData['EmergencyContact'] = [];
                $emergencyContactsArray = $emergencyContactsCollection->toArray();
                if(!empty($emergencyContactsArray)){
                    $intakeData['EmergencyContact'] = $emergencyContactsArray;
                }
                $intakeCustomFields = [];
                $customFieldsList = CustomFields::where('is_deleted', 0)
                ->where('entity', 'client')
                ->where('category', 'Intake')
                ->orderBy('category')
                ->orderBy('sort')
                ->get()
                ->toArray();
                foreach($customFieldsList as $key => $field) {
                    $category = $field['category'];
                    if (empty($category)) {
                        $category = 'Default';
                    }
                    $intakeCustomFields[$category][] = view('ManagingCustomFields.customfields', [
                        'field' => $field,
                        'details' => $clientDetails,
                        'id' => $clientDetails->id
                    ])->render();
                }
                $intakeData['customefileds'] = $intakeCustomFields;
            }
            return view('client.tabs._intakenew', ['intakeData' => $intakeData, 'newrefarray' => $newrefarray]);
        }
    }

    /**
     * @param ClientScheduleDropdownRequest $request
     * @return array
     * @throws Exception
     */
    public function getScheduleClient(ClientScheduleDropdownRequest $request): array
    {
        try {
            $params = $request->toDTO();
            $term = str_replace('  ', ' ', str_replace(',', '', $params->getTerm()));

            $result = $this->clientScheduleService->getClient($term);

            return ClientScheduleDropdownResource::collection($result)->toArray($request);
        } catch (Exception $e) {
            Log::channel('client')->error('Exception caught', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
