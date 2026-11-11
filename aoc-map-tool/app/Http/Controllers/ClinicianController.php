<?php
namespace App\Http\Controllers;

use App\Helpers\H;
use App\MssqlProviders;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Notifications;
use App\Notes;
use DataTables;
use App\Helpers\LD;
use Illuminate\View\View;
use PDF as PDFGenerator;
use App\Notification;
use App\CustomFields;

class ClinicianController extends Controller
{
    public function getDetails(Request $request)
    {
        if (!$request->input('id')) {
            return response()->noContent(Response::HTTP_BAD_REQUEST);
        }

        try {
            $clinicianDetails = MssqlProviders::find($request->input('id'));
            if (empty($clinicianDetails)) {
                return response()->noContent(Response::HTTP_NOT_FOUND);
            }

            if (!empty($clinicianDetails->fullName)) {
                $clinicianDetails->fullName = $clinicianDetails->FirstName . ' ' . $clinicianDetails->LastName;
            }

            $careTeam = [];
            $careTeamIds = [
                'rdod_id' => 'Regional Director of Operations',
                'dbo_id' => 'Director of Branch Operations',
                'ccc_id' => 'Client Care Coordinator',
                'ic_id' => 'Intake Coordinator',
                'r_id' => 'Recruiter',
                'ccm_id' => 'Clinical Care Manager',
            ];

            if (LD::checkFeatureFlag('prn-changes-add-clinician')) {
                $careTeamIds['cc_id'] = 'Coverage Coordinator';
            }

            //Recruiting Activity Tab Data
            $recruitingActivity = DB::table('recruiting_schedule')
                ->leftJoin('schedule_types', 'recruiting_schedule.type_id', '=', 'schedule_types.id')
                ->leftJoin('mssql_patients', 'recruiting_schedule.aoc_patient_id', '=', 'mssql_patients.id')
                ->select('schedule_types.type as type', 'recruiting_schedule.*',
                    DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS client"))
                ->where('aoc_provider_id', $clinicianDetails->id)
                ->whereDate('appointment_date', '<', date('Y-m-d'))
                ->orderBy('appointment_date', 'desc')
                ->get();

            //Upcoming Appointments Tab Data
            $upcomingAppointments = DB::table('recruiting_schedule')
                ->leftJoin('schedule_types', 'recruiting_schedule.type_id', '=', 'schedule_types.id')
                ->leftJoin('mssql_patients', 'recruiting_schedule.aoc_patient_id', '=', 'mssql_patients.id')
                ->select('schedule_types.type as type', 'recruiting_schedule.*',
                    DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS client"))
                ->where('aoc_provider_id', $clinicianDetails->id)
                ->whereDate('appointment_date', '>=', date('Y-m-d'))
                ->orderBy('appointment_date', 'desc')
                ->get();

            //Renewables Tab Data
            $renewables = DB::table('renewables')
                ->leftJoin('renewables_types', 'renewables.renewables_type', '=', 'renewables_types.renewable_type_id')
                ->select('renewables.*', 'renewables_types.name')
                ->where('renewables.aoc_provider_id', $clinicianDetails->id)
                ->orderBy('renewables.expiration_date')
                ->get();

            //Care Team Data
            $i = 0;
            foreach ($careTeamIds as $k => $v) {
                if ($clinicianDetails->{$k} != '') {
                    $user = User::find($clinicianDetails->{$k});
                    if (!empty($user)) {
                        $careTeam[$i]['designation'] = $v;
                        $careTeam[$i]['profilePic'] = $user->profile_picture;
                        $careTeam[$i]['name'] = $user->name;
                        $i++;
                    }
                }
            }
            $notes = Notification::where('related_item', $request->input('id'))
                ->where('type', 'statusChangeRequest')->get();
            $twilioErrorData = [];
            if (
                LD::checkFeatureFlag('display-twilio-error-on-profile-page') &&
                env('APP_ENV') === 'production'
            ) {
                $candidateReceiversNumber = H::getHelper('MassMessageWidget')
                    ->getFormatPhoneForMessage($clinicianDetails->Mobile);
                $twilioErrorData = DB::table('twilio_error_logs')
                    ->where('phone_number', $candidateReceiversNumber)
                    ->first();
            }

            $customFields = [];
            if (LD::checkFeatureFlag('update-custom-fields-on-clinician-profile')) {
                if (LD::checkFeatureFlag('city-state-custom-fields-on-clinician-profile')) {
                    $customFieldsList = CustomFields::where('is_deleted', 0)
                        ->where('entity', 'clinician')
                        ->whereNotIn('db_field_name', ['aoc_cf_city', 'aoc_cf_state'])
                        ->orderBy('category')
                        ->orderBy('sort')
                        ->get()
                        ->toArray();
                } else {
                    $customFieldsList = CustomFields::where('is_deleted', 0)
                        ->where('entity', 'clinician')
                        ->orderBy('category')
                        ->orderBy('sort')
                        ->get()
                        ->toArray();
                }
                foreach ($customFieldsList as $key => $field) {
                    $category = $field['category'];
                    if (empty($category)) {
                        $category = 'Default';
                    }
                    $customFields[$category][] = view('ManagingCustomFields.customfields', [
                        'field' => $field,
                        'details' => $clinicianDetails,
                        'id' => $clinicianDetails->id,
                    ])->render();
                }
            }

            return view('clinician.details',
                [
                    'clinicianDetails' => $clinicianDetails,
                    'recruitingActivity' => $recruitingActivity,
                    'upcomingAppointments' => $upcomingAppointments,
                    'renewables' => $renewables,
                    'careTeam' => $careTeam,
                    'notes' => $notes,
                    'twilioErrorData' => $twilioErrorData,
                    'customFields' => $customFields,
                ],
            );
        } catch (\Exception $e) {
            Log::channel('clinician')->error($e);

            return response()->noContent(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function listing(Request $request) {
        $columns = [
            ['name' => 'id','label' => 'id', 'visible' => false, 'width' => '0'],
            ['name' => 'ProviderName', 'label' => 'Provider', 'width' => '20%'],
            ['name' => 'ClinicianID', 'label' => 'Clinician ID', 'width' => '10%'],
            ['name' => 'BranchName', 'label' => 'Branch', 'width' => '15%'],
            ['name' => 'EmploymentStatus', 'label' => 'Status', 'width' => '10%'],
            ['name' => 'DisciplineName', 'label' => 'Discipline', 'width' => '10%'],
            ['name' => 'HiredDate','label' => 'Hire Date', 'width' => '10%'],
            ['name' => 'type','label' => 'Type', 'width' => '10%']
        ];
        if ($request->ajax()) {
            $filters = H::getFilterInputArr($request->all());

            $search = $request->input('search.value');
            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')]['name'];
            $dir = $request->input('order.0.dir');

            $q = DB::table('mssql_providers')
                ->leftJoin('mssql_facilities', 'mssql_providers.FacilityID', '=', 'mssql_facilities.FacilityID')
                ->leftJoin('mssql_employmentstatus', 'mssql_providers.EmploymentStatusID', '=', 'mssql_employmentstatus.EmploymentStatusID')
                ->select('mssql_providers.id',
                'mssql_providers.ProviderName',
                'mssql_providers.ClinicianID',
                'mssql_providers.DisciplineName',
                'mssql_providers.HiredDate',
                'mssql_facilities.BranchName as BranchName',
                'mssql_employmentstatus.EmploymentStatus as EmploymentStatus',
                DB::raw("IF(IsClinician = '1', 'Clinician',
                IF(IsOfficeStaff = '1', 'Office', IF(IsClinician = '1'
                && IsOfficeStaff = '1', 'Clinician/Office', 'None')))
                AS type"))->where('mssql_providers.is_deleted', '0');

            $totalData = $q->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $q->whereNested(function($q) use ($search) {
                    $q->where('mssql_providers.ProviderName', 'LIKE', "%{$search}%")
                    ->orWhere('mssql_providers.ClinicianID', 'LIKE', "%{$search}%")
                    ->orWhere('mssql_facilities.BranchName', 'LIKE', "%{$search}%")
                    ->orWhere('mssql_employmentstatus.EmploymentStatus', 'LIKE', "%{$search}%")
                    ->orWhere('mssql_providers.DisciplineName', 'LIKE', "%{$search}%")
                    ->orWhere(DB::raw('DATE_FORMAT(mssql_providers.HiredDate, "' . config('constant')['mysqlsearchdateformat'] . '")'), 'LIKE', "%{$search}%");
                });
                $totalFiltered = $q->count();
            }
            if (!empty($filters)) {
                if (isset($filters['State'])) {
                    $q->where('mssql_providers.State', $filters['State']);
                }
                if (isset($filters['Branch'])) {
                    $q->where('mssql_facilities.id', $filters['Branch']);
                }
                if (isset($filters['Status'])) {
                    $q->where('mssql_providers.EmploymentStatusID', $filters['Status']);
                }
                if (isset($filters['Discipline'])) {
                    $q->where('mssql_providers.DisciplineName', $filters['Discipline']);
                }
                $totalFiltered = $q->count();
            }
            $data = $q->offset($start)->limit($limit)->orderBy($order, $dir)->get();

            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($totalData),
                'recordsFiltered' => intval($totalFiltered),
                'data'            => $this->DataProcess($data),
            ]);
        }
        return view('clinician.grid', ['columns' => $columns]);
    }

    private function DataProcess($data) {
        foreach ($data as $key => $value) {
            $value->HiredDate = H::getFormatedDate($value->HiredDate);
        }
        return $data;
    }

    public function payRateAgreementsList(Request $request) {

        $documentType = [];

        if(H::canPermissions(['view_field_evaluation_document'])) {
            $documentType[] = 'field_eval';
        }

        if(H::canPermissions(['view_payrate_agreeement_document'])) {
            $documentType[] = 'pay_rate';
        }

        if(H::canPermissions(['view_therapy_evaluation_document'])) {
            $documentType[] = 'therapy_eval';
        }

        if(H::canPermissions(['view_office_evaluation_document'])) {
            $documentType[] = 'office_eval';
        }

        if(H::canPermissions(['view_renewable_document'])) {
            $documentType[] = 'renewables';
        }

        $documentType[] = 'offer_accepted';
        $documentType[] = 'neo_attachment';
        $documentType[] = 'therapy_recruiter_phone_screen';
        $documentType[] = 'therapy_clinical_interview';
        $documentType[] = 'therapy_offer_extended';

        $clinicianId = $request->input('clinicianId');
        $q = DB::table('aoc_documents')->select(DB::raw('DATE_FORMAT(created_at,"' . config('constant')['mysqlsearchdatetimeformat'] . '") AS created_at'),'extra_data',DB::raw('DATE_FORMAT(send_date,"' . config('constant')['mysqlsearchdatetimeformat'] . '") AS send_date'),DB::raw('DATE_FORMAT(signed_date,"' . config('constant')['mysqlsearchdatetimeformat'] . '") AS signed_date'),'document_filename','id', 'document_type', 'description')
        ->where(function($q) use($documentType) {
            $q->where('aoc_documents.entity_type', 'provider')
            ->whereIn('aoc_documents.document_type', $documentType);
        })->where('entity_id',$clinicianId);
       // echo $q->toSql();
       // var_dump($documentType);
        return Datatables::of($q)

        ->editColumn('description', function ($row) {
            $fileExt = '';
            if(!empty($row->document_filename)) {
                $ext = explode(".",$row->document_filename);
                $fileExt = $ext[count($ext)-1];
            }
            $descript = $row->description;

            if($row->document_type == 'pay_rate'){
                $extraData = json_decode($row->extra_data,true);
                if(isset($extraData['client_id'])) {
                    $patientAccount = DB::table("mssql_patients")->select("PatientAccount")->where("PatientID",$extraData['client_id'])->first();
                    if($patientAccount) {
                        $descript.=' ('.$patientAccount->PatientAccount.')';
                    }

                }

            }

            if($fileExt=='pdf') {
                return '<a href="javascript:void(0)" onclick="showPDFDocument('.$row->id.')">'.$descript.'</a>';
            } else {
                return '<a href="javascript:void(0)" onclick="showClinicianDocument('.$row->id.')">'.$descript.'</a>';
            }

        })
        ->editColumn('document_filename', function ($row) {
            if(H::canPermissions(['rightSignature.documentDownload'])) {
                return '<a href="'.route('rightSignature.documentDownload').'?documentFileName='.$row->document_filename.'" title="Download Document"><i class="fa fa-download" aria-hidden="true"></i></a>';
            } else {
                return '';
            }
        })->editColumn('document_type', function ($row) {
            if($row->document_type == 'field_eval'){
                return "Field Evaluation";
            }else if($row->document_type == 'therapy_eval'){
                return "Therapy Evaluation";
            }else if($row->document_type == 'pay_rate'){
                return "Pay Rate Agreement";
            } else if($row->document_type=='office_eval') {
                return "Office Evaluation";
            } else if($row->document_type=='renewables') {
                return "Renewables";
            } else if($row->document_type=='offer_accepted') {
                return "Offer Accepted";
            } else if($row->document_type=='neo_attachment') {
                return "NEO Attachment";
            } else if($row->document_type=='therapy_recruiter_phone_screen') {
                return "Therapy Recruiter Phone Screen";
            } else if($row->document_type=='therapy_clinical_interview') {
                return "Therapy Clinical Interview";
            } else if($row->document_type=='therapy_offer_extended') {
                return "Therapy Offer Extended";
            } else{
                return $row->document_type;
            }
        })->rawColumns(['document_filename','description'])->make(true);
    }

    public function documentDownload(Request $request)
    {
        try {
            $requestData = $request->all();
            if (isset($requestData['documentFileName']) && $requestData['documentFileName'] != '') {
                $destinationPath = storage_path() . '/documents/';
                if (file_exists($destinationPath . $requestData['documentFileName'])) {
                    $type = explode('.', $requestData['documentFileName']);
                    $ext = $type[count($type) - 1];
                    $headers = ['Content-Type: application/' . $ext];
                    return response()->download($destinationPath . $requestData['documentFileName'], $requestData['documentFileName'], $headers);
                } else {
                    return redirect(route('clinicianListing'));
                }
            }
        } catch (\Exception $e) {
            H::ExcelLog($e->getMessage(),'clinicianController.log');
            return false;
        }
    }

    public function statusChangeRequest(Request $request)
    {
        return view('clinician.statusChangeRequest');
    }

    public function getCandidateName(Request $request)
    {
        $empStatus = config('constant')['statusChangeRequestStatus'];
        $term = str_replace('  ', ' ', str_replace(',', '', $request->term));
        $clientlist = DB::table('mssql_providers')
        ->select(
            'mssql_providers.id',
            'mssql_providers.FirstName',
            'mssql_providers.LastName',
            'mssql_providers.ClinicianID',
            'mssql_facilities.FacilityName',
            'mssql_facilities.branchid',
            'mssql_employmentstatus.EmploymentStatus',
        )
        ->join('mssql_employmentstatus', 'mssql_providers.EmploymentStatusID', '=', 'mssql_employmentstatus.EmploymentStatusID')
        ->join('mssql_facilities', 'mssql_providers.FacilityID', '=', 'mssql_facilities.FacilityID')
        ->whereIn('mssql_employmentstatus.EmploymentStatus', $empStatus)
        ->where(
            function ($query) use($term) {
                $query->where('mssql_providers.Search_Name', 'like', '%' . $term . '%');
                $query->orWhere('mssql_providers.ClinicianID', 'like', '%' . $term . '%');
            }
        )->where('mssql_providers.is_deleted', '0')->limit(15)->get();
        $clientlist = json_decode(json_encode($clientlist), true);

        $clientListData = [];
        foreach ($clientlist as $clientSubData) {
            $clientListData[] = [
                'label' => $clientSubData['LastName'] . ', ' . $clientSubData['FirstName'] . ' | ' . $clientSubData['ClinicianID'] . ' | ' . $clientSubData['EmploymentStatus'] . ' | ' . $clientSubData['FacilityName'],
                'value' => $clientSubData['FirstName'] . ' ' . $clientSubData['LastName'],
                'FacilityName' => $clientSubData['FacilityName'],
                'EmploymentStatus' => $clientSubData['EmploymentStatus'],
                'ClinicianID' => $clientSubData['ClinicianID'],
                'id' => $clientSubData['id'],
                'branchid' => $clientSubData['branchid']
            ];
        }

        return response()->json($clientListData);
    }

    public function statusChangeRequestSave (Request $request)
    {
        try {
            $postData = $request->all();
            $validator = Validator::make($request->all(), [
                'src_candidate_name' => ['required'],
                'src_requested_status' => ['required'],
                'src_reason' => ['required'],
                'src_date_requested' => ['required'],
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $checkRecordExists = DB::table('mssql_providers')->where('id', $postData['src_provider_id'])->whereNotNull('request_outcome')->whereRaw('request_outcome <> ""')->whereNotIn('request_outcome', ['Completed', 'Rejected'])->first();
            if ($checkRecordExists) {
                return response()->json(['status' => 0, 'msg' => 'An open status change request already exists']);
            } else {
                DB::table('mssql_providers')->where('id', $postData['src_provider_id'])->update([
                    'requested_status' => '',
                    'request_outcome' => 'Requested',
                    'request_outcome_reason' => '',
                    'request_outcome_comment' => '',
                    'request_pending_document' => '',
                    'request_reason' => '',
                    'request_date' => '',
                    'request_by' => '',
                    'request_last_followup' => '',
                    'request_advocacy_specialist' => ''
                ]);
            }

            $oldStatusId = MssqlProviders::where('id', $postData['src_provider_id'])->value('EmploymentStatusID');

            if(LD::checkFeatureFlag('status-change-request-request-oc-checkbox')) {

                $provider = MssqlProviders::select(DB::raw("(SELECT users.id from users
                left join users_has_roles on users.id = users_has_roles.user_id
                left join roles on users_has_roles.role_id = roles.id
                where roles.name='Orientation Coordinator'
                and users.status='1'
                and find_in_set(mssql_facilities.branchid, users.branchid) limit 1) as oc"))
                ->leftJoin("mssql_facilities", function($query){
                    $query->on('mssql_providers.FacilityID', '=', 'mssql_facilities.FacilityID');
                })
                ->where('mssql_providers.id', $postData['src_provider_id'])->first();
                $oc = $provider->oc;
                DB::table('mssql_providers')->where('id', $postData['src_provider_id'])->update([
                    'requested_status' => $postData['src_requested_status'],
                    'request_outcome' => 'Requested',
                    'request_reason' => $postData['src_reason'],
                    'request_date' => (!empty($postData['src_date_requested']))
                     ? date('Y-m-d', strtotime($postData['src_date_requested'])) : '',
                    'request_by' => Auth::user()->id,
                    'scr_original_status_id' => $oldStatusId,
                    'request_oc'=>$oc,
                    'change_request_employment' => isset($postData['employment_type'])
                     ? $postData['employment_type'] : 0,
                ]);

            } else {
                DB::table('mssql_providers')->where('id', $postData['src_provider_id'])->update([
                    'requested_status' => $postData['src_requested_status'],
                    'request_outcome' => 'Requested',
                    'request_reason' => $postData['src_reason'],
                    'request_date' => (!empty($postData['src_date_requested']))
                     ? date('Y-m-d', strtotime($postData['src_date_requested'])) : '',
                    'request_by' => Auth::user()->id,
                    'scr_original_status_id' => $oldStatusId,
                    'change_request_employment' => isset($postData['employment_type'])
                     ? $postData['employment_type'] : 0,
                ]);
            }


            $clinicianDetails = MssqlProviders::find($postData['src_provider_id']);
            if($clinicianDetails->fullName == ''){
                $clinicianDetails->fullName = $clinicianDetails->FirstName.' '.$clinicianDetails->LastName;
            }

            $empStatus = DB::table('mssql_employmentstatus')->where('mssql_employmentstatus.EmploymentStatusID', $postData['src_requested_status'])->first();
            $noteData = [];
            $noteData['entity'] = 'Clinician';
            $noteData['entity_id'] = $postData['src_provider_id'];
            $empOldStatus = DB::table('mssql_employmentstatus')->where('mssql_employmentstatus.EmploymentStatusID', $oldStatusId)->first();
            $msg = 'A Status Change Request has been submitted for  ' . $clinicianDetails->fullName;
            $msg .= ' by ' . H::getLoggedInUser()['name'].' ' . H::getFormatedDateTime(\Carbon\Carbon::now()) . '<br/>';
            $msg .= 'Reason: ' . $postData['src_reason'] . '<br/>';
            $msg .= 'Current Status: ' .$empOldStatus->EmploymentStatus . '<br/>';
            $msg .= 'Requested Status: '.$empStatus->EmploymentStatus . '<br/>';
            $msg .= 'Outcome: Requested';
            $noteData['notes'] = $msg;

            H::getHelper("ActivityNotes")->setActivityNotes($noteData);

            $notificationData = [];
            $notificationData['user_id'] = H::getOrientationCoordinator($postData['src_branch_id']);
            $notificationData['title'] = $clinicianDetails->fullName . ' Status Change Request - Action Required';
            $notificationData['type'] = 'statusChange';
            $notificationData['related_item'] = $postData['src_provider_id'];
            $notificationData['notes'] = 'A status change request has been submitted for ' . $clinicianDetails->fullName . '. Please take prompt action in updating their status in KanTime.';
            Notifications::addNotification($notificationData);

            return response()->json(['status' => 1, 'msg' => 'Status change request saved successfully.']);
        } catch (\Exception $e) {

            return response()->json(['status' => 0, 'msg' => 'Error while saving status change request.']);
        }
    }

    public function viewDocument(Request $request) {
        $reqD = $request->all();
        $tempDirectory = 'viewdocuments';
        $newFileName = '';
        $ext = [];
        $aocDocuments = DB::table("aoc_documents")->select("*")->where("id",$reqD['id'])->first();
        if(!empty($aocDocuments->document_filename)) {
            $ext = explode(".",$aocDocuments->document_filename);
             $path = $destinationPath = storage_path() . '/documents/'.$aocDocuments->document_filename;

            if(file_exists($path)) {
                $newFileName = time().'_'.Auth::user()->id.'.'.$ext[count($ext)-1];
                if(!is_dir(base_path().'/'.$tempDirectory)) {
                    mkdir(base_path().'/'.$tempDirectory);
                }
                $sourceFilePath = realpath($path);
                $unsanitizedDestinationPath = base_path().'/'.$tempDirectory.'/'.$newFileName;
                $sanitizedDestinationPath = str_replace("\0", '', realpath(dirname($unsanitizedDestinationPath)));
                $sanitizedFullDestinationPath = $sanitizedDestinationPath . '/' . basename($unsanitizedDestinationPath);
                if (
                    ($sourceFilePath !== false && file_exists($sourceFilePath))
                ) {
                    copy($sourceFilePath, $sanitizedFullDestinationPath);
                }
            }
        }
        if($ext[count($ext)-1]=='pdf') {
            return response()->json(['filename' => url('/').'/'.$tempDirectory.'/'.$newFileName]);
        } else {
            return view('clinician.viewDocumentPopup', ["type" => $ext[count($ext)-1],"path"=>$tempDirectory.'/'.$newFileName]);
        }
    }

    public function saveClinicianCustomCity (Request $request)
    {
        try {
            $clinicianID = $request->ClinicianID;
            $parts = explode(',', $request->city);
            $cityValue = trim($parts[0]);
            $stateValue = trim($parts[1]);

            $updateData = ['aoc_cf_city' =>$cityValue,'aoc_cf_state' =>$stateValue,'Preference_Status' => 1];
            DB::table('mssql_providers')->where('ClinicianID', $clinicianID)->update($updateData);
            DB::table('maps_nthalerts')->where('ClinicianID', $clinicianID)->update(['Preference_Status' => 1]);
            return response()->json(['status' => 1, 'msg' => 'Details saved successfully.','city' => $request->city]);
        } catch (\Exception $e) {
            return response()->json(['status' => 0, 'msg' => 'Error while saving city details.']);
        }
    }
}
