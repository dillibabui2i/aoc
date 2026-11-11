<?php

namespace App\Http\Controllers;

use App\Candidates;
use App\Helpers\ConstantHelper;
use App\Helpers\H;
use App\Helpers\LD;
use App\Helpers\Notifications;
use App\Intake;
use App\MssqlPatients;
use App\MssqlRecruitertracker;
use App\NeedToHireAccounts;
use App\NeedToHireChecklist;
use App\Services\Intake\IntakeService;
use App\Services\NeedToHire\SocialMediaAdAutoCloseService;
use DataTables;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IntakeController extends Controller
{
    private const INTAKE_NON_ADMIT = 'intake_nonadmit';
    private const INTAKE_ADMITTED = 'intake_admitted';
    private const SAVE_OUTCOME_MAX_RETRIES = 5;

    public function __construct(
        private IntakeService $intakeService,
        private SocialMediaAdAutoCloseService $socialMediaAdAutoCloseService
    ) {}

    public function getIntakeExceptions(Request $request) {
        if($request->input("type") == 'intake_coverage_shift_covered_by_perm_nurse') {
            $data = H::getHelper("ClientCareCoordinatorException")
            ->getIntakeCoverageShiftCoveredPermNurse('intake',$request);
            return Datatables::of($data)->editColumn('CF_GoalSOCDate', function ($row) {
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
        } elseif($request->input("type") == 'intake_coverage_shift_before_goal_soc_date') {
            $data = H::getHelper("ClientCareCoordinatorException")
            ->getIntakeCoverageShiftBeforeGoalSocDate('intake',$request);
            return Datatables::of($data)->editColumn('CF_GoalSOCDate', function ($row) {
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
            $data = H::getHelper("IntakeCoordinatorException")->getIntakeException($request->input("type"),$request);
        }
            if ($request->input("type") == 'staffed_coverage_shifts_not_updated_in_kantime') {
                return Datatables::of($data)->editColumn('shift_date', function ($row) {
                    return H::getFormatedDate($row->shift_date);
                })->make(true);
            } else if ($request->input("type") == 'intake_unassigned_care_team') {
                return Datatables::of($data)
                ->editColumn('intake_message', function ($row) {
                    $str = '';
                    if(empty($row->ccc_id)) {
                        $str.='No CCC Assigned to Client<br>';
                    }
                    if(empty($row->ccm_id)) {
                        $str.="No CCM Assigned to Client<br>";
                    }
                    return $str;
                })->escapeColumns('intake_message')->make(true);
            } else {
                return Datatables::of($data)->setRowAttr([
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
                ])->with('plannedhours', function() use ($data) {
                    $totalHr =  number_format(array_sum($data->pluck('PlannedHours')->all()), 0);
                    return '('.$data->count().' | ' . $totalHr . ' <span style="text-transform: initial;">hrs</span>)';
                })->editColumn('CreatedOn', function ($row) {
                    return H::getFormatedDate($row->CreatedOn);
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

                })->addColumn('select', function ($row) use($request){
                    $checkbox = '<input type="checkbox" name="checkbox-'.$request->input("type").'[]" class="form-input td select-all-' . str_replace(' ', '_', $request->input("type")) . '" value="' . $row->id . '" />';
                    return $checkbox;
                })->escapeColumns('select')->make(true);
            }
    }



    public function reloadCountIntakeExceptions(Request $request) {
        if($request->input("type") == 'intake_coverage_shift_covered_by_perm_nurse') {
            return json_encode(["counter"=>H::getHelper('ClientCareCoordinatorException')
            ->getIntakeCoverageShiftCoveredPermNurse('intake',$request,1),"type"=>$request->input("type")]);
        } elseif($request->input("type") == 'intake_coverage_shift_before_goal_soc_date') {
            return json_encode(["counter"=>H::getHelper('ClientCareCoordinatorException')
            ->getIntakeCoverageShiftBeforeGoalSocDate('intake',$request,1),"type"=>$request->input("type")]);
        }else {
            return json_encode(["counter"=>H::getHelper('IntakeCoordinatorException')
            ->getIntakeException($request->input("type"),$request,1),"type"=>$request->input("type")]);
        }
    }

    public function addIntakeForm(Request $request) {
        return view('intake.form');
    }
    public function loadMoreNurseForm(Request $request) {
        return view('intake._nurseForm',['nursesCount'=>$request->input('nursesCount')]);
    }
    public function save(Request $request){
        $reqData = $request->all();
        H::ExcelLog('Intake Form - request data','intake.log');
        H::ExcelLog(print_r($reqData, true),'intake.log');
         try {
        //     Inserting the Patients
            $nameArr = explode(' ',$reqData['clientName']);
            if(LD::checkFeatureFlag('intake-set-microsoft-events')){
                if(!empty($reqData['intakeid']) && isset($reqData['admissionVisitScheduled']) &&  $reqData['admissionVisitScheduled'] == 'on') {
                    $previousIntake = Intake::where("id",$reqData['intakeid'])->first();

                    $eventMatchData = [
                        'admission_visit_date'=>$reqData['admissionVisitDate'],
                        'start_time'=>$reqData['start_time'],
                        'end_time'=>$reqData['end_time'],
                        'intake_nurse_id'=>$reqData['intakeNurse']
                    ];
                    $eventModificationType = H::getHelper("MicrosoftGraph")->isEventRescheduleOrReassign($previousIntake,$eventMatchData);
                }

            }


            if(LD::checkFeatureFlag('intake-form-new-change')){
                $reqData['phy_id'] = '';
                $reqData['ref_id'] = '';
                $isNewRef = false;
                $updateNewref = false;
                if(isset($reqData['referring_physician'])){
                    $reqData['phy_id'] = self::getIdByName('phy', $reqData['referring_physician'])->physician_id;
                }
                if(isset($reqData['referral_source'])){
                    $reqData['ref_id'] = self::getIdByName('ref', $reqData['referral_source'])->source_id;
                }
                $conditionalValue = $reqData['phy_id'];
                if(LD::checkFeatureFlag('make-referral-field-not-required-in-add-intake-form')){
                    $conditionalValue = $reqData['business_development_rep'];
                }
                if((!empty($reqData['clientPId']) && !empty($conditionalValue)) && $reqData['mode'] != 'Edit'){
					H::ExcelLog('in clientPId not empty, phy_id not empty and mode <> edit','intake.log');
                    $isNewRef = self::checkIsNewref($reqData);
                    $reqData['aoc_patient_id'] = $reqData['clientPId'];
                }elseif((!empty($reqData['clientPId']) && (empty($reqData['intakeid']) && $reqData['mode'] != 'Edit'))){
                    H::ExcelLog('in clientPId not empty, phy_id not empty and mode <> edit but patient status = 2,3,5','intake.log');
                    //set IsLatestAdmit value to 0;
                    MssqlPatients::where('PatientAccount', $reqData['clientId'])->update(['IsLatestAdmit' => 0]);
                    $patientData = [
                        'Search_Name' => $reqData['clientName'],
                        'PatientName' => $reqData['clientName'],
                        'Patient_FirstName' => isset($nameArr[0]) ? $nameArr[0] : '',
                        'Patient_LastName' => isset($nameArr[1]) ? $nameArr[1] : '',
                        'ic_id' => isset($reqData['ic_id'])?$reqData['ic_id']:'',
                        'aoc_facility_id' => isset($reqData['aoc_facility_id'])?$reqData['aoc_facility_id']:'',
                        'PatientAccount' => isset($reqData['clientId'])?$reqData['clientId']:'',
                        'Patient_Address1' => isset($reqData['address1'])?$reqData['address1']:'',
                        'Patient_Address2' => isset($reqData['address2'])?$reqData['address2']:'',
                        'Patient_City' => isset($reqData['city'])?$reqData['city']:'',
                        'Patient_State' => isset($reqData['state'])?$reqData['state']:'',
                        'Patient_Zip' => isset($reqData['zipCode'])?$reqData['zipCode']:'',
                        'Payer' => isset($reqData['terminalPlayer'])?$reqData['terminalPlayer']:'',
                        'CF_ClinicianDiscipline' => isset($reqData['clinicianDiscipline']) ? $reqData['clinicianDiscipline'] : '',
                        'RespitePayer' => isset($reqData['respiteTerminalPlayer']) ? $reqData['respiteTerminalPlayer'] : '',
                        'CF_ServicesNeeded' => isset($reqData['serviceNeeded']) ? $reqData['serviceNeeded'] : '',
                        'CF_Skills' => isset($reqData['skills']) ? implode(',',$reqData['skills']) : '',
                        'NTHNotes' => isset($reqData['scheduleDescription']) ? $reqData['scheduleDescription'] : '',
                        'auth_nurse_id' => isset($reqData['auth_nurse_id']) ? $reqData['auth_nurse_id'] : '',
                        'IsLatestAdmit' => 1,
                        'is_intake' => 1,
                        'LOBID' => isset($reqData['lob']) ? $reqData['lob'] : '',
                        'CF_GoalSOCDate' => (isset($reqData['goalSocDate'])
                            && $reqData['goalSocDate'] != NULL)
                            ? date('Y-m-d H:i:s',strtotime($reqData['goalSocDate'])) : '',
                        'CF_Specialized' => (isset($reqData['specializedClient'])
                        && $reqData['specializedClient'] == 'on') ? 'Yes' : 'No',
                        'Patient_CurrentStatusID' => '4',
                        'CF_ReferralType' => isset($reqData['referralType']) ? $reqData['referralType'] : '',
                    ];
                    if(isset($reqData['admissionVisitScheduled']) &&
                    $reqData['admissionVisitScheduled'] == 'on'){
                        $patientData['IntakeStatus'] = 'Scheduled';
                    }else{
                        $patientData['IntakeStatus'] = 'Pending';
                    }

                    if (!empty($reqData['lob'])) {
                        $field = $this->intakeService->resolvePatientHoursField((int)$reqData['lob']);
                        $patientData[$field] = $reqData['anticipatedHours'];
                    }

                    $reqData['aoc_patient_id'] = MssqlPatients::insertGetId($patientData);
                }else{
                    if(!empty($reqData['intakeid']) && $reqData['mode'] == 'Edit'){
						H::ExcelLog('in intakeid not empty and mode = edit','intake.log');
                        $updateNewref = H::getHelper('ConstantHelper')->checkNewRefOnLob($reqData['intakeid']);
                        $reqData['aoc_patient_id'] = $reqData['clientPId'];
                        if($updateNewref){
                            H::getHelper('ConstantHelper')->addNoteWhenIntakeUpdate($reqData);
                        }else{
                            if(!empty($reqData['clientPId'])){
                                if($reqData['mode'] == 'Edit'){
                                    H::getHelper('ConstantHelper')->addNoteWhenIntakeUpdate($reqData);
                                }
                                $patientData = [
                                    // 'Search_Name' => $reqData['clientName'],
                                    // 'PatientName' => $reqData['clientName'],
                                    // 'Patient_FirstName' => isset($nameArr[0]) ? $nameArr[0] : '',
                                    // 'Patient_LastName' => isset($nameArr[1]) ? $nameArr[1] : '',
									'ic_id' => isset($reqData['ic_id'])?$reqData['ic_id']:'',
                                    'aoc_facility_id' => isset($reqData['aoc_facility_id'])?$reqData['aoc_facility_id']:'',
                                    'PatientAccount' => isset($reqData['clientId'])?$reqData['clientId']:'',
                                    'Patient_Address1' => isset($reqData['address1'])?$reqData['address1']:'',
                                    'Patient_Address2' => isset($reqData['address2'])?$reqData['address2']:'',
                                    'Patient_City' => isset($reqData['city'])?$reqData['city']:'',
                                    'Patient_State' => isset($reqData['state'])?$reqData['state']:'',
                                    'Patient_Zip' => isset($reqData['zipCode'])?$reqData['zipCode']:'',
                                    'Payer' => isset($reqData['terminalPlayer'])?$reqData['terminalPlayer']:'',
                                    // 'CF_ClinicianDiscipline' => isset($reqData['clinicianDiscipline']) ? $reqData['clinicianDiscipline'] : '',
                                    'RespitePayer' => isset($reqData['respiteTerminalPlayer']) ? $reqData['respiteTerminalPlayer'] : '',
                                    // 'CF_ServicesNeeded' => isset($reqData['serviceNeeded']) ? $reqData['serviceNeeded'] : '',
                                    // 'CF_Skills' => isset($reqData['skills']) ? implode(',',$reqData['skills']) : '',
                                    // 'NTHNotes' => isset($reqData['scheduleDescription']) ? $reqData['scheduleDescription'] : '',
                                    'auth_nurse_id' => isset($reqData['auth_nurse_id']) ? $reqData['auth_nurse_id'] : '',
                                    'LOBID' => isset($reqData['lob']) ? $reqData['lob'] : '',
                                    //'is_intake' => 1,
                                    'CF_GoalSOCDate' => (isset($reqData['goalSocDate'])
                                        && $reqData['goalSocDate'] != NULL)
                                        ? date('Y-m-d H:i:s',strtotime($reqData['goalSocDate'])) : '',
                                    'CF_Specialized' => (isset($reqData['specializedClient']) && $reqData['specializedClient'] == 'on') ? 'Yes' : 'No',
                                    //'Patient_CurrentStatusID' => '4',
                                    'CF_ReferralType' => isset($reqData['referralType']) ? $reqData['referralType'] : '',
                                ];
                                /*if(isset($reqData['admissionVisitScheduled']) &&
                                $reqData['admissionVisitScheduled'] == 'on'){
                                    $patientData['IntakeStatus'] = 'Scheduled';
                                }*/
                                if (!empty($reqData['lob'])) {
                                    $field = $this->intakeService->resolvePatientHoursField((int)$reqData['lob']);
                                    $patientData[$field] = $reqData['anticipatedHours'];
                                }

                                if(LD::checkFeatureFlag('hide-add-nth-part-from-edit-intake-form')){
                                    unset($patientData['CF_ClinicianDiscipline']);
                                    unset($patientData['CF_ServicesNeeded']);
                                    unset($patientData['CF_Skills']);
                                }
                                MssqlPatients::where('id', $reqData['clientPId'])->update($patientData);
                                $reqData['aoc_patient_id'] = $reqData['clientPId'];
                            }
                        }
                    }else{
						H::ExcelLog('either intakeid is empty or mode is not edit','intake.log');
                        if(!empty($reqData['clientPId'])){
                            if($reqData['mode'] == 'Edit'){
                                H::getHelper('ConstantHelper')->addNoteWhenIntakeUpdate($reqData);
                            }
                            $patientData = [
                                // 'Search_Name' => $reqData['clientName'],
                                // 'PatientName' => $reqData['clientName'],
                                // 'Patient_FirstName' => isset($nameArr[0]) ? $nameArr[0] : '',
                                // 'Patient_LastName' => isset($nameArr[1]) ? $nameArr[1] : '',
								'ic_id' => isset($reqData['ic_id'])?$reqData['ic_id']:'',
                                'aoc_facility_id' => isset($reqData['aoc_facility_id'])?$reqData['aoc_facility_id']:'',
                                'PatientAccount' => isset($reqData['clientId'])?$reqData['clientId']:'',
                                'Patient_Address1' => isset($reqData['address1'])?$reqData['address1']:'',
                                'Patient_Address2' => isset($reqData['address2'])?$reqData['address2']:'',
                                'Patient_City' => isset($reqData['city'])?$reqData['city']:'',
                                'Patient_State' => isset($reqData['state'])?$reqData['state']:'',
                                'Patient_Zip' => isset($reqData['zipCode'])?$reqData['zipCode']:'',
                                'Payer' => isset($reqData['terminalPlayer'])?$reqData['terminalPlayer']:'',
                                'CF_ClinicianDiscipline' => isset($reqData['clinicianDiscipline']) ? $reqData['clinicianDiscipline'] : '',
                                'RespitePayer' => isset($reqData['respiteTerminalPlayer']) ? $reqData['respiteTerminalPlayer'] : '',
                                'CF_ServicesNeeded' => isset($reqData['serviceNeeded']) ? $reqData['serviceNeeded'] : '',
                                'CF_Skills' => isset($reqData['skills']) ? implode(',',$reqData['skills']) : '',
                                'NTHNotes' => isset($reqData['scheduleDescription']) ? $reqData['scheduleDescription'] : '',
                                'auth_nurse_id' => isset($reqData['auth_nurse_id']) ? $reqData['auth_nurse_id'] : '',
                                'LOBID' => isset($reqData['lob']) ? $reqData['lob'] : '',
                                //'is_intake' => 1,
                                'CF_GoalSOCDate' => (isset($reqData['goalSocDate'])
                                    && $reqData['goalSocDate'] != NULL)
                                    ? date('Y-m-d H:i:s',strtotime($reqData['goalSocDate'])) : '',
                                'CF_Specialized' => (isset($reqData['specializedClient']) && $reqData['specializedClient'] == 'on') ? 'Yes' : 'No',
                                //'Patient_CurrentStatusID' => '4',
                                'CF_ReferralType' => isset($reqData['referralType']) ? $reqData['referralType'] : '',
                            ];
                            /*if(isset($reqData['admissionVisitScheduled']) &&
                            $reqData['admissionVisitScheduled'] == 'on'){
                                $patientData['IntakeStatus'] = 'Scheduled';
                            }else{
                                $patientData['IntakeStatus'] = 'Pending';
                            }*/

                            if (!empty($reqData['lob'])) {
                                $field = $this->intakeService->resolvePatientHoursField((int)$reqData['lob']);
                                $patientData[$field] = $reqData['anticipatedHours'];
                            }

                            if(LD::checkFeatureFlag('hide-add-nth-part-from-edit-intake-form')){
                                unset($patientData['CF_ClinicianDiscipline']);
                                unset($patientData['CF_ServicesNeeded']);
                                unset($patientData['CF_Skills']);
                            }
                            MssqlPatients::where('id', $reqData['clientPId'])->update($patientData);
                            $reqData['aoc_patient_id'] = $reqData['clientPId'];
                        }else{
							H::ExcelLog('either clientPId is empty or mode <> edit','intake.log');
                            $patientData = [
                                'Search_Name' => $reqData['clientName'],
                                'PatientName' => $reqData['clientName'],
                                'Patient_FirstName' => isset($nameArr[0]) ? $nameArr[0] : '',
                                'Patient_LastName' => isset($nameArr[1]) ? $nameArr[1] : '',
                                'ic_id' => isset($reqData['ic_id'])?$reqData['ic_id']:'',
                                'aoc_facility_id' => isset($reqData['aoc_facility_id'])?$reqData['aoc_facility_id']:'',
                                'PatientAccount' => isset($reqData['clientId'])?$reqData['clientId']:'',
                                'Patient_Address1' => isset($reqData['address1'])?$reqData['address1']:'',
                                'Patient_Address2' => isset($reqData['address2'])?$reqData['address2']:'',
                                'Patient_City' => isset($reqData['city'])?$reqData['city']:'',
                                'Patient_State' => isset($reqData['state'])?$reqData['state']:'',
                                'Patient_Zip' => isset($reqData['zipCode'])?$reqData['zipCode']:'',
                                'Payer' => isset($reqData['terminalPlayer'])?$reqData['terminalPlayer']:'',
                                'CF_ClinicianDiscipline' => isset($reqData['clinicianDiscipline']) ? $reqData['clinicianDiscipline'] : '',
                                'RespitePayer' => isset($reqData['respiteTerminalPlayer']) ? $reqData['respiteTerminalPlayer'] : '',
                                'CF_ServicesNeeded' => isset($reqData['serviceNeeded']) ? $reqData['serviceNeeded'] : '',
                                'CF_Skills' => isset($reqData['skills']) ? implode(',',$reqData['skills']) : '',
                                'NTHNotes' => isset($reqData['scheduleDescription']) ? $reqData['scheduleDescription'] : '',
                                'auth_nurse_id' => isset($reqData['auth_nurse_id']) ? $reqData['auth_nurse_id'] : '',
                                'IsLatestAdmit' => 1,
                                'is_intake' => 1,
                                'LOBID' => isset($reqData['lob']) ? $reqData['lob'] : '',
                                'CF_GoalSOCDate' => (isset($reqData['goalSocDate'])
                                    && $reqData['goalSocDate'] != NULL)
                                    ? date('Y-m-d H:i:s',strtotime($reqData['goalSocDate'])) : '',
                                'CF_Specialized' => (isset($reqData['specializedClient'])
                                && $reqData['specializedClient'] == 'on') ? 'Yes' : 'No',
                                'Patient_CurrentStatusID' => '4',
                                'CF_ReferralType' => isset($reqData['referralType']) ? $reqData['referralType'] : '',
                            ];
                            if(isset($reqData['admissionVisitScheduled']) &&
                            $reqData['admissionVisitScheduled'] == 'on'){
                                $patientData['IntakeStatus'] = 'Scheduled';
                            }else{
                                $patientData['IntakeStatus'] = 'Pending';
                            }
                            if (!empty($reqData['lob'])) {
                                $field = $this->intakeService->resolvePatientHoursField((int)$reqData['lob']);
                                $patientData[$field] = $reqData['anticipatedHours'];
                            }
                            $reqData['aoc_patient_id'] = MssqlPatients::insertGetId($patientData);
                        }
                    }
                }

            }else{
                $patientData = [
                    'Search_Name' => $reqData['clientName'],
                    'PatientName' => $reqData['clientName'],
                    'Patient_FirstName' => isset($nameArr[0]) ? $nameArr[0] : '',
                    'Patient_LastName' => isset($nameArr[1]) ? $nameArr[1] : '',
                    'ic_id' => isset($reqData['ic_id'])?$reqData['ic_id']:'',
                    'aoc_facility_id' => isset($reqData['aoc_facility_id'])?$reqData['aoc_facility_id']:'',
                    'PatientAccount' => isset($reqData['clientId'])?$reqData['clientId']:'',
                    'Patient_Address1' => isset($reqData['address1'])?$reqData['address1']:'',
                    'Patient_Address2' => isset($reqData['address2'])?$reqData['address2']:'',
                    'Patient_City' => isset($reqData['city'])?$reqData['city']:'',
                    'Patient_State' => isset($reqData['state'])?$reqData['state']:'',
                    'Patient_Zip' => isset($reqData['zipCode'])?$reqData['zipCode']:'',
                    'Payer' => isset($reqData['terminalPlayer'])?$reqData['terminalPlayer']:'',
                    'CF_ClinicianDiscipline' => isset($reqData['clinicianDiscipline']) ? $reqData['clinicianDiscipline'] : '',
                    'RespitePayer' => isset($reqData['respiteTerminalPlayer']) ? $reqData['respiteTerminalPlayer'] : '',
                    'CF_ServicesNeeded' => isset($reqData['serviceNeeded']) ? $reqData['serviceNeeded'] : '',
                    'CF_Skills' => isset($reqData['skills']) ? implode(',',$reqData['skills']) : '',
                    'CF_Specialized' => isset($reqData['skills']) ? (H::getHelper('NTHChecklist')->isSpecialized(implode(',',$reqData['skills'])) == true ? 'Yes' : 'No') : 'No',
                    'NTHNotes' => isset($reqData['scheduleDescription']) ? $reqData['scheduleDescription'] : '',
                    'CF_GoalSOCDate' => (isset($reqData['goalSocDate'])
                    && $reqData['goalSocDate'] != NULL)
                    ? date('Y-m-d H:i:s',strtotime($reqData['goalSocDate'])) : '',
                    'CF_Specialized' => (isset($reqData['specializedClient'])
                    && $reqData['specializedClient'] == 'on') ? 'Yes' : 'No',
                    'CF_ReferralType' => isset($reqData['referralType']) ? $reqData['referralType'] : '',
                ];
                $reqData['aoc_patient_id'] = MssqlPatients::insertGetId($patientData);
            }

            H::ExcelLog('Intake Form - Patient Added ::'.$reqData['aoc_patient_id'],'intake.log');

            $nthAccountId = '';
            $recruiter = '';
            //Inserting the NeedToHire Account
            if($reqData['aoc_patient_id'] != '' && isset($reqData['addToNTH']) && $reqData['addToNTH'] == 'on'){
                H::ExcelLog('Intake Form - Nth process start','intake.log');

                if (LD::checkFeatureFlag('auto-assign-recruiter-addnthform')) {

                    if(isset($reqData['recruiter']) && !empty($reqData['recruiter'])){

                        $recruiter = H::getHelper('ConstantHelper')->getUserById($reqData['recruiter']);

                    } else {

                        if(isset($reqData['address1']) && !empty($reqData['address1'])) {
                            $address['address1'] = $reqData['address1'];
                        }
                        if(isset($reqData['address2']) && !empty($reqData['address2'])) {
                            $address['address2'] = $reqData['address2'];
                        }
                        if(isset($reqData['city']) && !empty($reqData['city'])) {
                            $address['city'] = $reqData['city'];
                        }
                        if(isset($reqData['state']) && !empty($reqData['state'])) {
                            $address['state'] = $reqData['state'];
                        }
                        if(isset($reqData['zipCode']) && !empty($reqData['zipCode'])) {
                            $address['zipCode'] = $reqData['zipCode'];
                        }
                        if (!empty($address)) {
                            $recruiterId = H::getHelper('ConstantHelper')->getRecruiterByPolygon($address);

                            if($recruiterId) {
                                $reqData['recruiter'] = $recruiterId;
                                $recruiter = H::getHelper('ConstantHelper')->getUserById($recruiterId);
                            }
                        }

                    }


                } else {

                    if(isset($reqData['recruiter'])){
                        $recruiter = H::getHelper('ConstantHelper')->getUserById($reqData['recruiter']);
                    }
                }



                $needToHireArray = [
                    'aoc_patient_id' => $reqData['aoc_patient_id'],
                    'ClientID' => $reqData['clientId'],
                    'ServicesNeeded' => isset($reqData['serviceNeeded']) ? $reqData['serviceNeeded'] : '',
                    'skills' => isset($reqData['skills']) ? implode(',',$reqData['skills']) : '',
                    'Recruiter' => $recruiter,
                    'Status' => 'New',
                    'ScheduleText' => isset($reqData['scheduleDescription']) ? $reqData['scheduleDescription'] : '',
                    'RespiteTerminalPayer' => isset($reqData['respiteTerminalPlayer']) ? $reqData['respiteTerminalPlayer'] : '',
                    'clinicianDiscipline' => isset($reqData['clinicianDiscipline']) ? $reqData['clinicianDiscipline'] : '',
                    'TerminalPayer' => isset($reqData['terminalPlayer']) ? $reqData['terminalPlayer'] : '',
                    'IntakeCoordinator' => isset($reqData['ic_id']) ? $reqData['ic_id'] : '',
                    'user_id' => isset($reqData['recruiter']) ? $reqData['recruiter'] : 0,
                    'created_by' => Auth::user()->id,
                    'updated_by' => Auth::user()->id,
                    // 'IsActive'=>1 Not active account from here it will be active whene hours will added.
                ];
                if(!empty($request['nthID'])){
                    if(LD::checkFeatureFlag('hide-add-nth-part-from-edit-intake-form')){
                        // Do nothing when Ff is on.
                        // We have stopped updating Nth when editing intake.
                    }else{
                        H::ExcelLog('Intake Form - NTH Data Edit::'. $request['nthID'],'intake.log');
                        $nthAccountId = NeedToHireAccounts::where('id', $request['nthID'])
                        ->update($needToHireArray);
                        H::ExcelLog('Intake Form - NTH update ::'. $nthAccountId,'intake.log');
                    }
                    $nthAccountId = $request['nthID'];
                }else{
                    $nthAccountId = NeedToHireAccounts::insertGetId($needToHireArray);
                    H::ExcelLog('Intake Form - NTH Added ::'.$nthAccountId,'intake.log');
                    if($nthAccountId != ''){
                        NeedToHireChecklist::insert(['CurrentStep'=>1,'aoc_nthaccounts_id'=>$nthAccountId,'created_by' => Auth::user()->id,'updated_by' => Auth::user()->id]);
                        H::getHelper('NTHChecklist')->AddEditNTHMapRecord($nthAccountId);
                        H::ExcelLog('Intake Form - Checklist Added in NTH ID::'.$nthAccountId,'intake.log');
                    }
                }
            }
            //Inserting the Intake
            $intakeId = '';
            if($reqData['aoc_patient_id'] != '') {
                $intakeData = [
                    'aoc_patient_id' => $reqData['aoc_patient_id'],
                    'aoc_nthaccounts_id' => $nthAccountId,
                    'referral_type' => isset($reqData['referralType']) ? $reqData['referralType'] : '',
                    'hospital' => isset($reqData['hospital']) ? $reqData['hospital'] : '',
                    'hospital_discharge_date' => (isset($reqData['hospitalDischargeDate'])
                    && $reqData['hospitalDischargeDate'] != NULL)
                    ? date('Y-m-d H:i:s',strtotime($reqData['hospitalDischargeDate'])) : '',
                    'admission_visit_scheduled' => isset($reqData['admissionVisitScheduled']) && $reqData['admissionVisitScheduled'] == 'on' ? 1 : 0,
                    'admission_visit_date' => (isset($reqData['admissionVisitDate'])
                    && $reqData['admissionVisitDate'] != NULL) ? date('Y-m-d H:i:s',strtotime($reqData['admissionVisitDate'])) : '',
                    'add_to_nth' => isset($reqData['addToNTH']) && $reqData['addToNTH'] == 'on' ? 1 : 0,
                    'intake_nurse_id'=> isset($reqData['intakeNurse']) ? $reqData['intakeNurse'] : '',
                    'kantime_url'=> isset($reqData['linkToKanTime']) ? $reqData['linkToKanTime'] : '',
                    'notes'=> isset($reqData['notes']) ? $reqData['notes'] : '',
                    'LOBID' => isset($reqData['lob']) ? $reqData['lob'] : '',
                    'CF_GoalSOCDate' => (isset($reqData['goalSocDate'])
                            && $reqData['goalSocDate'] != NULL)
                            ? date('Y-m-d H:i:s',strtotime($reqData['goalSocDate'])) : '',
                    'ic_id' => isset($reqData['ic_id']) ? $reqData['ic_id'] : '',
                ];
                if(LD::checkFeatureFlag('intake-form-new-change')){
                    if(isset($reqData['admissionVisitScheduled']) &&
                    $reqData['admissionVisitScheduled'] == 'on'){
                        $intakeData['start_time'] = date('H:i', strtotime($reqData['start_time']));
                        $intakeData['end_time'] = date('H:i', strtotime($reqData['end_time']));
                        $intakeData['subject'] = $reqData['subject'];
                        $intakeData['location'] = $reqData['location'];
                        $intakeData['description'] = $reqData['description'];
                    }
                    if($isNewRef || $updateNewref){
                        $intakeStatus = '';
                        if($isNewRef){
                            if(isset($reqData['admissionVisitScheduled']) &&
                            $reqData['admissionVisitScheduled'] == 'on'){
                                $intakeStatus = 'Scheduled';
                            }else{
                                $intakeStatus = 'Pending';
                            }
                        }else{
                            if(isset($reqData['admissionVisitScheduled']) &&
                            $reqData['admissionVisitScheduled'] == 'on'){
                                $intakeStatus = 'Scheduled';
                            }
                        }

                        $intakeData['Payer'] = isset($reqData['terminalPlayer'])?$reqData['terminalPlayer']:'';
                        $intakeData['RespitePayer'] = isset($reqData['respiteTerminalPlayer']) ? $reqData['respiteTerminalPlayer'] : '';
                        $intakeData['ic_id'] = isset($reqData['ic_id'])?$reqData['ic_id']:'';
                        $intakeData['CF_ClinicianDiscipline'] = isset($reqData['clinicianDiscipline']) ? $reqData['clinicianDiscipline'] : '';
                        $intakeData['CF_ServicesNeeded'] = isset($reqData['serviceNeeded']) ? $reqData['serviceNeeded'] : '';
                        $intakeData['CF_Skills'] = isset($reqData['skills']) ? implode(',',$reqData['skills']) : '';
                        $intakeData['NTHNotes'] = isset($reqData['scheduleDescription']) ? $reqData['scheduleDescription'] : '';
                        $intakeData['ReferralDate'] = isset($reqData['referral_date']) ?
                            date('y-m-d', strtotime($reqData['referral_date'])) : '';
                        $intakeData['ReferralSourceID'] = $reqData['ref_id'];
                        $intakeData['AttendingPhysicianID'] = $reqData['phy_id'];
                        $intakeData['BD_Rep'] = H::getUserNameById($reqData['business_development_rep']);
                        $intakeData['bd_id'] = $reqData['business_development_rep'];
                        $intakeData['living_situation'] = $reqData['living_situation'];
                        $intakeData['auth_nurse_id'] = isset($reqData['auth_nurse_id']) ? $reqData['auth_nurse_id'] : '';
                        $intakeData['LOBID'] = isset($reqData['lob']) ? $reqData['lob'] : '';
                        $intakeData['CF_ReferralType'] = isset($reqData['referralType']) ? $reqData['referralType'] : '';
                        if(!empty($intakeStatus)){
                            $intakeData['IntakeStatus'] = $intakeStatus;
                        }

                        if (!empty($reqData['lob'])) {
                            $field = $this->intakeService->resolvePatientHoursField((int)$reqData['lob']);

                            DB::table('mssql_patients')
                                ->where('id', $reqData['aoc_patient_id'])
                                ->update([
                                    $field => $reqData['anticipatedHours']
                                ]);
                        }

                        if(LD::checkFeatureFlag('allow-edit-address-field-in-intake-form')){
                            $addressArray = [
                                'Patient_Address1' => isset($reqData['address1'])?$reqData['address1']:'',
                                'Patient_Address2' => isset($reqData['address2'])?$reqData['address2']:'',
                                'Patient_City' => isset($reqData['city'])?$reqData['city']:'',
                                'Patient_State' => isset($reqData['state'])?$reqData['state']:'',
                                'Patient_Zip' => isset($reqData['zipCode'])?$reqData['zipCode']:'',
                            ];
                            DB::table('mssql_patients')->where('id', $reqData['aoc_patient_id'])->update($addressArray);
                        }
                        //DB::table('mssql_patients')->where('id', $reqData['aoc_patient_id'])
                        //->update(['IsLatestAdmit' => 1, 'is_intake' => 1]);
                    }
                    if(!empty($reqData['intakeid'])){
						$intakeData['updated_by'] = Auth::user()->id;
                        $intakeData['updated_at'] =  date('Y-m-d H:i:s');
						$intakeData['ic_id'] = isset($reqData['ic_id'])?$reqData['ic_id']:'';
                        if(LD::checkFeatureFlag('hide-add-nth-part-from-edit-intake-form')){
                            unset($intakeData['CF_ClinicianDiscipline']);
                            unset($intakeData['CF_ServicesNeeded']);
                            unset($intakeData['CF_Skills']);
                        }
                        $intakeId = Intake::where('id', $reqData['intakeid'])->update($intakeData);
                        $intakeId = $reqData['intakeid'];
                        H::ExcelLog('Intake Form - Intake update::'.$intakeId,'intake.log');
                    }else{
                        if(isset($reqData['admissionVisitScheduled']) && $reqData['admissionVisitScheduled'] == 'on'){
                            $intakeData['intake_bucket'] = 'intake_schedule';
                        }
                        $intakeData['created_by'] =  Auth::user()->id;
                        $intakeData['created_at'] =  date('Y-m-d H:i:s');
                        $intakeData['IntakeDate'] =  date('Y-m-d H:i:s');
                        $intakeData['ReferralDate'] = date('Y-m-d H:i:s');
                        $intakeId = Intake::insertGetId($intakeData);
                        H::ExcelLog('Intake Form - Intake Added::'.$intakeId,'intake.log');
                    }

                }else{
                    $intakeId = Intake::insertGetId($intakeData);
                    H::ExcelLog('Intake Form - Intake Added::'.$intakeId,'intake.log');
                }

                if(LD::checkFeatureFlag('intake-set-microsoft-events')) {
                    if(isset($reqData['admissionVisitScheduled']) &&  $reqData['admissionVisitScheduled'] == 'on'){
                       $intakeNurseID = $reqData['intakeNurse'];
                       if(!empty($reqData['intakeid'])) {
                            $intakeId = $reqData['intakeid'];
                       }

                       $intakeNurseUser = H::getUserById($intakeNurseID);
                       if(strstr($intakeNurseUser->user_timezone,"#")){
                            $timezone = explode("#",$intakeNurseUser->user_timezone)[0];
                       } else {
                            $timezone =$intakeNurseUser->user_timezone;
                       }

                       $eventStartDate = date('Y-m-d\TH:i:s', strtotime($reqData['admissionVisitDate'].' '.$reqData['start_time']));
                       $eventEndDate = date('Y-m-d\TH:i:s', strtotime($reqData['admissionVisitDate'].' '.$reqData['end_time']));

                        $eventData = [
                            'subject'=>$reqData['subject'],
                            'description'=>$reqData['description'],
                            'location'=>$reqData['location'],
                            'event_startdate'=>$eventStartDate,
                            'event_enddate'=>$eventEndDate,
                            'timezone'=>$timezone,
                            'attendee'=>$intakeNurseUser->email,
                            'attendee_name'=>$intakeNurseUser->name
                        ];



                        $aocEventData = [
                            'user_id'=>$intakeNurseID,
                            'entity_id'=> $intakeId,
                            'entity_type'=>'intake',
                            'event_status'=>'open'
                        ];

                       if($event=H::getHelper("MicrosoftGraphBase")->isEventExist($intakeId,"intake")){
                            if($eventModificationType=='reschedule'){
                                H::ExcelLog("Reschedule :");
                                if(H::getHelper("MicrosoftGraph")->getUserByEmail(Auth::user()->email)) {

                                    $userEmail = H::getHelper("MicrosoftGraph")->getEventUserEmail($event->created_by);

                                    H::getHelper("MicrosoftGraph")
                                    ->getUserByEmail($userEmail)
                                    ->cancelEvent($event->event_id);

                                    $eventId = H::getHelper("MicrosoftGraph")
                                    ->getUserByEmail(Auth::user()->email)
                                    ->createEvent($eventData,$aocEventData);

                                    $notesData = [
                                        'entity' => 'intake',
                                        'entity_id' => $intakeId,
                                        'notes' => 'Event has been Rescheduled to '.H::getFormatedDateTime($eventStartDate).' - '.H::getFormatedDateTime($eventEndDate)];
                                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                                }


                            } else if($eventModificationType=='reassign') {
                                H::ExcelLog("Reassign :");
                               // $userEmail = H::getHelper("MicrosoftGraph")->getEventUserEmail($event->user_id);

                                if(H::getHelper("MicrosoftGraph")->getUserByEmail(Auth::user()->email)) {

                                    $userEmail = H::getHelper("MicrosoftGraph")->getEventUserEmail($event->created_by);

                                    H::getHelper("MicrosoftGraph")
                                    ->getUserByEmail($userEmail)
                                    ->deleteEvent($event->event_id);

                                    $eventId = H::getHelper("MicrosoftGraph")
                                    ->getUserByEmail(Auth::user()->email)
                                    ->createEvent($eventData,$aocEventData);

                                    $notesData = [
                                    'entity' => 'intake',
                                    'entity_id' => $intakeId,
                                    'notes' => 'Event has been cancelled with  '.H::getUserNameById($event->user_id).' By '.Auth::user()->name];
                                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);

                                    $notesData = [
                                    'entity' => 'intake',
                                    'entity_id' => $intakeId,
                                    'notes' => 'Event invite has been send to  '.H::getUserNameById($intakeNurseID).' By '.Auth::user()->name];
                                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);

                                }
                            }

                       } else {
                            if(H::getHelper("MicrosoftGraph")->getUserByEmail(Auth::user()->email)) {
                                $eventId = H::getHelper("MicrosoftGraph")
                                ->getUserByEmail(Auth::user()->email)
                                ->createEvent($eventData,$aocEventData);
                            }
                       }
                    }

                }

                //Adding the Intake Notes
                if($reqData['notes'] != ''){
                    $notesData = ['entity' => 'intake', 'entity_id' => $intakeId, 'notes' => $reqData['notes'], 'completed' => 1];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                }
            }

            //adding nurses
            $transferNurses = [];
            if(isset($reqData['nursesCount']) && $reqData['nursesCount'] > 0){
                for($i=0; $i < $reqData['nursesCount']; $i++){
                    if(isset($reqData['firstLastName'.$i]) && $reqData['firstLastName'.$i] != ''){
                        if(LD::checkFeatureFlag('intake-form-new-change')){
                            $transferNurses[$i] = [
                                'firstLastName'=>$reqData['firstLastName'.$i],
                                'phoneNumber'=>$reqData['phoneNumber'.$i],
                                'currentRate'=>$reqData['currentRate'.$i],
                                'email'=>$reqData['email'.$i],
                                'discipline'=>$reqData['discipline'.$i],
                                'GP'=>$reqData['gp'.$i],
                                'approved_by'=>$reqData['approved_by'.$i],
                                'paystubsRequest'=>isset($reqData['paystubsRequest'.$i]) && $reqData['paystubsRequest'.$i] == 'on' ? 'Paystubs Requested' : 'Paystubs NOT Requested',
                                'candidateId' => $reqData['candidateId'.$i],
                            ];
                        }else{
                            $transferNurses[$i] = [
                                'firstLastName'=>$reqData['firstLastName'.$i],
                                'phoneNumber'=>$reqData['phoneNumber'.$i],
                                'currentRate'=>$reqData['currentRate'.$i],
                                'email'=>$reqData['email'.$i],
                                'paystubsRequest'=>isset($reqData['paystubsRequest'.$i]) && $reqData['paystubsRequest'.$i] == 'on' ? 'Paystubs Requested' : 'Paystubs NOT Requested',
                            ];
                        }
                    }
                }
            }

            //Adding the candidates and the recruiter trackers details
            $candidateArray = [];
            if(!empty($transferNurses)){
                foreach($transferNurses as $k => $nurse){
                    $nameArr = explode(' ',$nurse['firstLastName']);
                    $candidates = [
                        'firstName'=>(isset($nameArr[0])) ?
                        $nameArr[0] : '',
                        'lastName'=>(isset($nameArr[1])) ?
                        $nameArr[1] : '',
                        'fullName'=>$nurse['firstLastName'],
                        'Search_Name'=>$nurse['firstLastName'],
                        'email'=>$nurse['email'],
                        'mobile'=>$nurse['phoneNumber'],
                        'created_at'=>date('Y-m-d H:i:s'),
                        'created_by'=>Auth::user()->id,
                    ];
                    if(LD::checkFeatureFlag('intake-form-new-change')){
                        $candidates['discipline'] = $nurse['discipline'];
                        $candidates['GP'] = (isset($nurse['GP']))
                        ? number_format($nurse['GP'] / 100, 2) : '';
                        $candidates['approved_by'] = $nurse['approved_by'];
                        $candidates['Agreed_Pay_Rate'] = $nurse['currentRate'];
                    }
                    if(!empty($nurse['candidateId'])){
                        $candidateId = Candidates::where('id', $nurse['candidateId'])
                        ->update($candidates);
                        $candidateId = $nurse['candidateId'];
                        H::ExcelLog('Intake Form - Candidates update::'.$candidateId,'intake.log');
                    }else{
                        $candidateId = Candidates::insertGetId($candidates);
                        H::ExcelLog('Intake Form - Candidates Added::'.$candidateId,'intake.log');
                    }

                    //Adding the Intake Notes
                    $notesData = ['entity' => 'candidate', 'entity_id' => $candidateId, 'notes' => 'Nurse\'s current rate is '.$nurse['currentRate'].' ('.$nurse['paystubsRequest'].')', 'completed' => 1];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                    $trakreid = Intake::getRecruiterTrackerId($candidateId);
                    $candidateArray[] = $candidateId;
                    $ic_Name = '';
                    if(!empty($reqData['ic_id'])){
                        $ic_Name = H::getHelper("ConstantHelper")->getUserNameById($reqData['ic_id']);
                    }

                    if(LD::checkFeatureFlag('auto-assign_ic-as-tracker-recruiter')) {
                        if(empty($recruiter)) {
                            $recruiter = $ic_Name;
                        }
                    }


                    if($candidateId != ''){
                        $recruiterTracker = [
                            'aoc_patient_id'=>$reqData['aoc_patient_id'],
                            'aoc_intake_id'=>$intakeId,
                            'nurse_status'=>0,
                            'aoc_candidate_id'=>$candidateId,
                            'aoc_facility_id'=>isset($reqData['aoc_facility_id'])?$reqData['aoc_facility_id']:'',
                            'Candidate'=>$nurse['firstLastName'],
                            'user_id' => isset($reqData['ic_id'])?$reqData['ic_id']:0,
                            'Recruiter' => $recruiter,
                            'Candidate_Current_Status'=>'Need to schedule IPEA',
                            'created_at'=>date('Y-m-d H:i:s'),
                            'created_by'=>Auth::user()->id,
                            'Agreed_pay_rate' => $nurse['currentRate']??0,
                            'Approved_By' => H::getUserNameById($nurse['approved_by']),
                            'GP' => (isset($nurse['GP']))
                            ? number_format($nurse['GP'] / 100, 2) : '',
                            'Skillset' => $nurse['discipline'],
                        ];
                        if($trakreid != 0){
                            unset($recruiterTracker['Candidate_Current_Status']);
                            $recruiterTrackerId = MssqlRecruitertracker::where('id', $trakreid)
                            ->update($recruiterTracker);
                            H::ExcelLog('Intake Form - Recruiter Tracker update::'.$trakreid,'intake.log');
                        }else{
                            $recruiterTrackerId = MssqlRecruitertracker::insertGetId($recruiterTracker);
                            H::ExcelLog('Intake Form - Recruiter Tracker Added::'.$recruiterTrackerId,'intake.log');
                        }
                    }
                }
            }
            if(!empty($reqData['intakeid'])){
                Intake::deleteCandiateData($intakeId, $candidateArray);
            }
            return response()->json(['status'=>true,'aoc_nthaccounts_id'=>$nthAccountId,
            'aoc_patient_id' => $reqData['aoc_patient_id'], 'intake_id'=> $intakeId]);
        } catch (\Exception $e) {
            H::ExcelLog('Save Intake:'.$e->getMessage(),'intake.log');
            return response()->json(['status'=>false,'msg'=>$e->getMessage()]);
        }
    }

    public function editIntakeForm(Request $request){
        $id = $request->id;
        $newRef = H::getHelper('ConstantHelper')->checkNewRefOnLob($id);
        $intakeData = [];
        if (!empty($id)) {
            $intake = Intake::where('id', $id)->first();
            if (!empty($intake)) {
                if(!empty($newRef)){
                    $intakeData['clientPId'] = $intake->aoc_patient_id??'';
                    $intakeData['nthID'] = $intake->aoc_nthaccounts_id??'';
                    $intakeData['intakeid'] = $intake->id??'';
                    $intakeData['referralType'] = $intake->CF_ReferralType??'';
                    $intakeData['hospital'] = $intake->hospital??'';
                    $intakeData['hospitalDischargeDate'] = ($intake->hospital_discharge_date == '0000-00-00 00:00:00')
                    ? null : ($intake->hospital_discharge_date ?? '');
                    $intakeData['admissionVisitScheduled'] = $intake->admission_visit_scheduled??'';
                    $intakeData['admissionVisitDate'] = ($intake->admission_visit_date == '0000-00-00 00:00:00')
                    ? null : ($intake->admission_visit_date ?? '');
                    $intakeData['addToNTH'] = $intake->add_to_nth??'';
                    $intakeData['intakeNurse'] = $intake->intake_nurse_id??'';
                    $intakeData['linkToKanTime'] = $intake->kantime_url??'';
                    $intakeData['notes'] = $intake->notes??'';
                    $intakeData['start_time'] = $intake->start_time??'';
                    $intakeData['end_time'] = $intake->end_time??'';
                    $intakeData['subject'] = $intake->subject??'';
                    $intakeData['location'] = $intake->location??'';
                    $intakeData['description'] = $intake->description??'';
                    $intakeData['goalSocDate'] = ($intake->CF_GoalSOCDate == '0000-00-00 00:00:00')
                    ? null : ($intake->CF_GoalSOCDate ?? '');
                    $intakeData['terminalPlayer'] = $intake->Payer??'';
                    $intakeData['clinicianDiscipline'] = $intake->CF_ClinicianDiscipline??'';
                    $intakeData['respiteTerminalPlayer'] = $intake->RespitePayer??'';
                    $intakeData['serviceNeeded'] = $intake->CF_ServicesNeeded??'';
                    $intakeData['skills'] = $intake->CF_Skills??'';
                    $intakeData['scheduleDescription'] = $intake->NTHNotes??'';
                    $intakeData['auth_nurse_id'] = $intake->auth_nurse_id??'';
                    $intakeData['LOBID'] = $intake->LOBID??'';
                    $intakeData['referral_source'] = $intake->ReferralSourceID ? self::getRefSourceById($intake->ReferralSourceID) :'';
                    $intakeData['business_development_rep'] = $intake->BD_Rep??'';
                    $intakeData['referral_date'] = $intake->ReferralDate??'';
                    $intakeData['referring_physician'] = $intake->AttendingPhysicianID? self::AttendingPhysicianByID($intake->AttendingPhysicianID):'';
                    $intakeData['living_situation'] = $intake->living_situation??'';
                    $patientData = MssqlPatients::where('id', $intake->aoc_patient_id)->first();
                    $intakeData['clientName'] = collect([
                        $patientData->Patient_FirstName,
                        $patientData->Patient_MiddleName,
                        $patientData->Patient_LastName
                    ])->filter()->implode(' ');
                    $intakeData['clientFirstName'] = $patientData->Patient_FirstName??'';
                    $intakeData['clientLastName'] = $patientData->Patient_LastName??'';
                    $intakeData['ic_id'] = $intake->ic_id??'';
                    $intakeData['aoc_facility_id'] = $patientData->aoc_facility_id??'';
                    $intakeData['clientId'] = $patientData->PatientAccount??'';
                    $intakeData['address1'] = $patientData->Patient_Address1??'';
                    $intakeData['address2'] = $patientData->Patient_Address2??'';
                    $intakeData['city'] = $patientData->Patient_City??'';
                    $intakeData['state'] = $patientData->Patient_State??'';
                    $intakeData['zipCode'] = $patientData->Patient_Zip??'';
                    $intakeData['specializedClient'] = $patientData->CF_Specialized??'';

                    $field = $this->intakeService->resolvePatientHoursField((int)$intake->LOBID);
                    $intakeData['anticipatedHours'] = $patientData->$field ?? null;

                    $nthAccountId = NeedToHireAccounts::where('id', $intake->aoc_nthaccounts_id)->first();
                    $intakeData['recruiter'] = $nthAccountId->user_id??'';
                    $recruiterTrackerId = MssqlRecruitertracker::where('aoc_intake_id', $intake->id)->get();
                    foreach ($recruiterTrackerId as $key => $data) {
                        $candidateData = Candidates::where('id', $data->aoc_candidate_id)->first();
                        $intakeData['nursedata'][$key]['id'] = $candidateData->id??'';
                        $intakeData['nursedata'][$key]['firstLastName'] = $candidateData->fullName??'';
                        $intakeData['nursedata'][$key]['email'] = $candidateData->email??'';
                        $intakeData['nursedata'][$key]['phoneNumber'] = $candidateData->mobile??'';
                        $intakeData['nursedata'][$key]['discipline'] = $candidateData->discipline??'';
                        $intakeData['nursedata'][$key]['gp'] = $candidateData->GP? ($candidateData->GP*100) :'';
                        $intakeData['nursedata'][$key]['approved_by'] = $candidateData->approved_by??'';
						$intakeData['nursedata'][$key]['Agreed_Pay_Rate'] = $candidateData->Agreed_Pay_Rate??'';
                    }
                }else{
                    $intakeData['clientPId'] = $intake->aoc_patient_id??'';
                    $intakeData['nthID'] = $intake->aoc_nthaccounts_id??'';
                    $intakeData['intakeid'] = $intake->id??'';
                    $intakeData['hospital'] = $intake->hospital??'';
                    $intakeData['hospitalDischargeDate'] = ($intake->hospital_discharge_date == '0000-00-00 00:00:00')
                    ? null : ($intake->hospital_discharge_date ?? '');
                    $intakeData['admissionVisitScheduled'] = $intake->admission_visit_scheduled??'';
                    $intakeData['admissionVisitDate'] = ($intake->admission_visit_date == '0000-00-00 00:00:00')
                    ? null : ($intake->admission_visit_date ?? '');
                    $intakeData['addToNTH'] = $intake->add_to_nth??'';
                    $intakeData['intakeNurse'] = $intake->intake_nurse_id??'';
                    $intakeData['linkToKanTime'] = $intake->kantime_url??'';
                    $intakeData['notes'] = $intake->notes??'';
                    $intakeData['start_time'] = $intake->start_time??'';
                    $intakeData['end_time'] = $intake->end_time??'';
                    $intakeData['subject'] = $intake->subject??'';
                    $intakeData['location'] = $intake->location??'';
                    $intakeData['description'] = $intake->description??'';
                    $patientData = MssqlPatients::where('id', $intake->aoc_patient_id)->first();
                    $field = $this->intakeService->resolvePatientHoursField((int)$intake->LOBID);
                    $intakeData['anticipatedHours'] = $patientData->$field ?? null;
                    $intakeData['goalSocDate'] = ($intake->CF_GoalSOCDate == '0000-00-00 00:00:00')
                    ? null : ($intake->CF_GoalSOCDate ?? '');
                    $intakeData['referralType'] = $patientData->CF_ReferralType??'';
                    $intakeData['clientName'] = collect([
                        $patientData->Patient_FirstName,
                        $patientData->Patient_MiddleName,
                        $patientData->Patient_LastName
                    ])->filter()->implode(' ');
                    $intakeData['clientFirstName'] = $patientData->Patient_FirstName??'';
                    $intakeData['clientLastName'] = $patientData->Patient_LastName??'';
                    $intakeData['ic_id'] = $patientData->ic_id??'';
                    $intakeData['aoc_facility_id'] = $patientData->aoc_facility_id??'';
                    $intakeData['clientId'] = $patientData->PatientAccount??'';
                    $intakeData['address1'] = $patientData->Patient_Address1??'';
                    $intakeData['address2'] = $patientData->Patient_Address2??'';
                    $intakeData['city'] = $patientData->Patient_City??'';
                    $intakeData['state'] = $patientData->Patient_State??'';
                    $intakeData['zipCode'] = $patientData->Patient_Zip??'';
                    $intakeData['terminalPlayer'] = $patientData->Payer??'';
                    $intakeData['clinicianDiscipline'] = $patientData->CF_ClinicianDiscipline??'';
                    $intakeData['respiteTerminalPlayer'] = $patientData->RespitePayer??'';
                    $intakeData['serviceNeeded'] = $patientData->CF_ServicesNeeded??'';
                    $intakeData['skills'] = $patientData->CF_Skills??'';
                    $intakeData['scheduleDescription'] = $patientData->NTHNotes??'';
                    $intakeData['auth_nurse_id'] = $patientData->auth_nurse_id??'';
                    $intakeData['LOBID'] = $intake->LOBID ?? '';
                    $intakeData['referral_source'] = $patientData->ReferralSourceID ? self::getRefSourceById($patientData->ReferralSourceID) :'';
                    $intakeData['business_development_rep'] = $patientData->BD_Rep??'';
                    $intakeData['referral_date'] = $patientData->ReferralDate??'';
                    $intakeData['referring_physician'] = $intake->AttendingPhysicianID? self::AttendingPhysicianByID($intake->AttendingPhysicianID):'';
                    $intakeData['living_situation'] = $patientData->living_situation??'';
                    $intakeData['specializedClient'] = $patientData->CF_Specialized??'';
                    $nthAccountId = NeedToHireAccounts::where('id', $intake->aoc_nthaccounts_id)->first();
                    $intakeData['recruiter'] = $nthAccountId->user_id??'';
                    $recruiterTrackerId = MssqlRecruitertracker::where('aoc_intake_id', $intake->id)->get();
                    foreach ($recruiterTrackerId as $key => $data) {
                        $candidateData = Candidates::where('id', $data->aoc_candidate_id)->first();
                        $intakeData['nursedata'][$key]['id'] = $candidateData->id??'';
                        $intakeData['nursedata'][$key]['firstLastName'] = $candidateData->fullName??'';
                        $intakeData['nursedata'][$key]['email'] = $candidateData->email??'';
                        $intakeData['nursedata'][$key]['phoneNumber'] = $candidateData->mobile??'';
                        $intakeData['nursedata'][$key]['discipline'] = $candidateData->discipline??'';
                        $intakeData['nursedata'][$key]['gp'] = $candidateData->GP? ($candidateData->GP*100) :'';
                        $intakeData['nursedata'][$key]['approved_by'] = $candidateData->approved_by??'';
                        $intakeData['nursedata'][$key]['Agreed_Pay_Rate'] = $candidateData->Agreed_Pay_Rate??'';
                    }
                }

                return view('intake.form', ['intakeData' => $intakeData, 'newRef' => $newRef]);
            }
        }
    }

    protected function getDataTableNeedToScheduleIpea($data)
    {
        return Datatables::query($data)->setRowAttr([
            'class' => function($row) {
                if(LD::checkFeatureFlag('red-highlights-intake-all-buckets')){
                    if(!in_array($row->LOBID, ['1003', '1004', '1006']) &&
                    isset($row->tracker_created_date) &&
                    strtotime('now') > strtotime($row->tracker_created_date . ' +1 weekday')){
                        return 'redexception';
                    }
                    return '';
                }else{
                    return '';
                }

            },
        ])->filterColumn('intakeCordinator', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw("users.name like ?", ["%{$keywords}%"]);
            })
            ->filterColumn('clinician', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw("CONCAT(candidates.firstName, ' ', candidates.lastName) like ?", ["%{$keywords}%"]);
            })
            ->filterColumn('patient_name', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw("CONCAT(mssql_patients.Patient_FirstName,' ',
                mssql_patients.Patient_LastName) like ?", ["%{$keywords}%"]);
            })
            ->filterColumn('FacilityName', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw("mssql_facilities.FacilityName like ?", ["%{$keywords}%"]);
            })
            ->filterColumn('mobile', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw("candidates.mobile like ?", ["%{$keywords}%"]);
            })
            ->editColumn('notes', function ($data) {
                if (!empty ($data->notes)) {
                    [$created_at, $notes, $created_by] = explode("#", $data->notes);
                    $notes = str_replace(['"',"<br>"], ["'",', '], (preg_replace('/^\s+|\s+$|\s+(?=\s)/', '', $notes)));
                    $userName = H::getUserNameById($created_by);
                    $date = date('m/d', strtotime($created_at));
                    $note = $date . ' ' . $userName . ' commented: ' . $notes;
                    $str = '';
                    if (strlen($note) > 35) {
                        $str = substr($note, 0, 35) . '...';
                    } else {
                        $str = $note;
                    }
                    return "<span data-toggle='tooltip' title=\"" .
                        mb_convert_encoding($note, 'UTF-8', 'UTF-8') . "\">" .
                        mb_convert_encoding($str, 'UTF-8', 'UTF-8') .
                        '</span>';
                }
                return "-";
            })
            ->filterColumn('notes', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw("(select CONCAT(notes.created_at,'#',notes.notes,'#',notes.created_by) from notes
            where mssql_recruitertracker.id = notes.entity_id order by created_at desc limit 1) like ?", ["%{$keywords}%"]);
            })
            ->addColumn('Action', function ($row) {
                $btn = '';

                $btn .= '<a
                href="javascript:void(0)"
                onclick="showScheduleForm('.$row->id.', \'INTAKE-SCHEDULE-IPEA\');">
                    <i class="fa fa-calendar" aria-hidden="true"></i>
                </a>';

                $btn .= '<a
                    href="javascript:void(0)"
                    onclick="getClientDetails(' . $row->aoc_patient_id . ')"
                    title="View Client">
                        <i class="fa fa-user" aria-hidden="true"></i>
                    </a>';
                $btn .= '<a
                    title="Add Notes"
                    data-bs-dismiss="modal"
                    href="javascript:void(0);"
                    onclick="openNotes(
                        ' . $row->id . ',
                        \'tracker\',
                        \'Recruiter Tracker Notes Added\'
                    )">
                        <i class="fa  fa-sticky-note-o"></i>
                    </a>';
                return $btn;
            })
            ->rawColumns(['notes', 'Action'])
            ->make(true);
    }

    protected function getDataTableCandidateStarts($data)
    {

        return Datatables::query($data)->setRowAttr([
            'class' => function($row) {
                if(LD::checkFeatureFlag('red-highlights-intake-all-buckets')){
                    if(!in_array($row->LOBID, ['1003', '1004', '1006']) &&
                    isset($row->Candidate_Start_Date_Time) &&
                    strtotime('now') > strtotime($row->Candidate_Start_Date_Time . ' +1 weekday')){
                        return 'redexception';
                    }
                    return '';
                }else{
                    return '';
                }

            },
        ])->filterColumn('intakeCordinator', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw("users.name like ?", ["%{$keywords}%"]);
            })
            ->filterColumn('patient_name', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw("CONCAT(mssql_patients.Patient_FirstName,' ',
                mssql_patients.Patient_LastName) like ?", ["%{$keywords}%"]);
            })
            ->filterColumn('FacilityName', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw("mssql_facilities.FacilityName like ?", ["%{$keywords}%"]);
            })
            ->filterColumn('clinician', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw("CONCAT(candidates.firstName, ' ', candidates.lastName) like ?", ["%{$keywords}%"]);
            })
            ->filterColumn('PatientStatus', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw('mssql_patientstatus.PatientStatus like ?', ["%{$keywords}%"]);
            })
            ->editColumn('Candidate_Notes', function ($data) {
                if (!empty ($data->Candidate_Notes)) {
                    [$created_at, $notes, $created_by] = explode("#", $data->Candidate_Notes);
                    $notes = str_replace(['"',"<br>"], ["'",', '], (preg_replace('/^\s+|\s+$|\s+(?=\s)/', '', $notes)));
                    $userName = H::getUserNameById($created_by);
                    $date = date('m/d', strtotime($created_at));
                    $note = $date . ' ' . $userName . ' commented: ' . $notes;
                    $str = '';
                    if (strlen($note) > 35) {
                        $str = substr($note, 0, 35) . '...';
                    } else {
                        $str = $note;
                    }
                    return "<span data-toggle='tooltip' title=\"" .
                        mb_convert_encoding($note, 'UTF-8', 'UTF-8') . "\">" .
                        mb_convert_encoding($str, 'UTF-8', 'UTF-8') .
                        '</span>';
                }
                return "-";
            })->editColumn('Candidate_Start_Date_Time', function ($row) {
                if(!empty($row->Candidate_Start_Date_Time)){
                    return [
                        'display' => e(H::getFormatedDateTime($row->Candidate_Start_Date_Time)),
                        'timestamp' => strtotime($row->Candidate_Start_Date_Time)
                    ];
                } else {
                    return [
                        'display' => '',
                        'timestamp' => ''
                    ];
                }
            })->editColumn('CF_GoalSOCDate', function ($row) {
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
            })

            ->addColumn('Action', function ($row) {
                $btn = '';
                $btn .= '<a
                    href="javascript:void(0)"
                    onclick="showScheduleForm(\'' . $row->id . '\', \'INTAKE-CANDIDATE-STARTS\')"
                    title="Schedule Next Step">
                        <i class="fa fa-calendar" aria-hidden="true"></i>
                    </a>';
                $btn .= '<a
                    href="javascript:void(0)"
                    onclick="openRecruiterDetails(\'' . $row->id . '\')"
                    title="View Tracker Detail">
                        <i class="fa fa-eye" aria-hidden="true"></i>
                    </a>';
                $btn .= '<a
                    data-bs-dismiss="modal"
                    href="javascript:void(0);"
                    title="Add Notes"
                    onclick="openNotes(
                        ' . $row->id . ',
                        \'tracker\',
                        \'Recruiter Tracker Notes Added\'
                    )">
                        <i class="fa  fa-sticky-note-o"></i>
                    </a>';
                return $btn;
            })
            ->rawColumns(['Candidate_Notes', 'Action'])
            ->make(true);
    }

    protected function getDataTablePendingCandidateStarts($data)
    {

        return Datatables::query($data)
            ->filterColumn('intakeCordinator', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw("users.name like ?", ["%{$keywords}%"]);
            })
            ->filterColumn('patient_name', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw("CONCAT(mssql_patients.Patient_FirstName,' ',
                mssql_patients.Patient_LastName) like ?", ["%{$keywords}%"]);
            })
            ->filterColumn('FacilityName', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw("mssql_facilities.FacilityName like ?", ["%{$keywords}%"]);
            })
            ->filterColumn('clinician', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw("CONCAT(candidates.firstName, ' ', candidates.lastName) like ?", ["%{$keywords}%"]);
            })
            ->filterColumn('PatientStatus', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw('mssql_patientstatus.PatientStatus like ?', ["%{$keywords}%"]);
            })
            ->editColumn('Candidate_Notes', function ($data) {
                if (!empty ($data->Candidate_Notes)) {
                    [$created_at, $notes, $created_by] = explode("#", $data->Candidate_Notes);
                    $notes = str_replace(['"',"<br>"], ["'",', '], (preg_replace('/^\s+|\s+$|\s+(?=\s)/', '', $notes)));
                    $userName = H::getUserNameById($created_by);
                    $date = date('m/d', strtotime($created_at));
                    $note = $date . ' ' . $userName . ' commented: ' . $notes;
                    $str = '';
                    if (strlen($note) > 35) {
                        $str = substr($note, 0, 35) . '...';
                    } else {
                        $str = $note;
                    }
                    return "<span data-toggle='tooltip' title=\"" .
                        mb_convert_encoding($note, 'UTF-8', 'UTF-8') . "\">" .
                        mb_convert_encoding($str, 'UTF-8', 'UTF-8') .
                        '</span>';
                }
                return "-";
            })->editColumn('Candidate_Start_Date_Time', function ($row) {
                if(!empty($row->Candidate_Start_Date_Time)){
                    return [
                        'display' => e(H::getFormatedDateTime($row->Candidate_Start_Date_Time)),
                        'timestamp' => strtotime($row->Candidate_Start_Date_Time)
                    ];
                } else {
                    return [
                        'display' => '',
                        'timestamp' => ''
                    ];
                }
            })->editColumn('CF_GoalSOCDate', function ($row) {
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
            })

            ->addColumn('Action', function ($row) {
                $btn = '';
                $btn .= '<a
                    href="javascript:void(0)"
                    onclick="showScheduleForm(\'' . $row->id . '\', \'INTAKE-PENDING-CANDIDATE-STARTS\')"
                    title="Schedule Next Step">
                        <i class="fa fa-calendar" aria-hidden="true"></i>
                    </a>';
                $btn .= '<a
                    href="javascript:void(0)"
                    onclick="openRecruiterDetails(\'' . $row->id . '\')"
                    title="View Tracker Detail">
                        <i class="fa  fa-eye" aria-hidden="true"></i>
                    </a>';
                $btn .= '<a
                    data-bs-dismiss="modal"
                    href="javascript:void(0);"
                    title="Add Notes"
                    onclick="openNotes(
                        ' . $row->id . ',
                        \'tracker\',
                        \'Recruiter Tracker Notes Added\'
                    )">
                        <i class="fa  fa-sticky-note-o"></i>
                    </a>';
                return $btn;
            })
            ->rawColumns(['Candidate_Notes', 'Action'])
            ->make(true);
    }


    protected function getDataTableTodaysIpea($data)
    {

        return Datatables::query($data)->setRowAttr([
            'class' => function($row) {
                if(LD::checkFeatureFlag('red-highlights-intake-all-buckets')){
                    if(!in_array($row->LOBID, ['1003', '1004', '1006']) &&
                    isset($row->IPEA_Date, $row->IPEA_Time)
                ) {
                    $ipeaDateFormatted = date('Y-m-d', strtotime($row->IPEA_Date));
                    $ipeaDateTimeString = $ipeaDateFormatted . ' ' . $row->IPEA_Time;
                    $ipeaDateTimePlusOneHour = strtotime($ipeaDateTimeString . ' +1 hour');
                    if (strtotime('now') >= $ipeaDateTimePlusOneHour) {
                        return 'redexception';
                    }
                }
                    return '';
                }else{
                    return '';
                }

            },
        ])->filterColumn('intakeCordinator', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw("users.name like ?", ["%{$keywords}%"]);
            })
            ->filterColumn('patient_name', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw("CONCAT(mssql_patients.Patient_FirstName,' ',
                mssql_patients.Patient_LastName) like ?", ["%{$keywords}%"]);
            })
            ->filterColumn('FacilityName', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw("mssql_facilities.FacilityName like ?", ["%{$keywords}%"]);
            })
            ->filterColumn('clinician', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw("CONCAT(candidates.firstName, ' ', candidates.lastName) like ?", ["%{$keywords}%"]);
            })
            ->editColumn('notes', function ($data) {
                if (!empty ($data->notes)) {
                    [$created_at, $notes, $created_by] = explode("#", $data->notes);
                    $notes = str_replace(['"',"<br>"], ["'",', '], (preg_replace('/^\s+|\s+$|\s+(?=\s)/', '', $notes)));
                    $userName = H::getUserNameById($created_by);
                    $date = date('m/d', strtotime($created_at));
                    $note = $date . ' ' . $userName . ' commented: ' . $notes;
                    $str = '';
                    if (strlen($note) > 35) {
                        $str = substr($note, 0, 35) . '...';
                    } else {
                        $str = $note;
                    }
                    return "<span data-toggle='tooltip' title=\"" .
                        mb_convert_encoding($note, 'UTF-8', 'UTF-8') . "\">" .
                        mb_convert_encoding($str, 'UTF-8', 'UTF-8') .
                        '</span>';
                }
                return "-";
            })
            ->editColumn('IPEA_Date', function ($row) {
                if(!empty($row->IPEA_Date)){
                    return [
                        'display' => e(H::getFormatedDate($row->IPEA_Date)),
                        'timestamp' => strtotime($row->IPEA_Date)
                    ];
                } else {
                    return [
                        'display' => '',
                        'timestamp' => ''
                    ];
                }
            })
            ->editColumn('IPEA_Time', function ($row) {
                if(!empty($row->IPEA_Time) && $row->IPEA_Time != '00:00:00'){
                        return date('g:i A', strtotime($row->IPEA_Time));
                } else {
                    return '-';
                }
            })
            ->filterColumn('notes', function ($query, $keyword) {
                $keywords = trim($keyword);
                $query->whereRaw("(select CONCAT(notes.created_at,'#',notes.notes,'#',notes.created_by) from notes
                where mssql_patients.id = notes.entity_id order by created_at desc limit 1) like ?", ["%{$keywords}%"]);
            })
            ->addColumn('Action', function ($row) {
                $btn = '';
                $btn .= '<a
                    href="javascript:void(0)"
                    onclick="showScheduleForm(\'' . $row->id . '\', \'INTAKE-TODAY-IPEA\')"
                    title="Schedule Next Step">
                        <i class="fa fa-calendar" aria-hidden="true"></i>
                    </a>';
                $btn .= '<a
                    href="javascript:void(0)"
                    onclick="openRecruiterDetails(\'' . $row->id . '\')"
                    title="View Tracker Detail">
                        <i class="fa fa-eye" aria-hidden="true"></i>
                    </a>';
                $btn .= '<a
                    data-bs-dismiss="modal"
                    href="javascript:void(0);"
                    title="Add Notes"
                    onclick="openNotes(
                        ' . $row->id . ',
                        \'tracker\',
                        \'Recruiter Tracker Notes Added\'
                    )">
                        <i class="fa  fa-sticky-note-o"></i>
                    </a>';
                return $btn;
            })
            ->rawColumns(['notes', 'Action'])
            ->make(true);
    }

    protected function getDataTableProspectiveClients($data, $request)
    {
        $dataTable = Datatables::query($data)->setRowAttr([
            'class' => function($row) {
                if(LD::checkFeatureFlag('red-highlights-intake-all-buckets')){
                    $followUpDate = isset($row->intake_followup)
                                    ? date('Y-m-d', strtotime($row->intake_followup))
                                    : null;
                    $goalSocDate = isset($row->CF_GoalSOCDate)
                                    ? date('Y-m-d', strtotime($row->CF_GoalSOCDate))
                                    : null;

                    $now = date('Y-m-d');
                    $sevenDaysFromNow = date('Y-m-d', strtotime('+7 days'));
                    if(!in_array($row->LOBID, ['1003', '1004', '1006'])){
                        if (
                            // Follow-up date is in the past
                            ($followUpDate < $now) ||

                            // Follow-up date is blank
                            !$followUpDate ||

                            // Follow-up date is more than 7 days in the future
                            ($followUpDate > $sevenDaysFromNow) ||

                            // Goal SOC is in the past
                            ($goalSocDate < $now)
                        ) {
                            return 'redexception';
                        }
                    }elseif(in_array($row->LOBID, ['1003', '1004', '1006'])){
                        if (
                            // Follow-up date is in the past
                            ($followUpDate < $now) ||

                            // Follow-up date is blank
                            !$followUpDate
                        ) {
                            return 'redexception';
                        }
                    }
                    return '';
                }else{
                    return '';
                }

            },
        ])->editColumn('notes', function ($data) {
                if (!empty ($data->notes)) {
                    [$created_at, $notes, $created_by] = explode("#", $data->notes);
                    $notes = str_replace(['"',"<br>"], ["'",', '], (preg_replace('/^\s+|\s+$|\s+(?=\s)/', '', $notes)));
                    $userName = H::getUserNameById($created_by);
                    $date = date('m/d', strtotime($created_at));
                    $note = $date . ' ' . $userName . ' commented: ' . $notes;
                    $str = '';
                    if (strlen($note) > 35) {
                        $str = substr($note, 0, 35) . '...';
                    } else {
                        $str = $note;
                    }
                    return "<span data-toggle='tooltip' title=\"" .
                        mb_convert_encoding($note, 'UTF-8', 'UTF-8') . "\">" .
                        mb_convert_encoding($str, 'UTF-8', 'UTF-8') .
                        '</span>';
                }
                return "-";
            })
            ->editColumn('ReferralDate', function ($row) {
                return H::getFormatedDate($row->ReferralDate);
            })
            ->editColumn('CF_GoalSOCDate', function ($row) {
                return H::getFormatedDate($row->CF_GoalSOCDate);
            })
            ->editColumn('intake_followup', function ($row) {
                return H::getFormatedDate($row->intake_followup);
            })
            ->addColumn('Action', function ($row) {
                $btn = '';
                $btn .= '<a
                    href="javascript:void(0)"
                    onclick="getIntakeStaffTransfersOutcome(\'' . $row->id . '\', \'intake_prospective_clients\', \'' . $row->type . '\')"
                    title="Document Call">
                        <i class="fa fa-calendar" aria-hidden="true"></i>
                    </a>';
                if(LD::checkFeatureFlag('remove-union-intake-dashboard')) {
					$btn .= '<a
						href="javascript:void(0)"
						onclick="getClientDetails(\'' . $row->pid . '\', \'clientintake\')"
						title="View Client Detail">
							<i class="fa fa-user" aria-hidden="true"></i>
						</a>';
				} else {
					$btn .= '<a
						href="javascript:void(0)"
						onclick="getClientDetails(\'' . $row->id . '\', \'clientintake\')"
						title="View Client Detail">
							<i class="fa fa-user" aria-hidden="true"></i>
						</a>';
				}
                return $btn;
            });
            if ($request->has('order')) {
                $columnIndex = $request->get('order')[0]['column'];
                $columnName = $request->get('columns')[$columnIndex]['data'];
                $sortDirection = $request->get('order')[0]['dir'];
                if ($columnName == 'ReferralDate') {
                    $data->orderByRaw("ISNULL(ReferralDate),
                                        ReferralDate = '',
                                        ReferralDate = '0000-00-00 00:00:00',
                                        ReferralDate $sortDirection");
                } else if ($columnName == 'CF_GoalSOCDate') {
                    $data->orderByRaw("ISNULL(CF_GoalSOCDate),
                                        CF_GoalSOCDate = '',
                                        CF_GoalSOCDate = '0000-00-00 00:00:00',
                                        CF_GoalSOCDate $sortDirection");
                } else if ($columnName == 'intake_followup') {
                    $data->orderByRaw("ISNULL(intake_followup),
                                        intake_followup = '',
                                        intake_followup = '0000-00-00 00:00:00',
                                        intake_followup $sortDirection");
                } else {
                    $data->orderByRaw("$columnName $sortDirection");
                }
            }
        return $dataTable->rawColumns(['notes', 'Action'])
            ->make(true);
    }

    protected function getDataTableIntakeSchedule($data, $request)
    {
        $dataTable = Datatables::query($data)->setRowAttr([
            'class' => function($row) {
                if(LD::checkFeatureFlag('red-highlights-intake-all-buckets')){
                    $followUpDate = isset($row->intake_followup) ? strtotime($row->intake_followup) : null;
                    $goalSocDate = isset($row->CF_GoalSOCDate) ? strtotime($row->CF_GoalSOCDate) : null;
                    $now = strtotime('now');
                    if(!in_array($row->LOBID, ['1003', '1004', '1006'])){
                        if (
                            // Follow-up date is in the past
                            ($followUpDate < $now) ||
                            // Goal SOC is in the past
                            ($goalSocDate && $goalSocDate < $now)
                        ) {
                            return 'redexception';
                        }
                    }elseif(in_array($row->LOBID, ['1003', '1004', '1006'])){
                        if (
                            // Follow-up date is in the past
                            ($followUpDate && $followUpDate < $now) ||

                            // Follow-up date is blank
                            !$followUpDate
                        ) {
                            return 'redexception';
                        }
                    }
                    return '';
                }else{
                    return '';
                }

            },
        ])->editColumn('notes', function ($data) {
                if (!empty ($data->notes)) {
                    [$created_at, $notes, $created_by] = explode("#", $data->notes);
                    $notes = str_replace(['"',"<br>"], ["'",', '], (preg_replace('/^\s+|\s+$|\s+(?=\s)/', '', $notes)));
                    $userName = H::getUserNameById($created_by);
                    $date = date('m/d', strtotime($created_at));
                    $note = $date . ' ' . $userName . ' commented: ' . $notes;
                    $str = '';
                    if (strlen($note) > 35) {
                        $str = substr($note, 0, 35) . '...';
                    } else {
                        $str = $note;
                    }
                    return "<span data-html='true' data-toggle='tooltip' title=\"" .
                        $note . "\">" .
                        $str .
                        '</span>';
                }
                return "-";
            })
            ->editColumn('IntakeDate', function ($row) {
                return H::getFormatedDate($row->IntakeDate);
            })
            ->editColumn('CF_GoalSOCDate', function ($row) {
                return H::getFormatedDate($row->CF_GoalSOCDate);
            })
            ->editColumn('intake_followup', function ($row) {
                return H::getFormatedDate($row->intake_followup);
            })
            ->addColumn('Action', function ($row) {
                $btn = '';
                $btn .= '<a
                    href="javascript:void(0)"
                    onclick="getIntakeStaffTransfersOutcome(\'' . $row->id . '\', \'intake_schedule\', \'' . $row->type . '\')"
                    title="Document Call">
                        <i class="fa fa-calendar" aria-hidden="true"></i>
                    </a>';
                if(LD::checkFeatureFlag('remove-union-intake-dashboard')) {
					$btn .= '<a
						href="javascript:void(0)"
						onclick="getClientDetails(\'' . $row->pid . '\', \'clientintake\', \'' . $row->LOBID . '\', \'intake_dashboard\')"
						title="View Client Detail">
							<i class="fa fa-user" aria-hidden="true"></i>
						</a>';
				} else {
					$btn .= '<a
						href="javascript:void(0)"
						onclick="getClientDetails(\'' . $row->id . '\', \'clientintake\', \'' . $row->LOBID . '\', \'intake_dashboard\')"
						title="View Client Detail">
							<i class="fa fa-user" aria-hidden="true"></i>
						</a>';
				}
                return $btn;
            });
        if ($request->has('order')) {
            $columnIndex = $request->get('order')[0]['column'];
            $columnName = $request->get('columns')[$columnIndex]['data'];
            $sortDirection = $request->get('order')[0]['dir'];
            if ($columnName == 'IntakeDate') {
                $data->orderByRaw("ISNULL(IntakeDate),
                                    IntakeDate = '',
                                    IntakeDate = '0000-00-00 00:00:00',
                                    IntakeDate $sortDirection");
            } else if ($columnName == 'CF_GoalSOCDate') {
                $data->orderByRaw("ISNULL(CF_GoalSOCDate),
                                    CF_GoalSOCDate = '',
                                    CF_GoalSOCDate = '0000-00-00 00:00:00',
                                    CF_GoalSOCDate $sortDirection");
            } else if ($columnName == 'intake_followup') {
                $data->orderByRaw("ISNULL(intake_followup),
                                    intake_followup = '',
                                    intake_followup = '0000-00-00 00:00:00',
                                    intake_followup $sortDirection");
            } else {
                $data->orderByRaw("$columnName $sortDirection");
            }
        }
        return $dataTable->rawColumns(['notes', 'Action'])
        ->make(true);
    }

    protected function getDataTablePendingSOC($data, $request)
    {
        $dataTable = Datatables::query($data)->setRowAttr([
            'class' => function($row) {
                if(LD::checkFeatureFlag('red-highlights-intake-all-buckets')){
                    $followUpDate = isset($row->intake_followup) ? strtotime($row->intake_followup) : null;
                    $goalSocDate = isset($row->CF_GoalSOCDate) ? strtotime($row->CF_GoalSOCDate) : null;
                    $now = strtotime('now');
                    if(!in_array($row->LOBID, ['1003', '1004', '1006']) && (($followUpDate < $now) || ($goalSocDate < $now))){
                            return 'redexception';
                    }
                    return '';
                }else{
                    return '';
                }

            },
        ])->editColumn('notes', function ($data) {
                if (!empty ($data->notes)) {
                    [$created_at, $notes, $created_by] = explode("#", $data->notes);
                    $notes = str_replace(['"',"<br>"], ["'",', '], (preg_replace('/^\s+|\s+$|\s+(?=\s)/', '', $notes)));
                    $userName = H::getUserNameById($created_by);
                    $date = date('m/d', strtotime($created_at));
                    $note = $date . ' ' . $userName . ' commented: ' . $notes;
                    $str = '';
                    if (strlen($note) > 35) {
                        $str = substr($note, 0, 35) . '...';
                    } else {
                        $str = $note;
                    }
                    return "<span data-toggle='tooltip' title=\"" .
                        mb_convert_encoding($note, 'UTF-8', 'UTF-8') . "\">" .
                        mb_convert_encoding($str, 'UTF-8', 'UTF-8') .
                        '</span>';
                }
                return "-";
            })
            ->editColumn('intake_readytostart', function ($data) {
                $status = '-';
                if ($data->intake_readytostart === 0) {
                    $status = 'No';
                } else if ($data->intake_readytostart === 1) {
                    $status = 'Yes';
                }
                return $status;
            })
            ->editColumn('admission_visit_date', function ($row) {
                return H::getFormatedDate($row->admission_visit_date);
            })
            ->editColumn('CF_GoalSOCDate', function ($row) {
                return H::getFormatedDate($row->CF_GoalSOCDate);
            })
            ->editColumn('intake_followup', function ($row) {
                return H::getFormatedDate($row->intake_followup);
            })
            ->addColumn('Action', function ($row) {
                $btn = '';
                $btn .= '<a
                    href="javascript:void(0)"
                    onclick="getIntakeStaffTransfersOutcome(\'' . $row->id . '\', \'intake_pending_soc\', \'' . $row->type . '\')"
                    title="Document Call">
                        <i class="fa fa-calendar" aria-hidden="true"></i>
                    </a>';
                if(LD::checkFeatureFlag('remove-union-intake-dashboard')) {
					$btn .= '<a
						href="javascript:void(0)"
						onclick="getClientDetails(\'' . $row->pid . '\', \'clientintake\', \'' . $row->LOBID . '\', \'intake_dashboard\')"
						title="View Client Detail">
							<i class="fa fa-user" aria-hidden="true"></i>
						</a>';
				} else {
					$btn .= '<a
						href="javascript:void(0)"
						onclick="getClientDetails(\'' . $row->id . '\', \'clientintake\', \'' . $row->LOBID . '\', \'intake_dashboard\')"
						title="View Client Detail">
							<i class="fa fa-user" aria-hidden="true"></i>
						</a>';
				}
                return $btn;
            });
            if ($request->has('order')) {
                $columnIndex = $request->get('order')[0]['column'];
                $columnName = $request->get('columns')[$columnIndex]['data'];
                $sortDirection = $request->get('order')[0]['dir'];
                if ($columnName == 'IntakeDate') {
                    $data->orderByRaw("ISNULL((select admission_visit_date from intake
                                            where intake.aoc_patient_id = mssql_patients.id order by id desc limit 1
                                    )),
                                    (
                                        select admission_visit_date from intake
                                        where intake.aoc_patient_id = mssql_patients.id order by id desc limit 1
                                    ) = '',
                                    (
                                        select admission_visit_date from intake
                                        where intake.aoc_patient_id = mssql_patients.id order by id desc limit 1
                                    ) = '0000-00-00 00:00:00',
                                    (
                                        select admission_visit_date from intake
                                        where intake.aoc_patient_id = mssql_patients.id order by id desc limit 1
                                    ) $sortDirection");
                } else if ($columnName == 'CF_GoalSOCDate') {
                    $data->orderByRaw("ISNULL(CF_GoalSOCDate),
                                        CF_GoalSOCDate = '',
                                        CF_GoalSOCDate = '0000-00-00 00:00:00',
                                        CF_GoalSOCDate $sortDirection");
                } else if ($columnName == 'intake_followup') {
                    $data->orderByRaw("ISNULL(intake_followup),
                                        intake_followup = '',
                                        intake_followup = '0000-00-00 00:00:00',
                                        intake_followup $sortDirection");
                } else {
                    $data->orderByRaw("$columnName $sortDirection");
                }
            }
            return $dataTable->rawColumns(['notes', 'Action'])
                    ->make(true);
    }

    protected function getDataTableClientStarts($data, $request)
    {
        $dataTable = Datatables::query($data)->setRowAttr([
            'class' => function($row) {
                if(LD::checkFeatureFlag('red-highlights-intake-all-buckets')){
                    $goalSocDate = isset($row->CF_GoalSOCDate) ? strtotime($row->CF_GoalSOCDate) : null;
                    $now = strtotime('now');
                    if(!in_array($row->LOBID, ['1003', '1004', '1006'])){
                        if ($goalSocDate && $goalSocDate < $now) {
                            return 'redexception';
                        }
                    }
                    return '';
                }else{
                    return '';
                }

            },
        ])->editColumn('notes', function ($data) {
                if (!empty ($data->notes)) {
                    [$created_at, $notes, $created_by] = explode("#", $data->notes);
                    $notes = str_replace(['"',"<br>"], ["'",', '], (preg_replace('/^\s+|\s+$|\s+(?=\s)/', '', $notes)));
                    $userName = H::getUserNameById($created_by);
                    $date = date('m/d', strtotime($created_at));
                    $note = $date . ' ' . $userName . ' commented: ' . $notes;
                    $str = '';
                    if (strlen($note) > 35) {
                        $str = substr($note, 0, 35) . '...';
                    } else {
                        $str = $note;
                    }
                    return "<span data-toggle='tooltip' title=\"" .
                        mb_convert_encoding($note, 'UTF-8', 'UTF-8') . "\">" .
                        mb_convert_encoding($str, 'UTF-8', 'UTF-8') .
                        '</span>';
                }
                return "-";
            })
            ->editColumn('CF_GoalSOCDate', function ($row) {
                return H::getFormatedDate($row->CF_GoalSOCDate);
            })
            ->addColumn('Action', function ($row) {
                $btn = '';
                $btn .= '<a
                    href="javascript:void(0)"
                    onclick="getIntakeStaffTransfersOutcome(\'' . $row->id . '\', \'intake_client_starts\', \'' . $row->type . '\')"
                    title="Document Call">
                        <i class="fa fa-calendar" aria-hidden="true"></i>
                    </a>';
                if(LD::checkFeatureFlag('remove-union-intake-dashboard')) {
					$btn .= '<a
						href="javascript:void(0)"
						onclick="getClientDetails(\'' . $row->pid . '\', \'clientintake\')"
						title="View Client Detail">
							<i class="fa fa-user" aria-hidden="true"></i>
						</a>';
				} else {
					$btn .= '<a
						href="javascript:void(0)"
						onclick="getClientDetails(\'' . $row->id . '\', \'clientintake\')"
						title="View Client Detail">
							<i class="fa fa-user" aria-hidden="true"></i>
						</a>';
				}
                return $btn;
            });
            if ($request->has('order')) {
                $columnIndex = $request->get('order')[0]['column'];
                $columnName = $request->get('columns')[$columnIndex]['data'];
                $sortDirection = $request->get('order')[0]['dir'];
                if ($columnName == 'CF_GoalSOCDate') {
                    $data->orderByRaw("ISNULL(CF_GoalSOCDate),
                                        CF_GoalSOCDate = '',
                                        CF_GoalSOCDate = '0000-00-00 00:00:00',
                                        CF_GoalSOCDate $sortDirection");
                } else {
                    $data->orderByRaw("$columnName $sortDirection");
                }
            }
            return $dataTable->rawColumns(['notes', 'Action'])
                    ->make(true);
    }

    public function getIntakeStaffTransfers(Request $request)
    {
        $data = H::getHelper("IntakeCoordinatorStaffTransfers")
                ->getIntakeStaffTransfers($request);
        if ($request->input("type") == 'intake_need_to_schedule_ipea') {
            return $this->getDataTableNeedToScheduleIpea($data);
        }

        if ($request->input("type") == 'intake_todays_ipea' ) {
            return $this->getDataTableTodaysIpea($data);
        }
        if($request->input("type") == 'intake_pending_candidate_starts') {
            return $this->getDataTablePendingCandidateStarts($data);
        }
        if($request->input("type") == 'intake_candidate_starts') {
            return $this->getDataTableCandidateStarts($data);
        }

        if ($request->input("type") == 'intake_prospective_clients') {
            return $this->getDataTableProspectiveClients($data, $request);
        }

        if ($request->input("type") == 'intake_schedule') {
            return $this->getDataTableIntakeSchedule($data, $request);
        }

        if ($request->input("type") == 'intake_pending_soc') {
            return $this->getDataTablePendingSOC($data, $request);
        }

        if ($request->input("type") == 'intake_client_starts') {
            return $this->getDataTableClientStarts($data, $request);
        }

        if ($request->input("type") == 'intake_pending_non_admit') {
            return $this->getDataTablePendingNonAdmit($data, $request);
        }

    }

    public function reloadCountIntakeStaffTransfers(Request $request)
    {
        return json_encode([
            "counter" => H::getHelper('IntakeCoordinatorStaffTransfers')->getIntakeStaffTransfers($request, 1),
            "type" => $request->input('type')
        ]);
    }

    public function getIntakeStaffTransfersOutcome(Request $request)
    {
        $clientId = $request->input('id');
        $bucket = $request->input('bucket');
        $type = $request->input('type');
        $intakeId = 0;
        if ($type == 'intake') {
            $intakeId = $clientId;
            $intake = Intake::find($clientId);
            $clientId = $intake->aoc_patient_id;
        }
        $clientDetails = MssqlPatients::select(
            "mssql_patients.*",
            "mssql_facilities.FacilityName",
            DB::raw("CONCAT(FLOOR((TIMESTAMPDIFF(MONTH, mssql_patients.Patient_DOB, CURDATE()) / 12)), ' Years ') AS Patient_age")
        )
        ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
        ->where("mssql_patients.id", $clientId)->first();

        if($clientDetails->PatientName == ''){
            $clientDetails->PatientName = $clientDetails->Patient_FirstName.' '.$clientDetails->Patient_LastName;
        }
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
        $clientDetails->setAttribute('is_workForce', $is_workForce);
        $IntakeData = '';
        if($bucket == 'intake_schedule'){
            $IntakeData = Intake::where('aoc_patient_id', $clientId)->first();
        }

        return view('intake.intakeStaffOutcomeForm', [
            "clientDetails" => $clientDetails,
            "bucket" => $bucket,
            "IntakeData" => $IntakeData,
            "type" => $type,
            "intakeId" => $intakeId
        ]);
    }

    public function list(Request $request)
    {
        $columns = [
            'patient_name' => [
                'data' => 'patient_name',
                'name' => 'patient_name',
                'label' => 'Client'
            ],
            'FacilityName' => [
                'data' => 'FacilityName',
                'name' => 'FacilityName',
                'label' => 'Branch'
            ],
            'ReferralDate' => [
                "data" => 'ReferralDate',
                "name" => 'ReferralDate',
                "searchable" => false,
                'label' => 'Referral Date',
                'type' => 'date'
            ],
            'IntakeStatus' => [
                'data' => 'IntakeStatus',
                'name' => 'IntakeStatus',
                'label' => 'Status'
            ],
            'intake_pending_reason' => [
                'data' => 'intake_pending_reason',
                'name' => 'intake_pending_reason',
                'label' => 'Pending Reason'
            ],
            'LOBName' => [
                'data' => 'LOBName',
                'name' => 'LOBName',
                'label' => 'LOB'
            ],
            'CF_Specialized' => [
                'data' => 'CF_Specialized',
                'name' => 'CF_Specialized',
                'label' => 'Specialized'
            ],
            'CF_TerminalPayer' => [
                'data' => 'CF_TerminalPayer',
                'name' => 'CF_TerminalPayer',
                'label' => 'Terminal Payer'
            ],
            'AuthorizedWeeklyHours' => [
                'data' => 'AuthorizedWeeklyHours',
                'name' => 'AuthorizedWeeklyHours',
                'label' => 'Authorized Weekly Hours'
            ],
            'AuthorizedWeeklyVisits' => [
                'data' => 'AuthorizedWeeklyVisits',
                'name' => 'AuthorizedWeeklyVisits',
                'label' => 'Authorized Weekly Visits'
            ],
            'totalhours' => [
                'data' => 'totalhours',
                'name' => 'totalhours',
                'label' => 'NTH Hours'
            ],
            'INTAKE_ACCEPTEDDATE' => [
                "data" => 'INTAKE_ACCEPTEDDATE',
                "name" => 'INTAKE_ACCEPTEDDATE',
                "searchable" => false,
                'label' => 'Completed Intake Date',
                'type' => 'date'
            ],
            'CF_GoalSOCDate' => [
                "data" => 'CF_GoalSOCDate',
                "name" => 'CF_GoalSOCDate',
                "searchable" => false,
                'label' => 'Goal SOC Date',
                'type' => 'date'
            ],
            'requested_soc_date' => [
                "data" => 'requested_soc_date',
                "name" => 'requested_soc_date',
                "searchable" => false,
                'label' => 'Actual SOC Date',
                'type' => 'date'
            ],
            'NonAdmitDate' => [
                "data" => 'NonAdmitDate',
                "name" => 'NonAdmitDate',
                "searchable" => false,
                'label' => 'Non Admit Date',
                'type' => 'date'
            ],
            'BD_Rep' => [
                'data' => 'BD_Rep',
                'name' => 'BD_Rep',
                'label' => 'BD Rep'
            ],
            'intakeCordinator' => [
                'data' => 'intakeCordinator',
                'name' => 'intakeCordinator',
                'label' => 'Intake Coordinator'
            ],
            'Action' => [
                'data' => 'Action',
                'name' => 'Action',
                'label' => 'Action'
            ]
        ];
        if ($request->ajax()) {
            $request->merge(["type" => "showall"]);
            $data = H::getHelper("IntakeCoordinatorStaffTransfers")
                ->getIntakeStaffTransfers($request);
            $dataTable = Datatables::query($data)
            ->editColumn('ReferralDate', function ($row) {
                return H::getFormatedDate($row->ReferralDate);
            })
            ->editColumn('CF_GoalSOCDate', function ($row) {
                return H::getFormatedDate($row->CF_GoalSOCDate);
            })
            ->editColumn('INTAKE_ACCEPTEDDATE', function ($row) {
                return H::getFormatedDate($row->INTAKE_ACCEPTEDDATE);
            })
            ->editColumn('requested_soc_date', function ($row) {
                return H::getFormatedDate($row->requested_soc_date);
            })
            ->editColumn('NonAdmitDate', function ($row) {
                return H::getFormatedDate($row->NonAdmitDate);
            })
            ->addColumn('Action', function ($row) {
                $btn = '';

                if (!empty($row->bucket) && !in_array($row->bucket, [self::INTAKE_NON_ADMIT, self::INTAKE_ADMITTED], true)) {
                    $btn .= '<a
                        href="javascript:void(0)"
                        onclick="getIntakeStaffTransfersOutcome(\'' . $row->id . '\', \''.$row->bucket.'\', \'' . $row->type . '\')"
                        title="Document Call">
                            <i class="fa fa-calendar" aria-hidden="true"></i>
                        </a>';
                }
                if(LD::checkFeatureFlag('remove-union-intake-dashboard')) {
					$btn .= '<a
						href="javascript:void(0)"
						onclick="getClientDetails(\'' . $row->pid . '\', \'clientintake\')"
						title="View Client Detail">
							<i class="fa fa-user" aria-hidden="true"></i>
						</a>';
				} else {
					$btn .= '<a
						href="javascript:void(0)"
						onclick="getClientDetails(\'' . $row->id . '\', \'clientintake\')"
						title="View Client Detail">
							<i class="fa fa-user" aria-hidden="true"></i>
						</a>';
				}
                $btn .= '<a
                    href="javascript:void(0)"
                    onclick="openNotes('.$row->id.',\'client\',\'Client Notes Added\')"
                    title="Client Notes Added">
                        <i class="fa fa-sticky-note-o" aria-hidden="true"></i>
                    </a>';
                return $btn;
            });
            if ($request->has('order')) {
                $columnIndex = $request->get('order')[0]['column'];
                $columnName = $request->get('columns')[$columnIndex]['data'];
                $sortDirection = $request->get('order')[0]['dir'];
                if ($columnName == 'ReferralDate') {
                    $data->orderByRaw("ISNULL(ReferralDate),
                                        ReferralDate = '',
                                        ReferralDate = '0000-00-00 00:00:00',
                                        ReferralDate $sortDirection");
                } elseif ($columnName == 'CF_GoalSOCDate') {
                    $data->orderByRaw("ISNULL(CF_GoalSOCDate),
                                        CF_GoalSOCDate = '',
                                        CF_GoalSOCDate = '0000-00-00 00:00:00',
                                        CF_GoalSOCDate $sortDirection");
                } elseif ($columnName == 'INTAKE_ACCEPTEDDATE') {
                    $data->orderByRaw("ISNULL(INTAKE_ACCEPTEDDATE),
                                        INTAKE_ACCEPTEDDATE = '',
                                        INTAKE_ACCEPTEDDATE = '0000-00-00 00:00:00',
                                        INTAKE_ACCEPTEDDATE $sortDirection");
                } elseif ($columnName == 'requested_soc_date') {
                    $data->orderByRaw("ISNULL(requested_soc_date),
                                        requested_soc_date = '',
                                        requested_soc_date = '0000-00-00 00:00:00',
                                        requested_soc_date $sortDirection");
                } elseif ($columnName == 'NonAdmitDate') {
                    $data->orderByRaw("ISNULL(NonAdmitDate),
                                        NonAdmitDate = '',
                                        NonAdmitDate = '0000-00-00 00:00:00',
                                        NonAdmitDate $sortDirection");
                } else {
                    $data->orderByRaw("$columnName $sortDirection");
                }
            }
            return $dataTable->rawColumns(['notes', 'Action'])
                    ->make(true);
        }
        return view('intake.grid', ['columns' => $columns]);
    }

    public function export(Request $request)
    {
        if(LD::checkFeatureFlag('add-client-address-intake-export')) {
            $columns = [
                ['name' => 'patient_name', 'label' => 'Client'],
                ['name' => 'PatientAccount', 'label' => 'Client ID'],
                ['name' => 'FacilityName', 'label' => 'Branch'],
                ['name' => 'Address1', 'label' => 'Address1'],
                ['name' => 'Address2', 'label' => 'Address2'],
                ['name' => 'City', 'label' => 'City'],
                ['name' => 'State', 'label' => 'State'],
                ['name' => 'Zip', 'label' => 'Zip'],
                ['name' => 'ReferralDate', 'label' => 'Referral Date'],
                ['name' => 'IntakeStatus', 'label' => 'Status'],
                ['name' => 'LOBName', 'label' => 'LOB'],
                ['name' => 'CF_Specialized', 'label' => 'Specialized'],
                ['name' => 'Payer', 'label' => 'Payer'],
                ['name' => 'Secondary_Payer', 'label' => 'Secondary Payer'],
                ['name' => 'Tertiary_Payer', 'label' => 'Tertiary Payer'],
                ['name' => 'AuthorizedWeeklyHours', 'label' => 'Authorized Weekly Hours'],
                ['name' => 'AuthorizedWeeklyVisits', 'label' => 'Authorized Weekly Visits'],
                ['name' => 'totalhours', 'label' => 'NTH Hours'],
                ['name' => 'INTAKE_ACCEPTEDDATE', 'label' => 'Completed Intake Date'],
                ['name' => 'CF_GoalSOCDate', 'label' => 'Goal SOC Date'],
                ['name' => 'requested_soc_date', 'label' => 'Actual SOC Date'],
                ['name' => 'Referral_Source', 'label' => 'Referral Source'],
                ['name' => 'Referral_Type', 'label' => 'Referral Type'],
                ['name' => 'NonAdmitDate', 'label' => 'Non Admit Date'],
                ['name' => 'intake_nonadmit_reason', 'label' => 'Non Admit Reason'],
                ['name' => 'intake_controllable', 'label' => 'Controllable/Uncontrollable'],
                ['name' => 'BD_Rep', 'label' => 'BD Rep'],
                ['name' => 'intakeCordinator', 'label' => 'Intake Coordinator']
            ];
        } else {
            $columns = [
                ['name' => 'patient_name', 'label' => 'Client'],
                ['name' => 'FacilityName', 'label' => 'Branch'],
                ['name' => 'ReferralDate', 'label' => 'Referral Date'],
                ['name' => 'IntakeStatus', 'label' => 'Status'],
                ['name' => 'LOBName', 'label' => 'LOB'],
                ['name' => 'CF_Specialized', 'label' => 'Specialized'],
                ['name' => 'Payer', 'label' => 'Payer'],
                ['name' => 'Secondary_Payer', 'label' => 'Secondary Payer'],
                ['name' => 'Tertiary_Payer', 'label' => 'Tertiary Payer'],
                ['name' => 'AuthorizedWeeklyHours', 'label' => 'Authorized Weekly Hours'],
                ['name' => 'AuthorizedWeeklyVisits', 'label' => 'Authorized Weekly Visits'],
                ['name' => 'totalhours', 'label' => 'NTH Hours'],
                ['name' => 'INTAKE_ACCEPTEDDATE', 'label' => 'Completed Intake Date'],
                ['name' => 'CF_GoalSOCDate', 'label' => 'Goal SOC Date'],
                ['name' => 'requested_soc_date', 'label' => 'Actual SOC Date'],
                ['name' => 'Referral_Source', 'label' => 'Referral Source'],
                ['name' => 'Referral_Type', 'label' => 'Referral Type'],
                ['name' => 'NonAdmitDate', 'label' => 'Non Admit Date'],
                ['name' => 'intake_nonadmit_reason', 'label' => 'Non Admit Reason'],
                ['name' => 'intake_controllable', 'label' => 'Controllable/Uncontrollable'],
                ['name' => 'BD_Rep', 'label' => 'BD Rep'],
                ['name' => 'intakeCordinator', 'label' => 'Intake Coordinator']
            ];
        }

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=intake.csv",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $request->merge(["type" => "showall"]);
        $data = H::getHelper("IntakeCoordinatorStaffTransfers")
                ->getIntakeStaffTransfers($request)->get();

        $callback = function () use ($data, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_column($columns, 'label'));

            foreach ($data as $tableData) {
                $csvData = [];
                foreach (array_column($columns, 'name') as $fields => $val) {
                    $csvData[] = $tableData->{$val};
                }
                fputcsv($file, $csvData);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    private function saveScheduleIntakeOutcome($requestData)
    {
        if (!empty($requestData['intake_id'])) {
            $previousIntake = Intake::where("id", $requestData['intake_id'])->first();
        } else {
            $previousIntake = Intake::where("aoc_patient_id", $requestData['aoc_patient_id'])->orderBy('id', 'desc')->first();
        }
        H::ExcelLog('Previous Intake ' . print_r($previousIntake, true), 'intake_outcome.log');
        $eventMatchData = [
            'admission_visit_date' => $requestData['intakeStaffForm_admissionVisitDate'],
            'start_time' => $requestData['intakeStaffForm_start_time'],
            'end_time' => $requestData['intakeStaffForm_end_time'],
            'intake_nurse_id' => $requestData['intakeStaffForm_intakeNurse']
        ];
        $eventModificationType = H::getHelper("MicrosoftGraph")->isEventRescheduleOrReassign($previousIntake, $eventMatchData);
        H::ExcelLog('Event Modification Type ' . $eventModificationType, 'intake_outcome.log');
        $intakeData = [
            'aoc_patient_id' => $requestData['aoc_patient_id'],
            'aoc_nthaccounts_id' => '',
            'referral_type' => '',
            'hospital' => '',
            'hospital_discharge_date' => '',
            'anticipated_hours' => '',
            'goal_soc_date' => '',
            'admission_visit_scheduled' => 1,
            'admission_visit_date' => (isset($requestData['intakeStaffForm_admissionVisitDate'])
            && $requestData['intakeStaffForm_admissionVisitDate'] != NULL) ? date('Y-m-d H:i:s',strtotime($requestData['intakeStaffForm_admissionVisitDate'])) : '',
            'add_to_nth' => 0,
            'intake_nurse_id'=> isset($requestData['intakeStaffForm_intakeNurse']) ? $requestData['intakeStaffForm_intakeNurse'] : '',
            'kantime_url'=> '',
            'notes'=> '',
            'start_time' => date('H:i', strtotime($requestData['intakeStaffForm_start_time'])),
            'end_time' => date('H:i', strtotime($requestData['intakeStaffForm_end_time'])),
            'subject' => $requestData['intakeStaffForm_subject'],
            'location' => $requestData['intakeStaffForm_location'],
            'description' => $requestData['intakeStaffForm_description'],
        ];

        if (!empty($previousIntake)) {
            $intakeData['updated_by'] = Auth::user()->id;
            $intakeData['updated_at'] = date('Y-m-d H:i:s');
            Intake::where('id', $previousIntake->id)->update($intakeData);
            $intakeId = $previousIntake->id;
            H::ExcelLog('Intake Updated with Data ' . print_r($intakeData, true), 'intake_outcome.log');
        } else {
            $intakeData['created_by'] = Auth::user()->id;
            $intakeData['created_at'] = date('Y-m-d H:i:s');
            $intakeId = Intake::insertGetId($intakeData);
            H::ExcelLog('Intake Inserted with Data ' . print_r($intakeData, true), 'intake_outcome.log');
        }

        $intakeNurseID = $requestData['intakeStaffForm_intakeNurse'];

        $intakeNurseUser = H::getUserById($intakeNurseID);
        if (strstr($intakeNurseUser->user_timezone,"#")) {
            $timezone = explode("#",$intakeNurseUser->user_timezone)[0];
        } else {
            $timezone =$intakeNurseUser->user_timezone;
        }

        $eventStartDate = date('Y-m-d\TH:i:s', strtotime($requestData['intakeStaffForm_admissionVisitDate'].' '.$requestData['intakeStaffForm_start_time']));
        $eventEndDate = date('Y-m-d\TH:i:s', strtotime($requestData['intakeStaffForm_admissionVisitDate'].' '.$requestData['intakeStaffForm_end_time']));

        $eventData = [
            'subject' => $requestData['intakeStaffForm_subject'],
            'description' => $requestData['intakeStaffForm_description'],
            'location' => $requestData['intakeStaffForm_location'],
            'event_startdate' => $eventStartDate,
            'event_enddate' => $eventEndDate,
            'timezone' => $timezone,
            'attendee' => $intakeNurseUser->email,
            'attendee_name' => $intakeNurseUser->name
        ];
        H::ExcelLog('Event Data ' . print_r($eventData, true), 'intake_outcome.log');
        $aocEventData = [
            'user_id' => $intakeNurseID,
            'entity_id' => $intakeId,
            'entity_type' => 'intake',
            'event_status' => 'open'
        ];
        H::ExcelLog('AOC Event Data ' . print_r($aocEventData, true), 'intake_outcome.log');
        $eventId = -1;
        if ($event = H::getHelper("MicrosoftGraphBase")->isEventExist($intakeId, "intake")) {
            H::ExcelLog('Previous Intake Event Exists', 'intake_outcome.log');
            H::ExcelLog('Previous Intake Event' . print_r($event, true), 'intake_outcome.log');
            if ($eventModificationType == 'reschedule') {
                H::ExcelLog('Reschedule Event', 'intake_outcome.log');
                if (H::getHelper("MicrosoftGraph")->getUserByEmail(Auth::user()->email)) {
                    $userEmail = H::getHelper("MicrosoftGraph")->getEventUserEmail($event->created_by);
                    H::getHelper("MicrosoftGraph")
                        ->getUserByEmail($userEmail)
                        ->cancelEvent($event->event_id);

                    $eventId = H::getHelper("MicrosoftGraph")
                        ->getUserByEmail(Auth::user()->email)
                        ->createEvent($eventData, $aocEventData);

                    $notesData = [
                        'entity' => 'intake',
                        'entity_id' => $intakeId,
                        'notes' => 'Event has been Rescheduled to ' . H::getFormatedDateTime($eventStartDate) . ' - ' . H::getFormatedDateTime($eventEndDate)
                    ];
                    H::ExcelLog('Notes Created with data ' . print_r($notesData, true), 'intake_outcome.log');
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                }


            } else if ($eventModificationType == 'reassign') {
                H::ExcelLog('Reassign Event', 'intake_outcome.log');
                if (H::getHelper("MicrosoftGraph")->getUserByEmail(Auth::user()->email)) {
                    $userEmail = H::getHelper("MicrosoftGraph")->getEventUserEmail($event->created_by);

                    H::getHelper("MicrosoftGraph")
                        ->getUserByEmail($userEmail)
                        ->deleteEvent($event->event_id);

                    $eventId = H::getHelper("MicrosoftGraph")
                        ->getUserByEmail(Auth::user()->email)
                        ->createEvent($eventData, $aocEventData);

                    $notesData = [
                        'entity' => 'intake',
                        'entity_id' => $intakeId,
                        'notes' => 'Event has been cancelled with  ' . H::getUserNameById($event->user_id) . ' By ' . Auth::user()->name
                    ];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                    H::ExcelLog('Notes Created with data ' . print_r($notesData, true), 'intake_outcome.log');
                    $notesData = [
                        'entity' => 'intake',
                        'entity_id' => $intakeId,
                        'notes' => 'Event invite has been send to  ' . H::getUserNameById($intakeNurseID) . ' By ' . Auth::user()->name
                    ];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                    H::ExcelLog('Notes Created with data ' . print_r($notesData, true), 'intake_outcome.log');
                }
            }

        } else {
            if (H::getHelper("MicrosoftGraph")->getUserByEmail(Auth::user()->email)) {
                H::ExcelLog('Reassign Created', 'intake_outcome.log');
                $eventId = H::getHelper("MicrosoftGraph")
                    ->getUserByEmail(Auth::user()->email)
                    ->createEvent($eventData, $aocEventData);
            }
        }

        return $eventId;
    }

    protected function sendIntakeNotificationToCareTeam($careTeams, $notificationText, $aoc_patient_id)
    {
        foreach($careTeams as $careTeam) {
            $data = [];
            $data['user_id'] = $careTeam['id'];
            $data['title'] = $notificationText;
            $data['type'] = 'intake';
            $data['related_item'] = $aoc_patient_id;
            $data['notes'] = '';
            Notifications::addNotification($data);
        }
    }

    private function saveProspectiveClientsOutcome($requestData)
    {

        $patientData = [];
        $notesData = [];
        $patientData['intake_outcome'] = $requestData['outcome'];
        if ($requestData['outcome'] == 'Schedule Intake') {
            $eventId = $this->saveScheduleIntakeOutcome($requestData);
            H::ExcelLog('Event Id ' . $eventId, 'intake_outcome.log');

            $patientData['IntakeStatus'] = 'Scheduled';
            $intakeNurseID = $requestData['intakeStaffForm_intakeNurse'];
            $intakeNurseUser = H::getUserById($intakeNurseID);
            $notesData = [
                'entity' => 'client',
                'entity_id' => $requestData['aoc_patient_id'],
                'notes' => Auth::user()->name . ' scheduled intake with ' . $intakeNurseUser->name .
                        ' for ' . H::getFormatedDate($requestData['intakeStaffForm_admissionVisitDate']) . ' from ' .
                        $requestData['intakeStaffForm_start_time'] . '-' . $requestData['intakeStaffForm_end_time']
            ];

            $patientDetail = MssqlPatients::find($requestData['aoc_patient_id']);
            $careTeams = ConstantHelper::getCareTeam($requestData['aoc_patient_id']);
            $notificationText = 'Intake scheduled for ' . $patientDetail->Patient_FirstName .
            ' ' . $patientDetail->Patient_LastName . ' on ' .
            H::getFormatedDate($requestData['intakeStaffForm_admissionVisitDate']) .
            ' at '.$requestData['intakeStaffForm_start_time'] . ' - ' . $requestData['intakeStaffForm_end_time'] .
            ' with ' . $intakeNurseUser->name . ' .';
            $this->sendIntakeNotificationToCareTeam($careTeams, $notificationText, $requestData['aoc_patient_id']);
        }
        else if ($requestData['outcome'] == 'Provide an update') {
            $patientData['intake_comment'] = $requestData['notes_text'];
            $patientData['intake_followup'] = (!empty($requestData['intakeStaffForm_followupdate'])) ?
            date('Y-m-d H:i:s',strtotime($requestData['intakeStaffForm_followupdate'])) : '';
            $patientData['intake_contact'] = $requestData['intakeStaffForm_contact'];
            $notesData = [
                'entity' => 'client',
                'entity_id' => $requestData['aoc_patient_id'],
                'notes' => 'Intake was updated by ' . Auth::user()->name . '<br>' .
                            'Comments : ' . $requestData['notes_text'] . '<br>' .
                            'Follow Up Date : ' . $requestData['intakeStaffForm_followupdate'] . '<br>' .
                            'Contact : ' . $requestData['intakeStaffForm_contact']
            ];

            if(isset($requestData['mention'])) {
                if(LD::checkFeatureFlag('careteam-tagging-to-notes')) {
                    if(strstr($requestData['mention'],'-1')) {
                        $careTeams = H::getHelper("ConstantHelper")->getCareTeam($requestData['aoc_patient_id']);
                        $careTeamIds = [];
                        foreach($careTeams as $careteam) {
                            $careTeamIds[] = $careteam['id'];
                        }
                        if(!empty($careTeamIds)) {
                            $requestData['mention'] = str_replace("-1",implode(",",$careTeamIds),$requestData['mention']);
                        }
                    }
                }


                $mentions = array_unique(explode(",",$requestData['mention']));

                foreach($mentions as $mention) {
                    $data = [];
                    $data['user_id'] = $mention;
                    $data['title'] = 'New update provided on '.H::getPatientNameById($requestData['aoc_patient_id']);
                    $data['type'] = 'intake';
                    $data['related_item'] = $requestData['aoc_patient_id'];
                    $data['notes'] = $requestData['notes_text'];
                    Notifications::addNotification($data);
                }
            }

        } else if ($requestData['outcome'] == 'Update Pending Reason') {
            $patientData['intake_pending_reason'] = $requestData['intakeStaffForm_pending_reason'];
            if ($requestData['type'] == 'intake') {
                $patientDetail = Intake::find($requestData['intake_id']);
            } else {
                $patientDetail = MssqlPatients::find($requestData['aoc_patient_id']);
            }
            $comment = '';
            if(!empty($requestData['intakeStaffForm_pending_reason_comment'])){
                $comment = ' with note '.$requestData['intakeStaffForm_pending_reason_comment'];
            }
            $notesData = [
                'entity' => 'client',
                'entity_id' => $requestData['aoc_patient_id'],
                'notes' => Auth::user()->name . ' updated pending reason from ' . $patientDetail->intake_pending_reason . ' to ' .
                            $requestData['intakeStaffForm_pending_reason'] . ''.$comment.'.'
            ];
        } else if ($requestData['outcome'] == 'Non Admit') {
            $this->cancellMSEvent($requestData['aoc_patient_id']);
            if(LD::checkFeatureFlag('non-admit-updating-meeting-nurses')) {
                $this->setMeetingNurses($requestData['aoc_patient_id']);
            }


            $patientData['admission_visit_scheduled'] = 0;
            $patientData['intake_nonadmit_reason'] = $requestData['intakeStaffForm_nonadmitreasons'];
            $patientData['intake_controllable'] = $requestData['intakeStaffForm_contrbluncontrbl'];
            $patientData['intake_lead_exclude'] = isset($requestData['intakeStaffForm_excludefromreporting']) ?
            $requestData['intakeStaffForm_excludefromreporting'] : 0;
            $patientData['intake_comment'] = $requestData['intakeStaffForm_comments'];
            $patientData['IntakeStatus'] = 'Pending Non Admit';
            $notesData = [
                'entity' => 'client',
                'entity_id' => $requestData['aoc_patient_id'],
                'notes' => $this->intakeService->buildNonAdmitActivityNote(
                    Auth::user()->name,
                    $requestData
                )
            ];
        }
        $patientData['intake_outcome_by'] = Auth::user()->id;
        $patientData['intake_outcome_at'] = date('Y-m-d H:i:s');
        if ($requestData['type'] == 'intake') {
            Intake::where('id', $requestData['intake_id'])->update($patientData);
        } else {
            MssqlPatients::where('id', $requestData['aoc_patient_id'])->update($patientData);
        }
        H::getHelper('ActivityNotes')->setActivityNotes($notesData);
        return json_encode(["status" => 1, "bucket" => $requestData['bucket']]);
    }

    private function setMeetingNurses($patientId){

        if(!empty($patientId)) {
            $patientData['CF_MeetingNurses'] = 'No - unable to contact';
            MssqlPatients::where('id', $patientId)->update($patientData);
            $nthId = DB::table("NeedToHire_Accounts")->where("aoc_patient_id",$patientId)->where('IsActive', 1)->first();
            if(!empty($nthId)){
                $nthId = $nthId->id;
            }
            if(!empty($nthId)){
                H::getHelper('NTHChecklist')->AddEditNTHMapRecord($nthId);
            }
        }


    }

    private function saveScheduleOutcome($requestData)
    {

        $patientData = [];
        $notesData = [];
        $patientData['intake_outcome'] = $requestData['outcome'];
        if ($requestData['outcome'] == 'Admit') {
            $patientData['IntakeStatus'] = 'Admitted';
            $notesData = [
                'entity' => 'client',
                'entity_id' => $requestData['aoc_patient_id'],
                'notes' => 'Client status updated to "Admit" by ' . Auth::user()->name
            ];

            $patientDetail = MssqlPatients::find($requestData['aoc_patient_id']);
            $careTeams = ConstantHelper::getCareTeam($requestData['aoc_patient_id']);
            $notificationText = 'Intake for ' . $patientDetail->Patient_FirstName .
            ' ' . $patientDetail->Patient_LastName . ' has been completed and client admitted to the agency.';
            $this->sendIntakeNotificationToCareTeam($careTeams, $notificationText, $requestData['aoc_patient_id']);
            if ($patientDetail->Patient_CurrentStatusID != 7 && empty($requestData['intake_id'])) {
                unset($patientData['IntakeStatus']);
                MssqlPatients::where('id', $patientDetail->id)->update(['IntakeStatus' => 'Scheduled']);
            }
        } else if ($requestData['outcome'] == 'Reschedule Intake') {
            $previousIntake = Intake::where("aoc_patient_id", $requestData['aoc_patient_id'])
                                ->orderBy('id', 'desc')
                                ->first();
            $eventId = $this->saveScheduleIntakeOutcome($requestData);
            H::ExcelLog('Event Id ' . $eventId, 'intake_outcome.log');

            $patientData['IntakeStatus'] = 'Scheduled';
            $intakeNurseID = $requestData['intakeStaffForm_intakeNurse'];
            $intakeNurseUser = H::getUserById($intakeNurseID);
            $notesData = [
                'entity' => 'client',
                'entity_id' => $requestData['aoc_patient_id'],
                'notes' => Auth::user()->name . ' rescheduled intake from ' . H::getFormatedDate($previousIntake->admission_visit_date) .
                            ' to ' . $requestData['intakeStaffForm_admissionVisitDate'] . ' from ' .
                        $requestData['intakeStaffForm_start_time'] . ' - ' . $requestData['intakeStaffForm_end_time'] .
                        ' with ' . $intakeNurseUser->name
            ];

            $patientDetail = MssqlPatients::find($requestData['aoc_patient_id']);
            $careTeams = ConstantHelper::getCareTeam($requestData['aoc_patient_id']);
            $notificationText = 'Intake rescheduled for ' . $patientDetail->Patient_FirstName .
            ' ' . $patientDetail->Patient_LastName . ' from ' .
            H::getFormatedDate($previousIntake->admission_visit_date) . ' to ' .
            H::getFormatedDate($requestData['intakeStaffForm_admissionVisitDate']) .
            ' at '.$requestData['intakeStaffForm_start_time'] . ' - ' . $requestData['intakeStaffForm_end_time'] . '.';
            $this->sendIntakeNotificationToCareTeam($careTeams, $notificationText, $requestData['aoc_patient_id']);
        } else if ($requestData['outcome'] == 'Provide an update') {

            $patientData['intake_comment'] = $requestData['notes_text'];
            $patientData['intake_followup'] = (!empty($requestData['intakeStaffForm_followupdate'])) ?
            date('Y-m-d H:i:s',strtotime($requestData['intakeStaffForm_followupdate'])) : '';
            $patientData['intake_contact'] = $requestData['intakeStaffForm_contact'];
            $notesData = [
                'entity' => 'client',
                'entity_id' => $requestData['aoc_patient_id'],
                'notes' => 'Intake was updated by ' . Auth::user()->name . '<br>' .
                            'Comments : ' . $requestData['notes_text'] . '<br>' .
                            'Follow Up Date : ' . $requestData['intakeStaffForm_followupdate'] . '<br>' .
                            'Contact : ' . $requestData['intakeStaffForm_contact']
            ];

            if(isset($requestData['mention'])) {

                if(LD::checkFeatureFlag('careteam-tagging-to-notes')) {
                    if(strstr($requestData['mention'],'-1')) {
                        $careTeams = H::getHelper("ConstantHelper")->getCareTeam($requestData['aoc_patient_id']);

                        $careTeamIds = [];
                        foreach($careTeams as $careteam) {
                            $careTeamIds[] = $careteam['id'];
                        }
                        if(!empty($careTeamIds)) {
                            $requestData['mention'] = str_replace("-1",implode(",",$careTeamIds),$requestData['mention']);
                        }
                    }
                }



                $mentions = array_unique(explode(",",$requestData['mention']));

                foreach($mentions as $mention) {
                    $data = [];
                    $data['user_id'] = $mention;
                    $data['title'] = 'New update provided on '.H::getPatientNameById($requestData['aoc_patient_id']);
                    $data['type'] = 'intake';
                    $data['related_item'] = $requestData['aoc_patient_id'];
                    $data['notes'] = $requestData['notes_text'];
                    Notifications::addNotification($data);
                }
            }
        } else if ($requestData['outcome'] == 'Non Admit') {
            $this->cancellMSEvent($requestData['aoc_patient_id']);
            if(LD::checkFeatureFlag('non-admit-updating-meeting-nurses')) {
                $this->setMeetingNurses($requestData['aoc_patient_id']);
            }
            $patientData['admission_visit_scheduled'] = 0;
            $patientData['intake_nonadmit_reason'] = $requestData['intakeStaffForm_nonadmitreasons'];
            $patientData['intake_controllable'] = $requestData['intakeStaffForm_contrbluncontrbl'];
            $patientData['intake_lead_exclude'] = isset($requestData['intakeStaffForm_excludefromreporting']) ?
            $requestData['intakeStaffForm_excludefromreporting'] : 0;
            $patientData['intake_comment'] = $requestData['intakeStaffForm_comments'];
            $patientData['IntakeStatus'] = 'Pending Non Admit';
            $notesData = [
                'entity' => 'client',
                'entity_id' => $requestData['aoc_patient_id'],
                'notes' => $this->intakeService->buildNonAdmitActivityNote(
                    Auth::user()->name,
                    $requestData
                )
            ];
        }
        $patientData['intake_outcome_by'] = Auth::user()->id;
        $patientData['intake_outcome_at'] = date('Y-m-d H:i:s');
        if ($requestData['type'] == 'intake') {
            Intake::where('id', $requestData['intake_id'])->update($patientData);
        } else {
            MssqlPatients::where('id', $requestData['aoc_patient_id'])->update($patientData);
        }
        H::getHelper('ActivityNotes')->setActivityNotes($notesData);
        return json_encode(["status" => 1, "bucket" => $requestData['bucket']]);
    }

    private function savePendingSocOutcome($requestData)
    {
        $patientData = [];
        $notesData = [];
        $patientData['intake_outcome'] = $requestData['outcome'];
        if ($requestData['outcome'] == 'Ready to Start') {
            $patientData['intake_readytostart'] = $requestData['intakeStaffForm_readytostartstatus'];
            $fromStatus = '';
            $patientDetail = MssqlPatients::find($requestData['aoc_patient_id']);
            if ($requestData['type'] == 'intake') {
                $intakeDetail = Intake::find($requestData['intake_id']);
                if ($intakeDetail->intake_readytostart == 0) {
                    $fromStatus = 'No';
                } else if ($intakeDetail->intake_readytostart == 1) {
                    $fromStatus = 'Yes';
                }
            } else {
                if ($patientDetail->intake_readytostart == 0) {
                    $fromStatus = 'No';
                } else if ($patientDetail->intake_readytostart == 1) {
                    $fromStatus = 'Yes';
                }
            }
            $toStatus = '';
            if ($requestData['intakeStaffForm_readytostartstatus'] == 0) {
                $toStatus = 'No';
            } else if ($requestData['intakeStaffForm_readytostartstatus'] == 1) {
                $toStatus = 'Yes';
            }
            if ($requestData['intakeStaffForm_readytostartstatus'] == 0) {
                $patientData['intake_readytostart_reason'] = $requestData['intakeStaffForm_reason'];
            }
            if(LD::checkFeatureFlag('update-pending-start-of-care-reason-column')){
                if($toStatus == 'Yes'){
                    $patientData['intake_readytostart_reason'] = '';
                }
            }
            if(LD::checkFeatureFlag('add-comment-box-in-intake-outcome-form')){
                $notesData = [
                    'entity' => 'client',
                    'entity_id' => $requestData['aoc_patient_id'],
                    'notes' => 'Client ready to start status updated from ' . $fromStatus . ' to ' . $toStatus . '. Comment: ' . trim($requestData['newComment'])
                ];
            }else{
                $notesData = [
                    'entity' => 'client',
                    'entity_id' => $requestData['aoc_patient_id'],
                    'notes' => 'Client ready to start status updated from ' . $fromStatus . ' to ' . $toStatus . ' by ' . Auth::user()->name
                ];
            }
            if ($toStatus == 'Yes') {
                $careTeams = ConstantHelper::getCareTeam($requestData['aoc_patient_id']);
                $notificationText = $patientDetail->Patient_FirstName .
                ' ' . $patientDetail->Patient_LastName . ' is ready to start.';
                $this->sendIntakeNotificationToCareTeam($careTeams, $notificationText, $requestData['aoc_patient_id']);
            }
        } else if ($requestData['outcome'] == 'Provide an update') {
            $patientData['intake_comment'] = $requestData['notes_text'];
            $patientData['intake_followup'] = (!empty($requestData['intakeStaffForm_followupdate'])) ?
            date('Y-m-d H:i:s',strtotime($requestData['intakeStaffForm_followupdate'])) : '';
            $patientData['intake_contact'] = $requestData['intakeStaffForm_contact'];
            $notesData = [
                'entity' => 'client',
                'entity_id' => $requestData['aoc_patient_id'],
                'notes' => 'Intake was updated by ' . Auth::user()->name . '<br>' .
                            'Comments : ' . $requestData['notes_text'] . '<br>' .
                            'Follow Up Date : ' . $requestData['intakeStaffForm_followupdate'] . '<br>' .
                            'Contact : ' . $requestData['intakeStaffForm_contact']
            ];

            if(isset($requestData['mention'])) {
                if(LD::checkFeatureFlag('careteam-tagging-to-notes')) {
                    if(strstr($requestData['mention'],'-1')) {
                        $careTeams = H::getHelper("ConstantHelper")->getCareTeam($requestData['aoc_patient_id']);

                        $careTeamIds = [];
                        foreach($careTeams as $careteam) {
                            $careTeamIds[] = $careteam['id'];
                        }
                        if(!empty($careTeamIds)) {
                            $requestData['mention'] = str_replace("-1",implode(",",$careTeamIds),$requestData['mention']);
                        }
                    }
                }
                $mentions = array_unique(explode(",",$requestData['mention']));

                foreach($mentions as $mention) {
                    $data = [];
                    $data['user_id'] = $mention;
                    $data['title'] = 'New update provided on '.H::getPatientNameById($requestData['aoc_patient_id']);
                    $data['type'] = 'intake';
                    $data['related_item'] = $requestData['aoc_patient_id'];
                    $data['notes'] = $requestData['notes_text'];
                    Notifications::addNotification($data);
                }
            }
        } else if ($requestData['outcome'] == 'Send Calendar Invite') {
            $eventId = $this->saveScheduleIntakeOutcome($requestData);
            H::ExcelLog('Event Id ' . $eventId, 'intake_outcome.log');

            $intakeNurseID = $requestData['intakeStaffForm_intakeNurse'];
            $intakeNurseUser = H::getUserById($intakeNurseID);
            $notesData = [
                'entity' => 'client',
                'entity_id' => $requestData['aoc_patient_id'],
                'notes' => Auth::user()->name . ' send invitation with ' . $intakeNurseUser->name .
                        ' for ' . $requestData['intakeStaffForm_admissionVisitDate'] . ' from ' .
                        $requestData['intakeStaffForm_start_time'] . '-'.$requestData['intakeStaffForm_end_time']
            ];
        } else if ($requestData['outcome'] == 'Non Admit') {
            $this->cancellMSEvent($requestData['aoc_patient_id']);
            if(LD::checkFeatureFlag('non-admit-updating-meeting-nurses')) {
                $this->setMeetingNurses($requestData['aoc_patient_id']);
            }
			$patientData['admission_visit_scheduled'] = 0;
            $patientData['intake_nonadmit_reason'] = $requestData['intakeStaffForm_nonadmitreasons'];
            $patientData['intake_controllable'] = $requestData['intakeStaffForm_contrbluncontrbl'];
            $patientData['intake_lead_exclude'] = isset($requestData['intakeStaffForm_excludefromreporting']) ?
            $requestData['intakeStaffForm_excludefromreporting'] : 0;
            $patientData['intake_comment'] = $requestData['intakeStaffForm_comments'];
            $patientData['IntakeStatus'] = 'Pending Non Admit';
            $notesData = [
                'entity' => 'client',
                'entity_id' => $requestData['aoc_patient_id'],
                'notes' => $this->intakeService->buildNonAdmitActivityNote(
                    Auth::user()->name,
                    $requestData
                )
            ];
        }
        $patientData['intake_outcome_by'] = Auth::user()->id;
        $patientData['intake_outcome_at'] = date('Y-m-d H:i:s');
        if ($requestData['type'] == 'intake') {
            Intake::where('id', $requestData['intake_id'])->update($patientData);
        } else {
            MssqlPatients::where('id', $requestData['aoc_patient_id'])->update($patientData);
        }
        H::getHelper('ActivityNotes')->setActivityNotes($notesData);
        return json_encode(["status" => 1, "bucket" => $requestData['bucket']]);
    }


    private function cancellMSEvent($patientID) {

        $previousIntake = Intake::where("aoc_patient_id", $patientID)->orderBy('id', 'desc')->first();

        if($previousIntake) {
            $intakeId = $previousIntake->id;

            if($intakeId) {
                if($event=H::getHelper("MicrosoftGraphBase")->isEventExist($intakeId,"intake")){
                    $userEmail = H::getHelper("MicrosoftGraph")->getEventUserEmail($event->created_by);

                    $notesData = [
                        'entity' => 'client',
                        'entity_id' => $patientID,
                        'notes' => 'Intake visit cancelled due to client being non admitted.'
                    ];

                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);

                    H::getHelper("MicrosoftGraph")
                    ->getUserByEmail($userEmail)
                    ->cancelEvent($event->event_id);

                }
            }
        }
    }

    private function saveClientStartsOutcome($requestData)
    {
        $patientData = [];
        $notesData = [];
        $patientData['intake_outcome'] = $requestData['outcome'];
        if ($requestData['outcome'] == 'Change Goal SOC Date') {
            $patientData['CF_GoalSOCDate'] = (!empty($requestData['intakeStaffForm_goalsocdate'])) ?
            date('Y-m-d H:i:s',strtotime($requestData['intakeStaffForm_goalsocdate'])) : '';
            if(LD::checkFeatureFlag('add-comment-box-in-intake-outcome-form')){
                $notesData = [
                    'entity' => 'client',
                    'entity_id' => $requestData['aoc_patient_id'],
                    'notes' =>'Intake goal SOC date changed by ' . Auth::user()->name . '<br>' .
                                'Goal SOC Date : ' . $requestData['intakeStaffForm_goalsocdate'].'. Comment:'.trim($requestData['newComment'])
                ];
            }else{
                $notesData = [
                    'entity' => 'client',
                    'entity_id' => $requestData['aoc_patient_id'],
                    'notes' => 'Intake goal SOC date changed by ' . Auth::user()->name . '<br>' .
                                'Goal SOC Date : ' . $requestData['intakeStaffForm_goalsocdate']
                ];
            }
        } else if ($requestData['outcome'] == 'Non Admit') {
            $this->cancellMSEvent($requestData['aoc_patient_id']);
            if(LD::checkFeatureFlag('non-admit-updating-meeting-nurses')) {
                $this->setMeetingNurses($requestData['aoc_patient_id']);
            }
			$patientData['admission_visit_scheduled'] = 0;
            $patientData['intake_nonadmit_reason'] = $requestData['intakeStaffForm_nonadmitreasons'];
            $patientData['intake_controllable'] = $requestData['intakeStaffForm_contrbluncontrbl'];
            $patientData['intake_lead_exclude'] = isset($requestData['intakeStaffForm_excludefromreporting']) ?
            $requestData['intakeStaffForm_excludefromreporting'] : 0;
            $patientData['intake_comment'] = $requestData['intakeStaffForm_comments'];
            $patientData['IntakeStatus'] = 'Pending Non Admit';
            $notesData = [
                'entity' => 'client',
                'entity_id' => $requestData['aoc_patient_id'],
                'notes' => $this->intakeService->buildNonAdmitActivityNote(
                    Auth::user()->name,
                    $requestData
                )
            ];
        }
        $patientData['intake_outcome_by'] = Auth::user()->id;
        $patientData['intake_outcome_at'] = date('Y-m-d H:i:s');
        if ($requestData['type'] == 'intake') {
            Intake::where('id', $requestData['intake_id'])->update($patientData);
        } else {
            MssqlPatients::where('id', $requestData['aoc_patient_id'])->update($patientData);
        }
        H::getHelper('ActivityNotes')->setActivityNotes($notesData);
        return json_encode(["status" => 1, "bucket" => $requestData['bucket']]);
    }

    public function saveOutcome(Request $request)
    {
       $attempt = 0;

        $requestData = $request->all();
        H::ExcelLog('Save Intake Outcome Start For ' . $requestData['aoc_patient_id'], 'intake_outcome.log');
        H::ExcelLog('Request Data : ' . print_r($requestData, true), 'intake_outcome.log');

        while ($attempt < self::SAVE_OUTCOME_MAX_RETRIES) {
            try {
                $attempt++;

                if (!empty($requestData['send_sms_care_team']) && ($requestData['outcome'] === 'Provide an update')) {
                    $requestData['from_page'] = $requestData['bucket'];
                    H::ExcelLog('Send SMS to care team Start For ' . $requestData['aoc_patient_id'], 'intake_outcome.log');
                    H::getHelper('MessageWidget')->sendSmsToIntakeCareTeam($requestData);
                }

                switch ($requestData['bucket']) {
                    case 'intake_prospective_clients':
                        return $this->saveProspectiveClientsOutcome($requestData);
                    case 'intake_schedule':
                        return $this->saveScheduleOutcome($requestData);
                    case 'intake_pending_soc':
                        return $this->savePendingSocOutcome($requestData);
                    case 'intake_client_starts':
                        return $this->saveClientStartsOutcome($requestData);
                    case 'intake_pending_non_admit':
                        return $this->saveIntakePendingNonAdmitOutcome($requestData);
                }

                H::ExcelLog('Save Intake Outcome End For ' . $requestData['aoc_patient_id'], 'intake_outcome.log');
                break;

            } catch (Exception $e) {
                H::ExcelLog("Attempt #$attempt failed: " . $e->getMessage(), 'intake_outcome.log');

                if ($attempt >= self::SAVE_OUTCOME_MAX_RETRIES) {
                    H::ExcelLog('Save Intake Outcome End For ' . $requestData['aoc_patient_id'], 'intake_outcome.log');
                    return json_encode([
                        'status' => -1,
                        'bucket' => $requestData['bucket'],
                        'errMsg' => $e->getMessage()
                    ]);
                }

                sleep($attempt * 2);
            }
        }
    }

    public function getDataTablePendingNonAdmit($data, $request){
        $dataTable = Datatables::query($data)->setRowAttr([
            'class' => function($row) {
                if(LD::checkFeatureFlag('red-highlights-intake-all-buckets')){
                    if(!in_array($row->LOBID, ['1003', '1004', '1006']) &&
                    isset($row->intake_approved_date) &&
                    strtotime('now') > strtotime($row->intake_approved_date . ' +2 weekday')){
                        return 'redexception';
                    }elseif(in_array($row->LOBID, ['1003', '1004', '1006']) &&
                    isset($row->intake_approved_date) &&
                    strtotime('now') > strtotime($row->intake_approved_date . ' +2 weekday')){
                        return 'redexception';
                    }
                    return '';
                }else{
                    return '';
                }

            },
        ])->editColumn('notes', function ($data) {
            if (!empty ($data->notes)) {
                [$created_at, $notes, $created_by] = explode("#", $data->notes);
                $notes = str_replace(['"',"<br>"], ["'",', '], (preg_replace('/^\s+|\s+$|\s+(?=\s)/', '', $notes)));
                $userName = H::getUserNameById($created_by);
                $date = date('m/d', strtotime($created_at));
                $note = $date . ' ' . $userName . ' commented: ' . $notes;
                $str = '';
                if (strlen($note) > 35) {
                    $str = substr($note, 0, 35) . '...';
                } else {
                    $str = $note;
                }
                return "<span data-toggle='tooltip' title=\"" .
                    mb_convert_encoding($note, 'UTF-8', 'UTF-8') . "\">" .
                    mb_convert_encoding($str, 'UTF-8', 'UTF-8') .
                    '</span>';
            }
            return "-";
        })
        ->editColumn('intake_approved_date', function ($row) {
            return H::getFormatedDate($row->intake_approved_date);
        })
        ->editColumn('Assignedto', function ($row) {
            return H::getHelper('IntakeCoordinatorStaffTransfers')->getPatientAssignedTo($row);
        })
        ->addColumn('Action', function ($row) {
            $btn = '';
            $btn .= '<a
                href="javascript:void(0)"
                onclick="getIntakeStaffTransfersOutcome(\'' . $row->id . '\', \'intake_pending_non_admit\', \'' . $row->type . '\')"
                title="Document Call">
                    <i class="fa fa-calendar" aria-hidden="true"></i>
                </a>';
            if(LD::checkFeatureFlag('remove-union-intake-dashboard')) {
					$btn .= '<a
						href="javascript:void(0)"
						onclick="getClientDetails(\'' . $row->pid . '\', \'clientintake\')"
						title="View Client Detail">
							<i class="fa fa-user" aria-hidden="true"></i>
						</a>';
				} else {
					$btn .= '<a
						href="javascript:void(0)"
						onclick="getClientDetails(\'' . $row->id . '\', \'clientintake\')"
						title="View Client Detail">
							<i class="fa fa-user" aria-hidden="true"></i>
						</a>';
				}
            return $btn;
        });
        if ($request->has('order')) {
            $columnIndex = $request->get('order')[0]['column'];
            $columnName = $request->get('columns')[$columnIndex]['data'];
            $sortDirection = $request->get('order')[0]['dir'];
            if ($columnName == 'intake_approved_date') {
                $data->orderByRaw("ISNULL(intake_approved_date),
                                    intake_approved_date = '',
                                    intake_approved_date = '0000-00-00 00:00:00',
                                    intake_approved_date $sortDirection");
            } else {
                $data->orderByRaw("$columnName $sortDirection");
            }
        }
        return $dataTable->rawColumns(['notes', 'Action'])
                ->make(true);
    }

    public function saveIntakePendingNonAdmitOutcome($requestData){
        $patientData = [];
        $notesData = [];
        $patientData['intake_outcome'] = $requestData['outcome'];
        if ($requestData['outcome'] == 'Intake Status') {
            $patientData['IntakeStatus'] = $requestData['new_intake_status'];
            $notesData = [
                'entity' => 'client',
                'entity_id' => $requestData['aoc_patient_id'],
                'notes' => 'Intake Status is changed by ' . Auth::user()->name . '<br>' .
                            'Intake Status : ' . $requestData['new_intake_status'] . '<br>' .
                            'Comment : ' . $requestData['notes_text']
            ];

            if(isset($requestData['mention'])) {
                if(LD::checkFeatureFlag('careteam-tagging-to-notes')) {
                    if(strstr($requestData['mention'],'-1')) {
                        $careTeams = H::getHelper("ConstantHelper")->getCareTeam($requestData['aoc_patient_id']);

                        $careTeamIds = [];
                        foreach($careTeams as $careteam) {
                            $careTeamIds[] = $careteam['id'];
                        }
                        if(!empty($careTeamIds)) {
                            $requestData['mention'] = str_replace("-1",implode(",",$careTeamIds),$requestData['mention']);
                        }
                    }
                }

                $mentions = array_unique(explode(",",$requestData['mention']));

                foreach($mentions as $mention) {
                    $data = [];
                    $data['user_id'] = $mention;
                    $data['title'] = 'Intake Status Changed';
                    $data['type'] = 'intake';
                    $data['related_item'] = $requestData['aoc_patient_id'];
                    $data['notes'] = $requestData['notes_text'];
                    Notifications::addNotification($data);
                }
            }
        }elseif ($requestData['outcome'] == 'Approve Non Admit') {
            $patientData['intake_nonadmit_reason'] = $requestData['intakeStaffForm_nonadmitreasons'];
            $patientData['intake_controllable'] = $requestData['intakeStaffForm_contrbluncontrbl'];
            $patientData['intake_lead_exclude'] = isset($requestData['intakeStaffForm_excludefromreporting']) ?
            $requestData['intakeStaffForm_excludefromreporting'] : 0;
            $patientData['intake_comment'] = $requestData['intakeStaffForm_comments'];
            $patientData['IntakeStatus'] = 'Non Admit Approved';
            $patientData['intake_approved_date'] = date('Y-m-d H:i:s');
            $notesData = [
                'entity' => 'client',
                'entity_id' => $requestData['aoc_patient_id'],
                'notes' => Auth::user()->name . ' requested client be non admitted.'
            ];
            //Added Notification to care team members
            $patientDetail = MssqlPatients::find($requestData['aoc_patient_id']);
            $careTeams = ConstantHelper::getCareTeam($requestData['aoc_patient_id']);
            $notificationText = 'Non admit approved for ' . $patientDetail->Patient_FirstName .
            ' ' . $patientDetail->Patient_LastName;
            $this->sendIntakeNotificationToCareTeam($careTeams, $notificationText, $requestData['aoc_patient_id']);
            $this->removeNthSchedules($requestData);
        }
        $patientData['intake_outcome_by'] = Auth::user()->id;
        $patientData['intake_outcome_at'] = date('Y-m-d H:i:s');
        if ($requestData['type'] == 'intake') {
            Intake::where('id', $requestData['intake_id'])->update($patientData);
        } else {
            MssqlPatients::where('id', $requestData['aoc_patient_id'])->update($patientData);
        }
        H::getHelper('ActivityNotes')->setActivityNotes($notesData);
        return json_encode(["status" => 1, "bucket" => $requestData['bucket']]);
    }

    public function getReferralSource(Request $request){
        $term = str_replace('  ', ' ', str_replace(',', '', $request->term));
        $query = DB::table('referralsource_master')
        ->select('referralsource_master.id', 'referralsource_master.name')
        ->where('referralsource_master.isvalidrecord', '=', 1)
        ->whereRaw("REPLACE(REPLACE(referralsource_master.name, '  ', ' '), ',', '') LIKE ?", ['%' . $term . '%'])
        ->get();
        $refarray = [];
        if(count($query) > 0){
            foreach($query as $ref){
                $refarray[$ref->id] = $ref->name;
            }
        }

        return response()->json($refarray);
    }

    public function getReferralPhysician(Request $request){
        $term = str_replace('  ', ' ', str_replace(',', '', $request->term));
        $query = DB::table('physician_master')
        ->select(
            'physician_master.id',
            DB::raw("CONCAT(physician_master.first_name, ' ',
        IF(physician_master.middle_name != '',
            CONCAT(physician_master.middle_name, ' '),
            ''
        ),
        physician_master.last_name
    ) AS full_name")
        )
        ->where('physician_master.status', '=', 'Active')
        ->where(DB::raw("CONCAT(physician_master.first_name, ' ',
        IF(physician_master.middle_name != '',
            CONCAT(physician_master.middle_name, ' '),
            ''
        ),
        physician_master.last_name)"), 'like', '%' . $term . '%')
        ->get();
        $refarray = [];
        if(count($query) > 0){
            foreach($query as $ref){
                $refarray[$ref->id] = $ref->full_name;
            }
        }
        return response()->json($refarray);
    }

    public static function getRefSourceById($id){
        return DB::table('referralsource_master')->where('source_id', $id)->value('name');
    }

    public static function AttendingPhysicianByID($id){
        $phy_name = DB::table('physician_master')
        ->select(DB::raw("CONCAT(physician_master.first_name, ' ',
        IF(physician_master.middle_name != '',
            CONCAT(physician_master.middle_name, ' '),
            ''
        ),
        physician_master.last_name
    ) AS full_name"))
        ->where('physician_id', $id)->first();
        if(!empty($phy_name)){
            return $phy_name->full_name;
        }else{
            return '';
        }
    }

    public function getIdByName($type, $name){
        $table_name = 'referralsource_master';
        $field_name = 'source_id';
        if ($type == 'phy') {
            $table_name = 'physician_master';
            $field_name = 'physician_id';
        }
        $query = DB::table($table_name)->select($field_name);
        if ($type == 'phy') {
            $query = $query->whereRaw(
                "CONCAT(physician_master.first_name, ' ',
        IF(physician_master.middle_name != '',
            CONCAT(physician_master.middle_name, ' '),
            ''
        ),
        physician_master.last_name
    ) LIKE ?",
                ['%' . $name . '%']
            );
        } else {
            $query = $query->where('referralsource_master.name', 'LIKE', '%' . $name . '%');
        }
        return $query->first();
    }

    public function getTerminalPayer(Request $request)
    {
        $term = str_replace('  ', ' ', str_replace(',', '', $request->term));
        $payerList = DB::table('payment_source')
        ->select('name')
        ->where('name','like','%'.$term.'%')
        ->where('name','not like','%EVV%')
        ->whereRaw('IFNULL(is_deleted, 0) <> 1')
        ->limit(15)
        ->orderBy('name')
        ->get()->toArray();
        $payerListData = [];
        foreach ($payerList as $payerSubData) {
            $payerListData[] = ['payer'=>$payerSubData->name, 'label' => $payerSubData->name];
        }
        return response()->json($payerListData);
    }

    public function getRespiteTerminalPayer(Request $request)
    {
        $term = str_replace('  ', ' ', str_replace(',', '', $request->term));
        $payerList = MssqlPatients::distinct()->select('RespitePayer')
        ->where('RespitePayer','like','%'.$term.'%')
        ->where('RespitePayer','not like','%EVV%')
        ->limit(15)
        ->orderBy('RespitePayer')
        ->get()->toArray();
        $payerListData = [];
        foreach ($payerList as $payerSubData) {
            $payerListData[] = ['payer'=>$payerSubData['RespitePayer'],'label' => $payerSubData['RespitePayer']];
        }
        return response()->json($payerListData);
    }

    public function markUnassignedCareTeam(Request $request) {

        try {
            MssqlPatients::where('id', $request->id)->update(['marked_careteam' => date('Y-m-d H:i:s')]);
            return response()->json(['status'=>true]);
        } catch(\Exception $e) {
            return response()->json(['status'=>false]);
        }
    }


    public static function checkIsNewref($data){
        $clientId = $data['clientPId'];
        $lob = $data['lob'];
        $ms_patient = DB::table('mssql_patients')
        ->where('id', $clientId)
        ->where('LOBID', $lob)
        ->first();
        return empty($ms_patient);
    }

    public function removeNthSchedules($data){
        try{
            $patientId = $data['aoc_patient_id'];
            $intakeId = $data['intake_id'];
            $type = $data['type'];
            $nthId = NULL;
            $reason = 'Non Admit';
            if($type == 'intake'){
                $patient_id = Intake::where('id', $intakeId)->value('aoc_patient_id');
                $nthId = DB::table("NeedToHire_Accounts")->where("aoc_patient_id",$patient_id)->where('IsActive', 1)->first();
                if(!empty($nthId)){
                    $nthId = $nthId->id;
                }
            }else if($type == 'patient'){
                $nthId = DB::table("NeedToHire_Accounts")->where("aoc_patient_id",$patientId)->where('IsActive', 1)->first();
                if(!empty($nthId)){
                    $nthId = $nthId->id;
                }
            }
            if(!empty($nthId)){
                $items = DB::table('NeedToHire_Schedules')->where('aoc_nthaccounts_id', $nthId)->pluck('id')->toArray();
                foreach($items as $item) {
                    $currentData = DB::table('NeedToHire_Schedules')->where("id",$item)->first();
                    DB::table("NeedToHire_Schedules")->where("id",$item)->update(["updated_at"=>date('Y-m-d H:i:s'),"IsActive"=>"0",'RemovalReason'=>$reason,'updated_by'=>Auth::user()->id]);
                    H::ExcelLog('removeall '.print_r(["updated_at"=>date('Y-m-d H:i:s'),"IsActive"=>"0",'RemovalReason'=>$reason,'updated_by'=>Auth::user()->id],true),'NthSchedules.log');
                    H::getHelper('ConstantHelper')->setNTHAuditRecord($item,'remove',$currentData);
                    H::getHelper('NTHChecklist')->ignoreNTHScheduleReason($reason,$currentData);
                }
                //inActive nth account
                DB::table("NeedToHire_Accounts")->where("id",$nthId)->update(['IsActive'=>0,'RemovalReason'=>'Non-admit']);
                $this->socialMediaAdAutoCloseService->closeOpenAdsForNth($nthId);
                H::getHelper('NTHChecklist')->AddEditNTHMapRecord($nthId);
                H::getHelper('NTHChecklist')->setNTHScheduleToMap($nthId);
                //Added activity history
                $notes = H::getLoggedInUser()['name'].' removed schedules on the NTH for reason: '.$reason;
                $notesData = ['entity' => 'nthAccount', 'entity_id' => $nthId, 'notes' =>$notes, 'completed' => 1];
                H::getHelper('ActivityNotes')->setActivityNotes($notesData);
            }
        }catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function checkPatientAccount(Request $request)
    {
        $term = trim(str_replace('  ', ' ', str_replace(',', '', $request->clientId)));
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
                'mssql_patients.AdmitNo'
            )
            ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_patients.FacilityID')
            ->leftJoin('mssql_patientstatus', 'mssql_patientstatus.PatientStatusID', '=', 'mssql_patients.Patient_CurrentStatusID');

        if (LD::checkFeatureFlag('client-new-changes')) {
            $clientlist->where('mssql_patients.IsLatestAdmit', 1);
        }

        $clientlist->addselect(
            'mssql_patients.Patient_Address1',
            'mssql_patients.Patient_Address2',
            'mssql_patients.Patient_Zip',
            'mssql_patients.RespitePayer as respiteTerminalPlayer',
            'mssql_patients.aoc_facility_id',
            'mssql_patients.LOBID',
            'mssql_patients.CF_Specialized',
            'mssql_patients.ic_id'
        );

        $clientData = $clientlist->where('mssql_patients.PatientAccount', '=', $term)
                    ->orderBy('mssql_patients.IsLatestAdmit', 'desc')
                    ->orderBy('mssql_patients.IntakeID', 'desc')->first();
        if (!$clientData) {
            return response()->json(['error' => 'Client not found'], 404);
        }

        return response()->json([
            'id' => $clientData->id,
            'Patient_FirstName' => $clientData->Patient_FirstName,
            'Patient_LastName' => $clientData->Patient_LastName,
            'Patient_Address1' => $clientData->Patient_Address1 ?? '',
            'Patient_Address2' => $clientData->Patient_Address2 ?? '',
            'Patient_Zip' => $clientData->Patient_Zip ?? '',
            'Patient_City' => $clientData->Patient_City,
            'Patient_State' => $clientData->Patient_State,
            'PatientAccount' => $clientData->PatientAccount,
            'payer' => $clientData->Payer,
            'respiteTerminalPlayer' => $clientData->respiteTerminalPlayer ?? '',
            'aoc_facility_id' => $clientData->aoc_facility_id ?? '',
            'LOBID' => $clientData->LOBID ?? '',
            'CF_Specialized' => $clientData->CF_Specialized ?? 'No',
            'ic_id' => $clientData->ic_id ?? 0,
            'PatientStatus' => $clientData->PatientStatus,
            'value' => $clientData->Patient_FirstName . ' ' . $clientData->Patient_LastName,
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        $clinicians = DB::table('mssql_providers as p')
        ->leftJoin('mssql_employmentstatus as e', 'e.EmploymentStatusID', '=', 'p.EmploymentStatusID')
        ->leftJoin('mssql_facilities as f', 'f.FacilityID', '=', 'p.FacilityID')
        ->where('p.fullName', 'LIKE', "%{$query}%")
        ->whereNotIn('e.EmploymentStatus', ['Terminated', 'Inactive'])
        ->select(
            'p.ClinicianID',
            'p.fullName',
            'f.BranchName as branch',
            'p.phone',
            'p.AOC_Email as email',
            'e.EmploymentStatus as status'
        )
        ->get();
        return response()->json($clinicians);
    }

    public function validateClinician(Request $request){
        $phone = $request->input('phone');
        $email = $request->input('email');

        $exists = DB::table('mssql_providers as p')
            ->leftJoin('mssql_employmentstatus as e', 'e.EmploymentStatusID', '=', 'p.EmploymentStatusID')
            ->where(function ($q) use ($phone, $email) {
                $q->where('phone', $phone)
                    ->orWhere('AOC_Email', $email);
            })
            ->whereNotIn('e.EmploymentStatus', ['Terminated', 'Inactive'])
            ->exists();

        return response()->json([
            'valid' => $exists,
        ]);
    }

}
