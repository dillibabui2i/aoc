<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\H;
use DataTables;
use App\MssqlPatients;
use App\MssqlProviders;
use App\Candidates;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\LD;
use App\Helpers\Notifications;

class SchoolSetupController extends Controller
{
    public function getClientSchoolSetup(Request $request) {
        if($request->input("type") == 'ccc_client_need_school_setup') {
            $data = H::getHelper("SchoolSetup")
            ->getClientSchoolSetup($request);
            return Datatables::of($data)
            ->addColumn('Action', function ($row){
                $btn = '';
                $btn .= '<a href="javascript:void(0);"
                onclick="loadSchoolSetupForm(\'' . $row->id . '\')"
                title="School Set Up Request form">
                <i class="fa fa-plus" aria-hidden="true"></i></a>';
                    $btn .= ' | <a href="javascript:void(0);"
                    onclick="loadNoNeedSchoolSetUp(\'' . $row->id . '\')"
                    title="Cancel school set up"><i class="fa fa-times textcolorred" aria-hidden="true"></i></a>';
                return $btn;
            })->escapeColumns('Action')->make(true);
        } elseif($request->input("type") == 'ccc_pending_school_setups_request') {
            $data = H::getHelper("SchoolSetup")
            ->getPendingSchoolSetupRequestForCcDashboard($request);
            if(LD::checkFeatureFlag('add-requested-date-column-to-school-set-ups-buckets')){
                return Datatables::of($data)
                ->addColumn('Action', function ($row){
                    $btn = '';
                    $btn .= '<a href="javascript:void(0);"
                    onclick="getClientDetails(\'' . $row->aoc_patient_id . '\')"
                    title="View client profile">
                    <i class="fa fa-user" aria-hidden="true"></i></a>';
                        $btn .= ' | <a href="javascript:void(0);"
                        onclick="getClinicianProfile(\'' . $row->aoc_provider_id . '\')"
                        title="View clinician profile"><i class="fa fa-user-md" aria-hidden="true"></i></a>';
                    if(LD::checkFeatureFlag('make-ssu-form-view-only')){
                        if(!empty($row->aoc_patient_id) && !empty($row->id)){
                            $btn .= ' | <a href="javascript:void(0);" 
                                onclick="loadSchoolSetupForm(\'' . $row->aoc_patient_id . '\', \'' . $row->id . '\', \'view\')">
                                <i class="fa fa-paperclip"></i>
                            </a>';
                        }
                    }
                    if($row->status == 'Completed') {
                        $btn .= ' | <a href="javascript:void(0);" title="Request Completed"
                        onclick="loadSchoolSetupCccAcknowledged(\'' . $row->id . '\');">
                        <i class="fa fa-check-square-o"></i></a>';
                    }
                    if(LD::checkFeatureFlag('new-ssu-form-change')){
                        if($row->status == 'Cancelled') {
                            $btn .= ' | <a href="javascript:void(0)" onclick="markSsuRequest('.$row->id.')" >
                            <i class="fa fa-check-square-o" aria-hidden="true" title = "Request Completed"></i></a>';
                        }
                    }
                    return $btn;
                })->editColumn('start_date', function ($row) {
                    if(isset($row->start_date) && $row->start_date != '00/00/0000'){
                        return [
                            'display' => $row->start_date,
                            'timestamp' => strtotime($row->start_date)
                        ];
                    } else {
                        return [
                            'display' => '',
                            'timestamp' => ''
                        ];
                    }
                })->editColumn('requested_date', function ($row) {
                    if(isset($row->requested_date) && $row->requested_date != '00/00/0000'){
                        return [
                            'display' => $row->requested_date,
                            'timestamp' => strtotime($row->requested_date)
                        ];
                    } else {
                        return [
                            'display' => '',
                            'timestamp' => ''
                        ];
                    }
                })->escapeColumns('Action')->make(true);
            }else{
                return Datatables::of($data)
                ->addColumn('Action', function ($row){
                    $btn = '';
                    $btn .= '<a href="javascript:void(0);"
                    onclick="getClientDetails(\'' . $row->aoc_patient_id . '\')"
                    title="View client profile">
                    <i class="fa fa-user" aria-hidden="true"></i></a>';
                        $btn .= ' | <a href="javascript:void(0);"
                        onclick="getClinicianProfile(\'' . $row->aoc_provider_id . '\')"
                        title="View clinician profile"><i class="fa fa-user-md" aria-hidden="true"></i></a>';
                    if($row->status == 'Completed') {
                        $btn .= ' | <a href="javascript:void(0);" title="Request Completed"
                        onclick="loadSchoolSetupCccAcknowledged(\'' . $row->id . '\');">
                        <i class="fa fa-check-square-o"></i></a>';
                    }
                    if(LD::checkFeatureFlag('new-ssu-form-change')){
                        if($row->status == 'Cancelled') {
                            $btn .= ' | <a href="javascript:void(0)" onclick="markSsuRequest('.$row->id.')" >
                            <i class="fa fa-check-square-o" aria-hidden="true" title = "Request Completed"></i></a>';
                        }
                    }
                    return $btn;
                })->editColumn('start_date', function ($row) {
                    if(isset($row->start_date) && $row->start_date != '00/00/0000'){
                        return [
                            'display' => $row->start_date,
                            'timestamp' => strtotime($row->start_date)
                        ];
                    } else {
                        return [
                            'display' => '',
                            'timestamp' => ''
                        ];
                    }
                })->escapeColumns('Action')->make(true);
            }
        }
    }
    public function reloadClientSchoolSetup(Request $request) {
        if($request->input("type") == 'ccc_client_need_school_setup') {
            return json_encode(["counter"=>H::getHelper('SchoolSetup')
            ->getClientSchoolSetup($request,1),"type"=>$request->input("type")]);
        } elseif($request->input("type") == 'ccc_pending_school_setups_request') {
            return json_encode(["counter"=>H::getHelper('SchoolSetup')
            ->getPendingSchoolSetupRequestForCcDashboard($request,1),"type"=>$request->input("type")]);
        }
    }

    public function showAll(Request $request) {
        if ($request->ajax()) {
            $data = H::getHelper("SchoolSetup")
                    ->getSchoolSetUpRequest("showall",
                    $request->input("filters"),0,$request);
            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($data['totalData']),
                'recordsFiltered' => intval($data['totalFiltered']),
                'data'            => $data['data'],
            ]);
        } else {
            $columns = H::getHelper('SchoolSetup')->getShowAllGridColumns();
            return view('schoolSetUp.grid', ["columns" => $columns]);
        }
    }

    public function export(Request $request)
    {
        if(LD::checkFeatureFlag('add-requested-date-column-to-school-set-ups-buckets')){
            $dataTableFields = [
                'Client_Coordinator' => 'Client Care Coordinator',
                'FacilityName' => 'Branch Location',
                'clinicianName' => 'Clinician',
                'clientName' => 'Client',
                'SchoolName' => 'School Name',
                'SchoolDistrict' => 'School District',
                'requested_date'=>'School Set Up Requested Date',
                'start_date' => 'Start Date',
                'status' => 'School Set Up Status'
            ];
        }else{
            $dataTableFields = [
                'Client_Coordinator' => 'Client Care Coordinator',
                'FacilityName' => 'Branch Location',
                'clinicianName' => 'Clinician',
                'clientName' => 'Client',
                'SchoolName' => 'School Name',
                'SchoolDistrict' => 'School District',
                'start_date' => 'Start Date',
                'status' => 'School Set Up Status'
            ];
        }

        $data = H::getHelper("SchoolSetup")
                ->getSchoolSetUpRequest("export",
                $request->input("filters"),0,$request);
        $data = json_decode(json_encode($data['data']), true);
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=SchoolSetUpsData.csv",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];
        $callback = function () use ($data, $dataTableFields) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_values($dataTableFields));
            foreach ($data as $tableData) {
                $csvData = [];
                foreach ($dataTableFields as $key => $val) {
                    if ($key == 'start_date' && $tableData[$key] == '00/00/0000') {
                        $csvData[] = '';
                    } else {
                        $csvData[] = $tableData[$key];
                    }
                }
                fputcsv($file, $csvData);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function schoolSetupForm(Request $request)
    {
        $patientsDetails =[];
        $ref = 'sidebar';
        if(!empty($request->input('aoc_patient_id'))) {
            $ref = 'bucket';
            $patientsDetails = MssqlPatients::find($request->input('aoc_patient_id'));
        }
        if ($request->input('mode') != 'add') {
            $ref = 'bucket';
            $schoolSetupData = DB::table("school_setup_requests")
                ->where('id', $request->input('school_setup_id'))
                ->first();
            $providerData = DB::table("mssql_providers")
                ->where('id', $schoolSetupData->aoc_provider_id)
                ->first();
            return view('schoolSetUp.form', [
                'patientsDetails' => $patientsDetails,
                'schoolSetupData' => $schoolSetupData,
                'providerData' => $providerData,
                'mode' => $request->input('mode'),
                'ref' => $ref
            ]);
        } else {
            $clinicianDetails =[];
            if(!empty($request->input('aoc_patient_id'))) {
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
            }
            if(LD::checkFeatureFlag('add-save-button-in-ssu-form-from-bucket')){
                $schoolSetupData = DB::table('school_setup_requests')
                ->where('aoc_patient_id', $request->input('aoc_patient_id'))
                ->where('created_by', auth()->user()->id)->where('save_flag', '1')->first();
                return view('schoolSetUp.form', [
                    'patientsDetails' => $patientsDetails,
                    'clinicianDetails' => $clinicianDetails,
                    'mode' => $request->input('mode'),
                    'ref' => $ref,
                    'schoolSetupData' => $schoolSetupData
                ]);
            }else{
                return view('schoolSetUp.form', [
                    'patientsDetails' => $patientsDetails,
                    'clinicianDetails' => $clinicianDetails,
                    'mode' => $request->input('mode'),
                    'ref' => $ref
                ]);
            }

        }
    }

    public function saveForm(Request $request)
    {
        $user = H::getLoggedInUser();
        try {
            $data = $request->all();
            $mode = $data['mode'];
            $ref = $data['ref'];
            $type = isset($data['buttonType']) ? $data['buttonType'] : NULL;
            unset($data['_token']);
            unset($data['clinican']);
            unset($data['mode']);
            unset($data['ref']);
            if(isset($data['buttonType'])){
                unset($data['buttonType']);
            }

            $data['start_date'] = isset($data['start_date']) ? H::getMssqlDate($data['start_date']) : '';
            if ($mode == 'add') {
                $data['created_at'] = date('Y-m-d');
                $data['created_by'] = $user['id'];
            } else {
                $data['updated_at'] = date('Y-m-d');
                $data['updated_by'] = $user['id'];
            }
            H::ExcelLog('mode: ' . $mode,'saveSchoolSetUp.log');
            if ($mode == 'add') {
                $excludeStatus = ['Cancelled','Completed'];
                $existingProviderName = $nonExistingProviderName ='';
                $alreadyExistSsu = $nonExistSsu = 0;
                $selectedProviders = $data['aoc_provider_id'];
                foreach($selectedProviders as $provider_id) {
                    H::ExcelLog('provider_id: ' . $provider_id,'saveSchoolSetUp.log');
                    if(LD::checkFeatureFlag('add-save-button-in-ssu-form-from-bucket')){
                        $schoolSetupExist = DB::table("school_setup_requests")
                        ->where('aoc_provider_id',$provider_id)
                        ->where('aoc_patient_id',$data['aoc_patient_id'])
                        ->whereNotIn('status',$excludeStatus)
                        ->where('save_flag', '=', '0')
                        ->first();
                    }else{
                        $schoolSetupExist = DB::table("school_setup_requests")
                        ->where('aoc_provider_id',$provider_id)
                        ->where('aoc_patient_id',$data['aoc_patient_id'])
                        ->whereNotIn('status',$excludeStatus)
                        ->first();
                    }
                    $providerDetails = DB::table("mssql_providers")
                        ->select('fullName')
                        ->where('id',$provider_id)->first();
                    if(empty($schoolSetupExist)) {
                        $nonExistSsu = 1;
                        $itHave = 0;
                        if($type == 'save'){
                            $data['save_flag'] = '1';
                            $schoolData = DB::table('school_setup_requests')->where('aoc_patient_id', $data['aoc_patient_id'])->where('save_flag', '1')->first();
                            if(!empty($schoolData)){
                                $itHave = 1;
                            }

                        }
                        $nonExistingProviderName = $nonExistingProviderName.'<br>'.$providerDetails->fullName;
                        H::ExcelLog('No schoolSetup Not Exist for provider_id =' . $provider_id .'
                        and patient_id = '.$data['aoc_patient_id'],'saveSchoolSetUp.log');
                        $data['aoc_provider_id'] = $provider_id;
                        if($itHave == 1){
                            DB::table("school_setup_requests")->where('id', $schoolData->id)->update($data);
                        }else{
                            DB::table("school_setup_requests")->insert($data);
                        }
                    } else {
                        H::ExcelLog('Yes schoolSetup Exist for provider_id =' . $provider_id .'
                        and patient_id = '.$data['aoc_patient_id'],'saveSchoolSetUp.log');
                        $alreadyExistSsu = 1;
                        $existingProviderName = $existingProviderName.'<br>'.$providerDetails->fullName;
                    }
                }
                if($type != 'save'){
                    DB::table("mssql_patients")
                    ->where('id', $data['aoc_patient_id'])
                    ->update(['needs_school_set_up' => 0]);
                }
                H::ExcelLog('alreadyExistSsu =' . $alreadyExistSsu,'saveSchoolSetUp.log');
                $msg = '';
                if($nonExistSsu == 1) {
                    $msg = $msg.'<div class="alert alert-success col-md-12" role="alert">
                    School Set Up request has been created for following provider(s)
                    '.$nonExistingProviderName.'</div>';
                }
                if($alreadyExistSsu == 1) {
                    $msg = $msg.'<div class="alert alert-danger col-md-12" role="alert">
                    School Set UP request has not been created for following provider(s),
                    because an active school set up already exists '.$existingProviderName.'</div>';
                }
            } else {
                $data['aoc_provider_id'] = $data['aoc_provider_id'][0];
                H::ExcelLog('UPDATING provider_id: ' . $data['aoc_provider_id'],'saveSchoolSetUp.log');
                DB::table("school_setup_requests")->where('id', $data['id'])->update($data);
                $msg = '<div class="alert alert-success col-md-12" role="alert">
                    School Set Up information saved successfully.
                    </div>';
            }
            return response()->json([
                "status" => true,
                "msg" => $msg
            ]);
        } catch (\Exception $e) {
            H::ExcelLog('Exception =' . $e->getMessage(),'saveSchoolSetUp.log');
            return response()->json([
                'status' => false,
                "msg" => '<div class="alert alert-danger col-md-12" role="alert">
                    Error while saving school set up information.
                    </div>'
            ]);
        }
    }

    public function reloadOpenSchoolSetupRequest(Request $request){
        return json_encode(["counter"=>H::getHelper("SchoolSetup")->getSchoolSetUpRequest("dashboard"
        ,$request->input("filters"),1,$request),"type"=>$request->input("type")]);
    }

    public function getSchoolSetUpRequest(Request $request){
        $data = H::getHelper("SchoolSetup")->getSchoolSetUpRequest("dashboard",
        $request->input("filters"),0,$request);
        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => intval($data['totalData']),
            'recordsFiltered' => intval($data['totalFiltered']),
            'data'            => $data['data'],
        ]);
    }

    public function noNeedSchoolSetupForm(Request $request)
    {
        return view('schoolSetUp.noNeedSchoolSetupForm',
        ['aoc_patient_id' => $request->input('aoc_patient_id')]);
    }

    public function saveNoNeedSchoolSetupForm(Request $request)
    {
        $user = H::getLoggedInUser();
        try {
            $patientsDetails = MssqlPatients::find($request->input('aoc_patient_id'));
            $data = $request->all();
            DB::table("mssql_patients")
            ->where('id', $data['aoc_patient_id'])
            ->update(['needs_school_set_up' => 0]);
            $noteData['entity'] = 'client';
            $noteData['entity_id'] = $data['aoc_patient_id'];
            $noteData['notes'] = $patientsDetails->Patient_FirstName.' '.$patientsDetails->Patient_LastName.' was
            set to not need a school set up by ' .$user['name'];

            H::getHelper("ActivityNotes")->setActivityNotes($noteData);
            return response()->json([
                "status" => true,
                "msg" => '<div class="alert alert-success col-md-12" role="alert">
                    School Set Up information saved successfully.
                    </div>'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                "msg" => '<div class="alert alert-danger col-md-12" role="alert">
                    Error while saving no need school set up information.
                    </div>'
            ]);
        }
    }

    public function showOpenSchoolSetUpNextStepForm(Request $request) {
        try {
            $candidateDetails = $clinicianDetails = [];
            if ($request->input('id') != '') {
                $schoolSetupDetail = DB::table('school_setup_requests')->select(
                    'school_setup_requests.*')
                    ->where('school_setup_requests.id',$request->input('id'))->first();
                if(!empty($schoolSetupDetail->aoc_provider_id)) {
                    $clinicianDetails = MssqlProviders::find($schoolSetupDetail->aoc_provider_id);
                } elseif(!empty($schoolSetupDetail->aoc_candidate_id)) {
                    $candidateDetails = Candidates::find($schoolSetupDetail->aoc_candidate_id);
                }
                $patientsDetails = MssqlPatients::find($schoolSetupDetail->aoc_patient_id);
                return view('schoolSetUp.openSchoolSetUpRequestOutcome', ['schoolSetupDetail'=>$schoolSetupDetail,
                'clinicianDetails'=>$clinicianDetails,
                'patientsDetails'=>$patientsDetails,
                'candidateDetails'=>$candidateDetails]);
            }
        } catch (\Exception $e) {
            return false;
        }
    }
    public function saveSchoolSetupOutcomeInfo(Request $request)
    {
        $reqData = $request->all();
        $updateArray = [];
        $notes = '';
        try {
            //code...
            if (isset($reqData['name']) && $reqData['name'] != ''){
                if($reqData['name'] == 'pending_nurse_reason[]' && !empty($reqData['value'])) {
                    $updateArray['clearance_packet_pending_nurse_reason'] = implode(', ',$reqData['value']);
                    $notes = 'Clearance Packet was set to Pending Nurse
                    [optional : '.$updateArray['clearance_packet_pending_nurse_reason'].'] by ' .
                    Auth::user()->name;
                }
                if($reqData['name'] == 'school_setup_status_cancelled_reason[]'  && !empty($reqData['value'])) {
                    $updateArray['status_cancelled_reason'] = implode(', ',$reqData['value']);
                    $notes = 'School Set Up Status to Cancelled
                    [optional : '.$updateArray['status_cancelled_reason'].'] by ' .
                    Auth::user()->name;
                }
                if($reqData['name']=='school_clearance_packet'){
                    $clearanceArr = [
                    'None' => 'None',
                    'Pending school information from branch' => 'Pending Branch',
                    'Pending school sending packet' => 'Pending School',
                    'Pending answer if nurse can attend school' => 'Pending School',
                    'Pending Nurse' => 'Pending Nurse',
                    'Pending Caregiver' => 'Pending Caregiver',
                    'Pending Background' => 'Pending Background',
                    'Pending school approval' => 'Pending School',
                    'Pending nurse receiving badge' => 'Pending Nurse',
                    'Completed' => 'Completed'];
                    //$clearanceArr
                    $updateArray['live_status'] = $clearanceArr[$reqData['value']];
                    $updateArray['clearance_packet'] = $reqData['value'];
                    $notes = 'Clearance Packet was set to '.
                    $reqData['value'].' by ' .
                    Auth::user()->name;
                }elseif($reqData['name']=='finger_printing'){
                    $updateArray['finger_printing'] = $reqData['value'];
                    $notes = 'Finger Printing was set to '.
                    $reqData['value'].' by ' .
                    Auth::user()->name;
                }elseif($reqData['name']=='school_setup_status'){
                    $updateArray['school_setup_status'] = $updateArray['status'] = $reqData['value'];
                    if(LD::checkFeatureFlag('send-notification-when-ssu-completed-or-canceled')){
                        if($reqData['value'] == 'Completed' || $reqData['value'] == 'Cancelled') {
                            $updateArray['live_status'] = $reqData['value'];
                            $school_data = DB::table('school_setup_requests')->where('id', $reqData['school_setup_id'])->first();
                            $sendUser[] = $school_data->assigned_ccc;
                            if(!empty($school_data->tracker_id)){
                                $sendUser[] = H::getRecruterIdFromTrackerId($school_data->tracker_id);
                            }
                            foreach($sendUser as $user) {
                                $data['user_id'] = $user;
                                $data['title'] = auth()->user()->name . " marked school setup as " . $reqData['value'] .
                                " for " . H::getPatientNameById($school_data->aoc_patient_id) .
                                " with clinician " .H::getProviderNameById($school_data).".";
                                $data['type'] = 'school_setup_request';
                                $data['related_item'] = $reqData['school_setup_id'];
                                $data['notes'] = '';
                                Notifications::addNotification($data);
                            }
                        }
                    }
                    $notes = 'School Set Up Status was set to '.
                    $reqData['value'].' by ' .
                    Auth::user()->name;
                }
                if(!empty($updateArray)) {
                    DB::table('school_setup_requests')->where('id', $reqData['school_setup_id'])->update($updateArray);
                }
                if($notes != '') {
                    H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'school-setup',
                    'entity_id' => $reqData['school_setup_id'],
                    'type' => 'school',
                    'notes' => $notes]);
                }
                return response()->json(['status'=>true]);
            } else {
                return response()->json(['status'=>false]);
            }
        } catch (\Throwable $th) {
            return response()->json(['status'=>false,'message' => $th->getMessage()]);
        }
    }
    public function assignedCcc(Request $request)
    {
        $reqData = $request->all();
        if (!empty($reqData['ccc_id']) && !empty($reqData['ids'])) {
            $selectedIds = explode(",",$reqData['ids']);
            DB::table('school_setup_requests')
            ->whereIn('id', $selectedIds)
            ->update(['assigned_ccc' => $reqData['ccc_id']]);
            return response()->json(['success' => 1, 'message' => 'Infomation saved successfully']);
        } else {
            return response()->json(['success' => 0, 'message' => 'Something went wrong please try after some time.']);
        }
    }

    public function schoolSetupCcAcknowledgedForm(Request $request)
    {
        return view('schoolSetUp.cccAcknowledgedForm',
        ['school_setup_id' => $request->input('school_setup_id')]);
    }

    public function saveSchoolSetupCcAcknowledgedForm(Request $request)
    {
        $reqData = $request->all();
        $updateArray = [];
        try {
            $updateArray['ccc_acknowledged'] = 1;
            DB::table('school_setup_requests')
            ->where('id', $reqData['school_setup_id'])
            ->update($updateArray);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                "msg" => '<div class="alert alert-danger col-md-12" role="alert">
                    Error while completing request.
                    </div>'
            ]);
        }
    }

    public function markSsuRequest(Request $request){
        $id = $request->id;
        if(!empty($id)){
            $data = [
                'ssu_marked' => 1,
            ];
            DB::table('school_setup_requests')->where('id', $id)->update($data);
            return response()->json(['status'=>true]);
        }
    }
}
