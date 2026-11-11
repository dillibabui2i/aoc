<?php
namespace App\Http\Controllers;

use App\Helpers\H;
use App\Helpers\LD;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\User;
use DataTables;
use App\MssqlProviders;
use App\MssqlRecruitertracker;
class ClientCareCoordinatorController extends Controller
{
    public function getClientCareCoordinatorSchedules(Request $request) {
        $data = H::getHelper("ClientCareCoordinatorSchedules")->getClientCareCoordinatorSchedules($request->input("type"),$request);
        return Datatables::of($data)
        ->addColumn('Action', function ($row){
            $btn = '';
            return $btn;
        })->make(true);
    }
    public function reloadClientCareCoordinatorSchedules(Request $request) {
        return json_encode(["counter"=>H::getHelper('ClientCareCoordinatorSchedules')->getClientCareCoordinatorSchedules($request->input("type"),$request,1),"type"=>$request->input("type")]);
    }
    public function getClientCareCoordinatorHourComparison(Request $request) {
        $data = H::getHelper("ClientCareCoordinatorHourComparison")->getClientCareCoordinatorHourComparison($request->input("type"),$request);
        return Datatables::of($data)
        ->addColumn('Action', function ($row){
            $btn = '';
            return $btn;
        })->make(true);
    }
    public function reloadClientCareCoordinatorHourComparison(Request $request) {
        return json_encode(["counter"=>H::getHelper('ClientCareCoordinatorHourComparison')->getClientCareCoordinatorHourComparison($request->input("type"),$request,1),"type"=>$request->input("type")]);
    }
    public function getSchedulesByWeekAndCompare(Request $request)
    {
        H::getHelper('ClientCareCoordinatorHourComparison')->getSchedulesByWeekAndCompare();
    }

    public function getClinicianNoLongerOnCase(Request $request) {
        $data = H::getHelper("ClinicianNoLongerOnCase")->getClinicianNoLongerOnCase($request);
        return Datatables::of($data)->make(true);
    }

    public function reloadClinicianNoLongerOnCase(Request $request) {
        return json_encode(["counter" => H::getHelper('ClinicianNoLongerOnCase')->getClinicianNoLongerOnCase($request, 1), "type" => $request->input("type")]);
    }

    public function showClinicianNoLongerOnCaseNextStepForm(Request $request) {
        try {
            if ($request->input('id') != '') {
                $clinicianDetails = MssqlProviders::select('mssql_providers.*')->where('mssql_providers.id',$request->input('id'))->first();
                return view('ccc.clinicianNoLongerOnCaseOutcome', ['clinicianDetails'=>$clinicianDetails]);
            }
        } catch (\Exception $e) {
            return false;
        }
    }
    public function saveClinicianNoLongerOnCaseOutcomeForm(Request $request) {
        $req = $request->all();
        $data = $newRecruiter = [];
        try {
            //code...
            if(!empty($req['outcome'])) {
                $clinicianDetails = MssqlProviders::select('mssql_providers.*')->where('mssql_providers.id',$request->input('id'))->first();
                $clinicianName = $clinicianDetails->fullName;
                $user = H::getLoggedInUser();
                //Update provider
                $data['nurse_lost_client_resolved_by'] = $user['id'];
                $data['nurse_lost_client_resolved'] = date('Y-m-d H:i:s');
                $data['nurse_lost_client'] = '0';
                DB::table("mssql_providers")->where("id",$req['id'])->update($data);
                $aocFacilityId = $branchLocation = $activityNote = $recruiter= '';
                if($req['outcome_key']=='review_new_clients' ) {
                    //New Tracker
                    $address = [];
                    $patientDetail = DB::table('mssql_patients')->where('PatientID', $clinicianDetails->nurse_lost_patientid)->first();
                    if(isset($patientDetail->Patient_Address1) && !empty($patientDetail->Patient_Address1)) {
                        $address['address1'] = $patientDetail->Patient_Address1;
                    }
                    if(isset($patientDetail->Patient_Address2) && !empty($patientDetail->Patient_Address2)) {
                        $address['address2'] = $patientDetail->Patient_Address2;
                    }
                    if(isset($patientDetail->Patient_City) && !empty($patientDetail->Patient_City)) {
                        $address['city'] = $patientDetail->Patient_City;
                    }
                    if(isset($patientDetail->Patient_State) && !empty($patientDetail->Patient_State)) {
                        $address['state'] = $patientDetail->Patient_State;
                    }
                    if(isset($patientDetail->Patient_Zip) && !empty($patientDetail->Patient_Zip)) {
                        $address['zipcode'] = $patientDetail->Patient_Zip;
                    }
                    $recruiterId = H::getHelper('ConstantHelper')->getRecruiterByPolygon($address);
                    if($recruiterId) {
                        $recruiter = H::getHelper('ConstantHelper')->getUserById($recruiterId);
                    }
                    if($recruiterId!=0 ){
                        $newRecruiter['Recruiter'] = $recruiter;
                        $newRecruiter['user_id'] = $recruiterId;
                    }
                    $tmp = DB::table('mssql_facilities')->where('FacilityID', $clinicianDetails->FacilityID)->first();
                    if (!empty($tmp)) {
                        $aocFacilityId = $aocFacilityId = $tmp->id;
                        $branchLocation = $tmp->FacilityName;
                    }
                    $newRecruiter['aoc_facility_id'] = $aocFacilityId;
                    $newRecruiter['Branch_Location'] = $branchLocation;
                    $newRecruiter['aoc_provider_id'] = $clinicianDetails->id;
                    $newRecruiter['Candidate_Current_Status'] = 'Need to Review New Clients';
                    $newRecruiter['is_active'] = 1;
                    $newRecruiter['updated_at'] = date('Y-m-d H:i:s');
                    $newRecruiter['created_at'] = date('Y-m-d H:i:s');
                    $newRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                    $newRecruiter['application_date'] = date('Y-m-d H:i:s');
                    $newRecruiter['Date_Contacted'] = date('Y-m-d H:i:s');
                    $newRecruiter['Clinician_ID'] = $clinicianDetails->ClinicianID;
                    $newRecruiter['Candidate'] = $clinicianName;
                    $newRecruiter['Skillset'] = $clinicianDetails->DisciplineName;
                    $recruitertrackerData = MssqlRecruitertracker::create($newRecruiter);
                    $trackerId = $recruitertrackerData->id;
                    //tracker note
                    $trackerNoteData=[];
                    $trackerNoteData['entity'] = 'tracker';
                    $trackerNoteData['entity_id'] = $trackerId;
                    $trackerNoteData['notes'] =$req['comment'];
                    H::getHelper("ActivityNotes")->setActivityNotes($trackerNoteData);
                    //main note
                    $text= "";
                    $text .= $user['name'].' set an outcome from Clinician No Longer On Case<br>';
                    $text .= 'Outcome: '. $req['outcome'].'<br>';
                    $text.=$req['comment'];
                    $activityNote = $text;
                } elseif($req['outcome_key']=='not_interested_in_perm_case_moved_to_active_coverage' ) {
                    $activityNote = $clinicianName.' is not interested in perm cases, moved to coverage active.';
                } elseif($req['outcome_key']=='resigned' ) {
                    $activityNote = $clinicianName.' has resigned.';
                } elseif($req['outcome_key']=='terminated' ) {
                    $activityNote = $clinicianName.' has been terminated.';
                }
                //Notes
                $noteData['entity'] = 'Clinician';
                $noteData['entity_id'] = $req['id'];
                $noteData['notes'] =$activityNote;
                $noteData['type'] = 'clinician_no_longer_on_case';
                H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                return response()->json(["status" => true]);

            }
        } catch (\Throwable $th) {
            //throw $th;
            H::ExcelLog('saveClinicianNoLongerOnCaseOutcomeForm:'.$th->getMessage(),'saveClinicianNoLongerOnCaseOutcomeForm.log');
            return response()->json(["status" => false]);
        }
    }
    public function getClinicianNoLongerOnCaseNthSchedule(Request $request) {
        $aoc_nthaccounts_id = $request->input("aoc_nthaccounts_id");
        $nthDetails = DB::table("NeedToHire_Accounts")->select('aoc_patient_id')->where('id',$aoc_nthaccounts_id)->first();
        $todayDate = date('Y-m-d');
        $bucket = DB::table("mssql_schedulemaster");
        $bucket->select(DB::raw("DISTINCT mssql_schedulemaster.PatientID, mssql_schedulemaster.ProviderID, mssql_providers.fullName"));
        $bucket->leftJoin("mssql_patients",function($query){
            $query->on("mssql_patients.PatientID","=","mssql_schedulemaster.PatientID");
        });
        $bucket->leftJoin("mssql_providers",function($query){
            $query->on("mssql_providers.ProviderID","=","mssql_schedulemaster.ProviderID");
        });
        $bucket->where("mssql_schedulemaster.ScheduleDate", ">",$todayDate);
        $bucket->where("mssql_patients.id",$nthDetails->aoc_patient_id);
        $bucket->where("mssql_schedulemaster.ProviderID", "!=",18212);
        $nthData = $bucket->get();
        if(count($nthData) > 0) {
            return view('ccc.clinicianNoLongerOnCaseNthSchedule', compact('nthData','aoc_nthaccounts_id'));
        }
        return response()->json(['status' => false]);
    }

    public function saveClinicianNoLongerOnCaseNthSchedule(Request $request) {
        $req = $request->all();
        if(is_array($request->provider_id)) {
            $needToHireDetails = DB::table('NeedToHire_Accounts')->where('IsActive',1)->where('id', $req['aoc_nthaccounts_id'])->first();
            foreach($request->provider_id as $provider_id) {
                $data = $newRecruiter = [];
                $activityNote = '';
                try {
                    //code...
                    if(!empty($req['outcome'][$provider_id])) {
                        $outcome = $req['outcome'][$provider_id];
                        $comment = $req['comment'][$provider_id]??'';
                        $clinicianDetails = MssqlProviders::select('mssql_providers.*')->where('mssql_providers.ProviderID',$provider_id)->first();
                        $clinicianName = $clinicianDetails->fullName;
                        $user = H::getLoggedInUser();
                        //Update provider
                        $data['nurse_lost_client_resolved_by'] = $user['id'];
                        $data['nurse_lost_client_resolved'] = date('Y-m-d H:i:s');
                        $data['nurse_lost_client'] = '0';
                        DB::table("mssql_providers")->where("id",$clinicianDetails->id)->update($data);
                        $aocFacilityId = $branchLocation = '';
                        if($outcome=='review_new_clients' ) {
                            //New Tracker
                            if (!empty($needToHireDetails)) {
                                $newRecruiter['Recruiter'] = $needToHireDetails->Recruiter;
                                $newRecruiter['user_id'] = $needToHireDetails->user_id;
                                $newRecruiter['Skillset'] = $needToHireDetails->skills;
                            }
                            $tmp = DB::table('mssql_facilities')->where('FacilityID', $clinicianDetails->FacilityID)->first();
                            if (!empty($tmp)) {
                                $aocFacilityId = $aocFacilityId = $tmp->id;
                                $branchLocation = $tmp->FacilityName;
                            }
                            $newRecruiter['aoc_facility_id'] = $aocFacilityId;
                            $newRecruiter['Branch_Location'] = $branchLocation;
                            $newRecruiter['aoc_provider_id'] = $clinicianDetails->id;
                            $newRecruiter['Candidate_Current_Status'] = 'Need to Review New Clients';
                            $newRecruiter['is_active'] = 1;
                            $newRecruiter['updated_at'] = date('Y-m-d H:i:s');
                            $newRecruiter['created_at'] = date('Y-m-d H:i:s');
                            $newRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
                            $newRecruiter['application_date'] = date('Y-m-d H:i:s');
                            $newRecruiter['Date_Contacted'] = date('Y-m-d H:i:s');
                            $newRecruiter['Clinician_ID'] = $clinicianDetails->ClinicianID;
                            $newRecruiter['Candidate'] = $clinicianName;
                            $recruitertrackerData = MssqlRecruitertracker::create($newRecruiter);
                            $trackerId = $recruitertrackerData->id;
                            //tracker note
                            $trackerNoteData=[];
                            $trackerNoteData['entity'] = 'tracker';
                            $trackerNoteData['entity_id'] = $trackerId;
                            $trackerNoteData['notes'] =$comment;
                            H::getHelper("ActivityNotes")->setActivityNotes($trackerNoteData);
                            //main note
                            $text= "";
                            $text .= $user['name'].' set an outcome from Clinician No Longer On Case<br>';
                            $text .= 'Outcome: '. $outcome.'<br>';
                            $text.=$comment;
                            $activityNote = $text;
                        } elseif($outcome=='not_interested_in_perm_case_moved_to_active_coverage' ) {
                            $activityNote = $clinicianName.' is not interested in perm cases, moved to coverage active.';
                        } elseif($outcome=='resigned' ) {
                            $activityNote = $clinicianName.' has resigned.';
                        } elseif($outcome=='terminated' ) {
                            $activityNote = $clinicianName.' has been terminated.';
                        }
                        //Notes
                        $noteData['entity'] = 'Clinician';
                        $noteData['entity_id'] = $clinicianDetails->id;
                        $noteData['notes'] =$activityNote;
                        $noteData['type'] = 'clinician_no_longer_on_case';
                        H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                    }
                } catch (\Throwable $th) {
                    //throw $th;
                    H::ExcelLog('saveClinicianNoLongerOnCaseOutcomeForm:'.$th->getMessage(),'saveClinicianNoLongerOnCaseOutcomeForm.log');
                    return response()->json(["status" => false]);
                }
            }
            return response()->json(["status" => true]);
        }
    }

    public function getClientCareCoordinatorSchedulesBuiltAuth(Request $request) {
        if($request->input("type") == 'clients_without_auth') {
            $data = H::getHelper("ClientCareCoordinatorSchedulesBuiltAuth")
        ->getClientCareCoordinatorSchedulesBuiltClientWithoutAuth($request);
        } else {
            $data = H::getHelper("ClientCareCoordinatorSchedulesBuiltAuth")
            ->getClientCareCoordinatorSchedulesBuiltAuth($request);
        }
        if($request->input("type") == 'clients_without_auth'){
            return Datatables::of($data)
            ->addColumn('Action', function ($row) use($request){
                return '<a href="javascript:void(0)"
                onclick="getScheduleCorrected('.$row->aoc_patient_id.',\''.$request->input("type").'\')">
                    <i class="fa fa-check-square-o" aria-hidden="true"></i></a> | <a href="javascript:void(0)"
                    onclick=\'getClientDetails("'. $row->aoc_patient_id . '")\'>
                    <i class="fa fa-user" aria-hidden="true"></i></a>';
            })->escapeColumns('Action')->make(true);
        }else{
            return Datatables::of($data)->setRowAttr([
                'class' => function($row) {
                        if ($row->TotalHours > $row->HoursNeeded) {
                            return 'redexception';
                        }
                    return '';
                },
            ])
            ->addColumn('Action', function ($row) use($request){
                return '<a href="javascript:void(0)"
                onclick="getScheduleCorrected('.$row->aoc_patient_id.',\''.$request->input("type").'\')">
                    <i class="fa fa-check-square-o" aria-hidden="true"></i></a> | <a href="javascript:void(0)"
                    onclick=\'getClientDetails("'. $row->aoc_patient_id . '")\'>
                    <i class="fa fa-user" aria-hidden="true"></i></a>';
            })->editColumn('HoursNeeded', function ($row) {
                if (intval($row->HoursNeeded) != $row->HoursNeeded) {
                    return number_format($row->HoursNeeded, 2, '.', ',');
                } else {
                    return number_format($row->HoursNeeded, 0, '.', ',');
                }
            })->editColumn('TotalHours', function ($row) {
                if (intval($row->TotalHours) != $row->TotalHours) {
                    return number_format($row->TotalHours, 2, '.', ',');
                } else {
                    return number_format($row->TotalHours, 0, '.', ',');
                }
            })
            ->escapeColumns('Action')->make(true);
        }
    }
    public function reloadClientCareCoordinatorSchedulesBuiltAuth(Request $request) {
        if($request->input("type") == 'clients_without_auth') {
            return json_encode(["counter"=>H::getHelper('ClientCareCoordinatorSchedulesBuiltAuth')
            ->getClientCareCoordinatorSchedulesBuiltClientWithoutAuth
             ($request,1),"type"=>$request->input("type")]);
        } else {
            return json_encode(["counter"=>H::getHelper('ClientCareCoordinatorSchedulesBuiltAuth')
            ->getClientCareCoordinatorSchedulesBuiltAuth
             ($request,1),"type"=>$request->input("type")]);
        }
    }

    public function getScheduleCorrected(Request $request) {
        $requestData = $request->all();
        return view('ccc.schedulecorrectedconfirmation',['requestData' => $requestData]);
    }
    public function saveScheduleCorrected(Request $request) {
        try {
            if ($request->input('id') != '') {
                if ($request->input('bucket') == 'zero_rates') {
                    DB::table("mssql_schedulemaster")->where("id", $request->input('id'))
                    ->update([
                    'zero_rate_fixed_by' => Auth::user()->id,
                    'zero_rate_fixed_at' => date('Y-m-d H:i:s')]);
                    $patientId = DB::table('mssql_schedulemaster')
                    ->join('mssql_patients', 'mssql_schedulemaster.PatientID', '=', 'mssql_patients.PatientID')
                    ->where('mssql_schedulemaster.id', $request->input('id'))
                    ->value('mssql_patients.id');
                    if ($patientId) {
                        H::getHelper('ActivityNotes')->setActivityNotesFromConsole([
                            'entity' => 'client',
                            'entity_id' => $patientId,
                            'type' => 'zero_rate_corrected',
                            'notes' => Auth::user()->name . ' marked the zero rate as corrected in KanTime',
                        ]);
                    }
                }else {
                    DB::table("mssql_patients")->where("id", $request->input('id'))
                ->update(['auth_corrected_by' => Auth::user()->id,'auth_corrected_at' => date('Y-m-d H:i:s')]);
                }
                return response()->json(["success" => true]);
            } else {
                return response()->json(['status' => false]);
            }
        }catch(\Exception $e){
            return response()->json(['status' => false]);
        }
    }

    public function getZeroRatesData(Request $request) {
        $data = H::getHelper("ClientCareCoordinatorCoverage")
        ->getZeroRatesData($request);
        return Datatables::of($data)->setRowAttr([
            'class' => function($row) {
                return !empty($row->zero_rate_marked_corrected) ? 'yellowexception' : '';
            },
        ])
        ->addColumn('Action', function ($row) use($request){
            return '<a href="javascript:void(0)" title = "Resolve Zero Rate"
            onclick="getScheduleCorrected('.$row->id.',\''.$request->input("type").'\')">
                <i class="fa fa-check-square-o" aria-hidden="true"></i></a> | <a href="javascript:void(0)"
                onclick=\'getClientDetails("'. $row->aoc_patient_id . '")\'>
                <i class="fa fa-eye" aria-hidden="true"></i></a>';
        })->editColumn('PlannedStart', function ($row) {
            if(!empty($row->PlannedStart)){
                return [
                    'display' => e(H::getFormatedDateTime($row->PlannedStart)),
                    'timestamp' => strtotime($row->PlannedStart)
                ];
            } else {
                return [
                    'display' => '',
                    'timestamp' => ''
                ];
            }
        })->escapeColumns('Action')->make(true);
    }
    public function reloadZeroRatesData(Request $request) {
        return json_encode(["counter"=>H::getHelper('ClientCareCoordinatorCoverage')
        ->getZeroRatesData
         ($request,1),"type"=>$request->input("type")]);
    }

    public function getCaseLoadData(Request $request) {
        $data = H::getHelper("ClientCareCoordinatorCoverage")
        ->getCaseLoadData($request);
        return Datatables::of($data)
        ->addColumn('select', function ($row){
            $checkbox = '';
            $checkbox = '<input type="checkbox" name="checkbox-case_load[]" class="form-input td select-all-case_load" value="' . $row->id . '" />';
            return $checkbox;
        })
        ->editColumn('onnth', function ($row){
            if($row->onnth) {
                return 'Yes';
            } else {
                return 'No';
            }
        })
        ->addColumn('Action', function ($row) use($request){
            return '<a href="javascript:void(0)"
                onclick=\'getClientDetails("'. $row->id . '")\'>
                <i class="fa fa-user" aria-hidden="true"></i></a>';
        })->escapeColumns('Action')->make(true);
    }
    public function reloadCaseLoadData(Request $request) {
        return json_encode(["counter"=>H::getHelper('ClientCareCoordinatorCoverage')
        ->getCaseLoadData($request,1),"type"=>$request->input("type")]);
    }

    public function getActiveCoverageData(Request $request) {
        $data = H::getHelper("ClientCareCoordinatorCoverage")
        ->getActiveCoverageData($request);
        return Datatables::of($data)
        ->editColumn('clinician_name', function ($row) {
            if (empty(trim($row->clinician_name))) {
                return $row->candidate_name;
            } else {
                return $row->clinician_name;
            }
        })
        ->editColumn('activecoverage_outcome', function ($row) {
            if (empty(trim($row->activecoverage_outcome))) {
                if($row->live_ipea_status!='Ready to work') {
                    return '';
                } else {
                    return $row->live_ipea_status;
                }
                
            } else {
                
                return $row->activecoverage_outcome;
            }
        })
        ->editColumn('days_available', function ($row) {
            $dates = explode(',', $row->days_available);
            $today = date('Y-m-d');
            $future_dates = array_filter($dates, fn($d) => date("Y-m-d", strtotime($d)) >= $today);
            return implode(',', $future_dates);
        })
        ->addColumn('Action', function ($row) use($request){
            $calendarIcon = '<a href="javascript:void(0);" onclick=\'showActiveCoverageOutcome("'.$row->id.
                            '", "active_coverage")\'><i class="fa fa-calendar"></i></a>';
            $clinicianIcon = '<a href="javascript:void(0)"
                onclick=\'getClinicianProfile("'. $row->aoc_provider_id . '")\'>
                <i class="fa fa-user" aria-hidden="true"></i></a>';

            return $calendarIcon.' | '.$clinicianIcon;
            
        })->escapeColumns('Action')->make(true);
    }
    public function reloadActiveCoverageData(Request $request) {
        return json_encode(["counter"=>H::getHelper('ClientCareCoordinatorCoverage')
        ->getActiveCoverageData($request,1),"type"=>$request->input("type")]);
    }

    public function getActiveCoverageOutcomeForm(Request $request) {
        $data = $request->all();
        $clinicianDetails = DB::table("mssql_providers")
        ->select("mssql_providers.*","mssql_recruitertracker.days_available","mssql_recruitertracker.shift_type","mssql_employmentstatus.EmploymentStatus","mssql_facilities.FacilityName")
        ->leftJoin("mssql_recruitertracker","mssql_recruitertracker.aoc_provider_id","=","mssql_providers.id")
        ->leftJoin("mssql_facilities","mssql_facilities.FacilityID","=","mssql_providers.FacilityID")
        ->leftJoin("mssql_employmentstatus","mssql_employmentstatus.EmploymentStatusID","=","mssql_providers.EmploymentStatusID")
        ->where("mssql_recruitertracker.id",$data['id'])->first();
       
        return view('ccc.activeCoverageOutcomeForm', [
            "clinicianDetails" => $clinicianDetails,
            'trackerId'=>$data['id']
        ]);
    }

    public function saveActiveCoverageOutcomeForm(Request $request) {
        try {
            $requestData = $request->all();
        
            $recruiterTracker = MssqlRecruitertracker::select("*")->where("id",$requestData['trackerId'])->first();
            if($requestData['outcome']=='Attempted to contact') {
                $clinicianDetails = MssqlProviders::select('mssql_providers.*')->where('mssql_providers.id',$recruiterTracker->aoc_provider_id)->first();
                $clinicianName = $clinicianDetails->fullName;

                $attempt = $recruiterTracker->activecoverage_attempt+1;
                $data = ["activecoverage_outcome"=>"Attempt ".$attempt,'activecoverage_attempt'=>$attempt];
                MssqlRecruitertracker::where("id",$requestData['trackerId'])->update($data);
                if(is_array($requestData['result'])) {
                    $result = implode(" and ",$requestData['result']);
                } else {
                    $result = $requestData['result'];
                }
                $noteText = Auth::user()->name.' made attempt #'.$attempt.' to contact '.$clinicianName.' for active coverage.  <br> Comment :'.$requestData['activecoverage_comment'];
                
                $noteData = [];
                $noteData['entity'] = 'clinician';
                $noteData['entity_id'] = $recruiterTracker->aoc_provider_id;
                $noteData['notes'] =$noteText;

                H::getHelper("ActivityNotes")->setActivityNotes($noteData);
            } else if($requestData['outcome']=='Document call') {
                
                if(is_array($requestData['shift_type'])) {
                    $shift_type = implode(",",$requestData['shift_type']);
                } else {
                    $shift_type =$requestData['shift_type'];
                }
                $dates = array_map(function($value){
                    return date("Y-m-d",strtotime($value));
                },explode(",",$requestData['days_available_hidden']));
                $data = [
                    "shift_type"=>$shift_type,
                    "days_available"=>implode(",",$dates),
                    "activecoverage_outcome"=>"Completed"
                ];


                $noteText ='Next steps preferred shift type was changed to '.$shift_type.' by '.Auth::user()->name.'<br/> Next steps days available '.implode(",",$dates).' selected by '.Auth::user()->name;
                
                $noteData = [];
                $noteData['entity'] = 'clinician';
                $noteData['entity_id'] = $recruiterTracker->aoc_provider_id;
                $noteData['notes'] =$noteText;

                H::getHelper("ActivityNotes")->setActivityNotes($noteData);


                if ($requestData['bucket'] == 'active_coverage_exception') {
                    $data['live_ipea_status'] = 'Ready to work';
                    $data['nurse_status'] = 2;
                    $data['activecoverage_attempt'] = 0;
                }
                MssqlRecruitertracker::where("id",$requestData['trackerId'])->update($data);


            } else if($requestData['outcome']=='Escalate to Client Care Manager') {

                $clinicianDetails = MssqlProviders::select('mssql_providers.*')->where('mssql_providers.id',$recruiterTracker->aoc_provider_id)->first();
                $clinicianName = $clinicianDetails->fullName;

                $data = ["activecoverage_outcome"=>'Escalated to CCM'];
                MssqlRecruitertracker::where("id",$requestData['trackerId'])->update($data);

            
                $noteText = Auth::user()->name.' escalated '.$clinicianName.'  to client care manager.';

                $noteData = [];
                $noteData['entity'] = 'clinician';
                $noteData['entity_id'] = $recruiterTracker->aoc_provider_id;
                $noteData['notes'] =$noteText;
            } else if($requestData['outcome']=='Status Change Request'){
                $clinicianDetails = MssqlProviders::select('mssql_providers.*')->where('mssql_providers.id',$recruiterTracker->aoc_provider_id)->first();
                $clinicianName = $clinicianDetails->fullName;
                $data = [
                    
                    "activecoverage_outcome"=>$requestData['outcome']
                ];
                MssqlRecruitertracker::where("id",$requestData['trackerId'])->update($data);

                $oldStatusId = MssqlProviders::where('id', $recruiterTracker->aoc_provider_id)->value('EmploymentStatusID');
                $provider = MssqlProviders::select(DB::raw("(SELECT users.id from users
                left join users_has_roles on users.id = users_has_roles.user_id
                left join roles on users_has_roles.role_id = roles.id
                where roles.name='Orientation Coordinator'
                and users.status='1'
                and find_in_set(mssql_facilities.branchid, users.branchid) limit 1) as oc"))
                ->leftJoin("mssql_facilities", function($query){
                    $query->on('mssql_providers.FacilityID', '=', 'mssql_facilities.FacilityID');
                })
                ->where('mssql_providers.id', $recruiterTracker->aoc_provider_id)->first();
                $oc = $provider->oc;
                DB::table('mssql_providers')->where('id', $recruiterTracker->aoc_provider_id)->update([
                    'requested_status' => $requestData['src_requested_status'],
                    'request_outcome' => 'Requested',
                    'request_reason' => $requestData['src_reason'],
                    'request_date' => (!empty($requestData['src_date_requested']))
                        ? date('Y-m-d', strtotime($requestData['src_date_requested'])) : '',
                    'request_by' => Auth::user()->id,
                    'scr_original_status_id' => $oldStatusId,
                    'request_oc'=>$oc,
                    'change_request_employment' => isset($requestData['employment_type'])
                        ? $requestData['employment_type'] : 0,
                ]);


                $noteText ='New Status Change Request for '.$clinicianName;
                $noteText.="<br>Date request was submitted: ".$requestData['src_date_requested'];
                $noteText.="<br>".$requestData['src_requested_status'];
                $noteText.="<br>".$requestData['src_reason'];

                $noteData = [];
                $noteData['entity'] = 'clinician';
                $noteData['entity_id'] = $recruiterTracker->aoc_provider_id;
                $noteData['notes'] =$noteText;
                
            }
            
            return response()->json(["status" => true]);
        } catch (\Exception $e){
            return response()->json(["status" => false,"errMsg"=>"There is something wrong with saving the data"]);
        }
    }

    
}
