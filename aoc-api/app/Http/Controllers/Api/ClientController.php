<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ExternalUsers;
use App\Models\PatientVacation;
use Illuminate\Support\Str;
use Validator;
use App\Helpers\Helper;
use DB;
use App\Models\MssqlPatients;
use App\Models\Notification;
use App\Models\ExternalUserPatients;
use App\Models\User;

class ClientController extends Controller
{
    //
    public function clientList(Request $request)
    {
        $term = '';
        $mode = 'app';
        if(isset($request->is_hh) && $request->is_hh){
            $mode = 'hh';
        }
        if($request->api_key=='SKb6eaaa9212c63bf7cdae4e82ac6f358032'){
            $mode = 'hh';
        }
        $limit = $request->batch??100;
        if($mode=='hh'){
            $limit = 5000;
        }
        $page = isset($request->page)?$request->page:1;
        $start = (($page-1)*$limit);
        $order = !empty($request->sort_by)?$request->sort_by:'mssql_patientstatus.PatientStatusID';
        $dir =  !empty($request->sort_order_by)?$request->sort_order_by:'asc';
        if(isset($request->filters['search']) && $request->filters['search'] != '') {
            $term =  $request->filters['search'];
            $term = str_replace('  ', ' ', str_replace(',', '', $term));
        }

        if($mode=='hh'){
            $query = DB::table('mssql_patients')
                ->leftJoin('mssql_patientstatus', 'mssql_patientstatus.PatientStatusID', '=',
                    'mssql_patients.Patient_CurrentStatusID')
                ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=',
                    'mssql_patients.aoc_facility_id')
                ->leftJoin('lob', 'lob.LOBID', '=', 'mssql_patients.LOBID')
                ->select('mssql_patients.id', 'mssql_patients.PatientName',
                    'mssql_facilities.FacilityName',
                    'mssql_patients.PatientAccount',
                    'mssql_patients.Patient_DOB',
                    DB::raw("ifnull(mssql_patients.hh_chartid,'') as hh_chartid"),
                    DB::raw("ifnull(mssql_patients.hh_soc,'') as hh_soc"),
                    DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',
                        mssql_patients.Patient_MiddleName,' ',mssql_patients.Patient_LastName) as patientFullName"),
                    'mssql_patients.Patient_DOB','mssql_patients.MedicaidNumber',
                    'mssql_patients.Payer',DB::raw("ifnull(mssql_patients.hh_status,'') as hh_status"),
                    "mssql_patients.hh_nurse_navigator",
                    "mssql_patients.Patient_Gender",
                    "mssql_patients.Languages",
                    DB::raw("IF(DischargeDate = '0000-00-00' OR DischargeDate IS NULL, '',
                    DischargeDate) AS DischargeDate"));
            if((isset($request->filters['hh_only']) && $request->filters['hh_only'] == true) || !isset($request->filters['hh_only'])){
				Helper::ExcelLog('HH clientList Request HH Only true', 'clientList.log');
                $query->whereRaw("ifnull(hh_chartid,'') <> ''");
            }
            //$query->where('mssql_patients.hh_eligible',1);
            if (isset($request->filters['hh_status']) && ($request->filters['hh_status'] !== ''
            && $request->filters['hh_status'] !== null)) {
                if (!is_array($request->filters['hh_status'])) {
                    $query->where('mssql_patients.hh_status',$request->filters['hh_status']);
                } elseif (!empty($request->filters['hh_status'])) {
                    $query->whereIn('mssql_patients.hh_status',$request->filters['hh_status']);
                }
            }
            if(isset($request->filters['global_search']['value']) && $request->filters['global_search']['value'] != '') {
                $query->whereRaw("MATCH(global_search) AGAINST('".$request->filters['global_search']['value']."' IN BOOLEAN MODE)");
            }
            if (isset($request->filters['hh_nurse_navigator']) && ($request->filters['hh_nurse_navigator'] !== ''
            && $request->filters['hh_nurse_navigator'] !== null)) {
                if (!is_array($request->filters['hh_nurse_navigator'])) {
                    $query->where('mssql_patients.hh_nurse_navigator',$request->filters['hh_nurse_navigator']);
                } elseif (!empty($request->filters['hh_nurse_navigator'])) { 
                    $query->whereIn('mssql_patients.hh_nurse_navigator',$request->filters['hh_nurse_navigator']);
                }
            }
            if (!empty($request->filters['patient_CurrentStatusID'])) {
                $query->whereIn('mssql_patients.patient_CurrentStatusID',$request->filters['patient_CurrentStatusID']);
            }
            if (!empty($request->filters['Patient_DOB'])) {
                if(strpos($request->filters['Patient_DOB'], "##") !== false) {
                    $dateRange = explode("##",$request->filters['Patient_DOB']);
                    $query->whereDate('mssql_patients.Patient_DOB','>=',date('Y-m-d',strtotime($dateRange[0])));
                    $query->whereDate('mssql_patients.Patient_DOB','<=',date('Y-m-d',strtotime($dateRange[1])));
                } else {
                    $query->whereDate('mssql_patients.Patient_DOB',date('Y-m-d',strtotime($request->filters['Patient_DOB'])));
                }
            }
            if (!empty($request->filters['lob'])) {
                $query->whereIn('mssql_patients.LOBID',$request->filters['lob']);
                $query->where('mssql_patients.IsLatestAdmit',1);
            }
        }else{
            $query = DB::table('mssql_patients')
                ->leftJoin('mssql_patientstatus', 'mssql_patientstatus.PatientStatusID',
                    '=', 'mssql_patients.Patient_CurrentStatusID')
                ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
                ->leftJoin('lob', 'lob.LOBID', '=', 'mssql_patients.LOBID')
                ->select(DB::raw('DISTINCT(mssql_patients.id)'), 'mssql_patients.PatientName',
                    'mssql_facilities.FacilityName', 'mssql_patients.PatientAccount',
                    'mssql_patients.Patient_DOB',
                    DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',
                        mssql_patients.Patient_LastName) as patientFullName"));
        }
        if( $term != '') {
            $query->where(
                function ($query) use($term) {
                    $query->where('mssql_patients.Search_Name', 'like', '%' . $term . '%')
                        ->orWhere('mssql_patients.PatientAccount', 'like', '%' . $term . '%');
                }
            );
        }

        $filters = [];
        if(isset($request->filters)) {
            foreach($request->filters as $key => $fl) {
                if(!empty($fl)) {
                    $filters[$key] = $fl;
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
                }elseif($fkey=='payer'){
                    $query->where(function($query) use ($fval) {
                        foreach($fval as $val) {
                            $query->orWhere("mssql_patients.Payer",'=',$val);
                        }
                    });
                }
            }
        }
        if(!empty($request->filters['aoc_provider_id'])) {
            $query->leftJoin('mssql_schedulemaster', 'mssql_schedulemaster.PatientID', '=', 'mssql_patients.PatientID')
            ->leftJoin('mssql_providers', 'mssql_providers.ProviderID', '=', 'mssql_schedulemaster.ProviderID');
            $query->where("mssql_providers.id",'=',$request->filters['aoc_provider_id']);
            $query->orderByRaw('PatientName, CGTaskID');
        }else{
            $query->offset($start)->limit($limit)->orderBy($order, $dir);
        }
        Helper::ExcelLog('ClientList Query =='.$query->toSql(), 'clientList.log');
        $data = $query->get()->toArray();
        if(count($data) > 0) {
            $nextpage = $request->page+1;
        }else {
            $nextpage = -1;
        }
        return Helper::response(config('constants.response_code.OK'),['page'=> $page,'nextpage'=> $nextpage,'batch'=> $limit,'client'=> $data]);
    }

    public function clientDetails(Request $request)
    {
        $validation = $this->validateClientDetails($request->all());

        if ($validation->fails()) {
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'),
                [], $validation->errors()->all());
        }
        $clientData = DB::table('mssql_patients')->select("mssql_patients.*",
            DB::raw("concat(mssql_patients.Patient_FirstName,' ',
                mssql_patients.Patient_MiddleName,' ',
                mssql_patients.Patient_LastName) as patientFullName"),
            DB::raw("ifnull(mssql_patients.hh_chartid,'') as hh_chartid"),
            DB::raw("ifnull(mssql_patients.hh_soc,'') as hh_soc"),
            DB::raw("ifnull(mssql_patients.hh_status,'') as hh_status"),
            "mssql_facilities.FacilityName")
            ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
            ->where("mssql_patients.id",$request->patient_id)->first();
        $hhsoc = Helper::getFormatedDate($clientData->hh_soc);
        $clientData->hh_soc =$hhsoc;
        $patientDOB = Helper::getFormatedDate($clientData->Patient_DOB);
        $clientData->Patient_DOB = $patientDOB;
        if(isset($request->api_key) && isset($request->api_secret)) {
            $clientDetails = $clientData;
            $primarycarePhysician =  '';
            if(!empty($clientData->PrimarycarePhysicianID)) {
                $physiciansNameList = DB::table('ReferringProviders')->where('ReferringProviders.ReferringProviderID', '=',$clientData->PrimarycarePhysicianID)->select(DB::raw("ifnull(ReferringProviders.ReferringProviderName,'') as name"))->first();
                $primarycarePhysician =  $physiciansNameList->name??'';
            }
            $clientData->PrimarycarePhysician =  $primarycarePhysician;

            $kantime_status = '';
            if(!empty($clientData->Patient_CurrentStatusID)) {
                $statusData = DB::table('mssql_patientstatus')
                ->where('mssql_patientstatus.PatientStatusID', '=',$clientData->Patient_CurrentStatusID)
                ->select(DB::raw("ifnull(mssql_patientstatus.PatientStatus,'') as kantime_status"))->first();
                $kantime_status = $statusData->kantime_status??'';
            }
            $clientData->kantime_status =  $kantime_status;

            $clientEmergencyContactList = ExternalUserPatients::getClientEmergencyContactDetails($request->patient_id);
            $clientData->EmergencyContact = $clientEmergencyContactList;
        } else {
            $clientDetails[__('messages.label_name')] = $clientData->patientFullName;
            $clientDetails[__('messages.label_address')] = $clientData->Patient_Address1." ".$clientData->Patient_Address2." ".$clientData->Patient_City." ".$clientData->Patient_Zip;
            $clientDetails[__('messages.label_phone')] = ($clientData->Patient_MobilPhone !='')?$clientData->Patient_MobilPhone:$clientData->Patient_Phone;
            //$clientDetails['Email'] = $clientData->patientFullName;
            $clientDetails[__('messages.label_dob')] = $clientData->Patient_DOB;
        }
        return Helper::response(config('constants.response_code.OK'), ['clientdetail'=> $clientDetails]);
    }

    private function validateClientDetails($request){

        return Validator::make($request,
        [
            'patient_id' => 'required'
        ],
        [
            'patient_id.required' => __('messages.client_id_required')
        ]
        );
    }

    public function addToHH(Request $request)
    {
        $hh_soc = '';
        if(isset($request->hh_soc) && $request->hh_soc != '') {
            $hh_soc = date('Y-m-d', strtotime($request->hh_soc));
        }
        $patient_id = $request->patient_id;
        try {
            $currentDetails = DB::table('mssql_patients')->select('hh_chartid')->where('id',$patient_id)->first();
            if($currentDetails->hh_chartid != null && $currentDetails->hh_chartid != '') {
                return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.hh_chartid_already_exists'));
            }else {
                $exitDetails = DB::table('mssql_patients')->select('hh_chartid')->latest('hh_chartid')->first();
                if($exitDetails->hh_chartid == null || $exitDetails->hh_chartid == '') {
                    $hh_chartid = '1000-HH';
                }else {
                    $hh_chartid = str_replace("-HH","", $exitDetails->hh_chartid);
                    $hh_chartid = ($hh_chartid+1).'-HH';
                }
                $requestData = ['hh_soc' => $hh_soc,'hh_chartid'  => $hh_chartid];
                $hhStatus = 'Referral';
                if($hh_soc != '') {
                    $hhStatus = 'Active';
                    if($hh_soc > date('Y-m-d')) {
                        $hhStatus = 'Pending';
                    }
                }
                $requestData['hh_status'] = $hhStatus;
                DB::table('mssql_patients')->where('id',$patient_id)->update($requestData);
                Helper::ExcelLog('addToHH Request: ' . print_r($requestData,true), 'addToHH.log');
                return Helper::response(config('constants.response_code.OK'), ['hh_chartid' => $hh_chartid], __('messages.details_updated_successfully'));
            }
        } catch (\Exception $e) {
            Helper::ExcelLog('addToHH Error: ' . $e->getMessage(), 'addToHH.log');
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }
    public function updateClient(Request $request)
    {
        $patient_id = $request->patient_id;
        try {
            $requestData = [];
            $dateField = ['hh_soc'];
            if(is_array($request->update_fields) && count($request->update_fields) > 0) {
                foreach($request->update_fields as $key => $value) {
                    if(in_array($key,$dateField)) {
                        $value = date('Y-m-d', strtotime($value));
                    }
                    $requestData[$key] = $value;
                }
            }
            DB::table('mssql_patients')->where('id',$patient_id)->update($requestData);
            Helper::ExcelLog('updateClient Request: ' . print_r($requestData,true), 'updateClient.log');
            return Helper::response(config('constants.response_code.OK'), ['patient_id' => $patient_id], __('messages.details_updated_successfully'));
        } catch (\Exception $e) {
            Helper::ExcelLog('updateClient Error: ' . $e->getMessage(), 'updateClient.log');
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }

    public function submitFormRequest(Request $request)
    {
        $validation = $this->validatesubmitFormRequest($request->all());

        if ($validation->fails()) {
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], $validation->errors()->all());
        }
        try {
            $patientDetails = MssqlPatients::getPatientDetails($request->patient_id,['mssql_patients.id','mssql_patients.ccc_id',
            'mssql_patients.rdod_id',
            'mssql_patients.dbo_id',
            'mssql_patients.ccm_id',
            'mssql_patients.FacilityID',
            DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',
            mssql_patients.Patient_MiddleName,' ',mssql_patients.Patient_LastName) as patientFullName")]);
            if ($patientDetails == NULL) {
                return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.invalid_details'));
            }
            $selectedPatientName = $patientDetails->patientFullName;
            $userDetail = ExternalUsers::find($request->id);
            $requestData['aoc_patient_id'] = $request->patient_id;
            $noteEntity = 'NoteByApi';
            $assignedTo[] = $patientDetails->ccc_id;
            $miscellaneous = $notificationNotes = $notificationTitle ='';
            if($request->request_type == 'vacationRequest') {
                $requestData['from_date'] = Helper::getDBFormatedDate($request->from_date);
                $requestData['to_date'] = Helper::getDBFormatedDate($request->to_date);
                $requestData['destination'] = $request->destination;
                $requestData['with_nurse'] = $request->with_nurse;
                $requestData['comment'] = $request->comment;
                Helper::ExcelLog('submitFormRequest Request: ' . print_r($requestData,true), 'submitFormRequest.log');
                PatientVacation::create($requestData);
                //Note
                $newNoteDetailStr = '';
                $newNoteDetailStr = $newNoteDetailStr.'From Date: '.$request->from_date."<br>";
                $newNoteDetailStr = $newNoteDetailStr.'To Date: '.$request->to_date."<br>";
                $newNoteDetailStr = $newNoteDetailStr.'Destination: '.$request->destination."<br>";
                $withNurse = ($request->with_nurse)?'Yes': 'No';
                $newNoteDetailStr = $newNoteDetailStr.'With Nurse: '.$withNurse;
                if($request->comment != '') {
                    $newNoteDetailStr = $newNoteDetailStr."<br>";
                    $newNoteDetailStr = $newNoteDetailStr.'Comment: '.$request->comment;
                }
                $notes = $notificationTitle = $userDetail->name.' added vacation request for patient '.$selectedPatientName.' for following schedule';
                $notes = $notes."<br> ".$newNoteDetailStr;
                $noteEntity = 'Vacation';
                $responseMessage  = __('messages.vacation_submitted');
                $notificationNotes = $newNoteDetailStr;
            } elseif($request->request_type == 'reportHospitalization') {
                //Note
                $newNoteDetailStr = '';
                $newNoteDetailStr = $newNoteDetailStr.'From Date: '.$request->from_date."<br>";
                $newNoteDetailStr = $newNoteDetailStr.'To Date: '.$request->to_date;
                if(!empty($request->currently_hospitalized)) {
                    $newNoteDetailStr = $newNoteDetailStr."<br>";
                    $newNoteDetailStr = $newNoteDetailStr.'Current Hospitalized: '.$request->currently_hospitalized;
                }
                if(!empty($request->discharge_date)) {
                    $newNoteDetailStr = $newNoteDetailStr."<br>";
                    $dischargeDateLabel = ($request->currently_hospitalized == 'Yes')? 'Expected Discharge Date':'Discharge Date';
                    $newNoteDetailStr = $newNoteDetailStr.' '.$dischargeDateLabel.': '.$request->discharge_date;
                }
                if(!empty($request->type)) {
                    $newNoteDetailStr = $newNoteDetailStr."<br>";
                    $newNoteDetailStr = $newNoteDetailStr.'Type: '.$request->type;
                }
                if(!empty($request->reason)) {
                    $newNoteDetailStr = $newNoteDetailStr."<br>";
                    $newNoteDetailStr = $newNoteDetailStr.'Reason: '.$request->reason;
                }
                if(!empty($request->hospital)) {
                    $newNoteDetailStr = $newNoteDetailStr."<br>";
                    $newNoteDetailStr = $newNoteDetailStr.'Hospital: '.$request->hospital;
                }
                if(!empty($request->room_number)) {
                    $newNoteDetailStr = $newNoteDetailStr."<br>";
                    $newNoteDetailStr = $newNoteDetailStr.'Room Number: '.$request->room_number;
                }
                if(!empty($request->diagnosis)) {
                    $newNoteDetailStr = $newNoteDetailStr."<br>";
                    $newNoteDetailStr = $newNoteDetailStr.'Diagnosis: '.$request->diagnosis;
                }
                if($request->comment != '') {
                    $newNoteDetailStr = $newNoteDetailStr."<br>";
                    $newNoteDetailStr = $newNoteDetailStr.'Comment: '.$request->comment;
                }
                $notes = $notificationTitle = $userDetail->name.' has reported hospitalization for patient '.$selectedPatientName.' for following dates';
                $notes = $notes."<br> ".$newNoteDetailStr;
                $noteEntity = 'Hospitalization';
                $responseMessage  = __('messages.hospitalization_submitted');
                $notificationNotes = $newNoteDetailStr;
            } elseif($request->request_type == 'callRequest') {
                //Note
                $newNoteDetailStr = '';
                if(!empty($request->call_date)) {
                    $newNoteDetailStr = $newNoteDetailStr.'<br>Date: '.$request->call_date;
                }
                if(!empty($request->availability)) {
                    $newNoteDetailStr = $newNoteDetailStr.'<br>Availability: '.$request->availability;
                }
                if($request->reason != '') {
                    $newNoteDetailStr = $newNoteDetailStr.'<br>Reason: '.$request->reason;
                }
                if($request->comment != '') {
                    $newNoteDetailStr = $newNoteDetailStr."<br>";
                    $newNoteDetailStr = $newNoteDetailStr.'Comment: '.$request->comment;
                }
                if($request->reason == 'Clinical Updates/Questions') {
                    if(!empty($patientDetails->ccm_id)) {
                        $assignedTo[] = $patientDetails->ccm_id;
                    }
                } elseif($request->reason == 'Report a Concern') {
                    //Assign to CCM/DBO/RDO/VP in to do list
                    if(!empty($patientDetails->ccm_id)) {
                        $assignedTo[] = $patientDetails->ccm_id;
                    }
                    if(!empty($patientDetails->rdod_id)) {
                        $assignedTo[] = $patientDetails->rdod_id;
                    }
                    if(!empty($patientDetails->dbo_id)) {
                        $assignedTo[] = $patientDetails->dbo_id;
                    }
                    $stateVP = MssqlPatients::getUserIdByBranchAndRole($patientDetails->FacilityID, 'State VP');
                    $regionalVP = MssqlPatients::getUserIdByBranchAndRole($patientDetails->FacilityID, 'Regional VP');
                    if(!empty($stateVP)) {
                        $assignedTo = array_merge($assignedTo,$stateVP);
                    }
                    if(!empty($regionalVP)) {
                        $assignedTo = array_merge($assignedTo,$regionalVP);
                    }
                }
                $notes = $notificationTitle = $userDetail->name.' has requested a call for patient '.$selectedPatientName;
                $notes = $notes." ".$newNoteDetailStr;
                $noteEntity = 'Call Request';
                $responseMessage  = __('messages.call_request_submitted');
                $notificationNotes = $newNoteDetailStr;
            } elseif($request->request_type == 'submitHalos'){
                $noteEntity = 'HALOS Moment';
                $responseMessage  = __('messages.halos_submitted');
                $notes = $request->shoutout;
                if($request->hasFile('image')){

                    $image = $request->file('image');

                    $extension = $image->extension();

                    $filename = sha1(
                            $image->getClientOriginalName() .
                            now()->timestamp .
                            Str::random(20)
                        ) . '.' . $extension;

                    $path = $image->storeAs(
                        'halos/' . date('Y/m'),
                        $filename,
                        'public'
                    );

                    $miscellaneous = $path;

                }
            } elseif ($request->request_type == 'submitDemographic') {
                //Note
                $newNoteDetailStr = '';
                $notes = $notificationTitle = $userDetail->name . ' wants to change following demographic info for patient ' . $selectedPatientName;
                $notes = $notes . "<br>" . $request->details;
                $noteEntity = 'submitDemographic';
                $responseMessage  = __('messages.demographic_change_submitted');
                $notificationNotes = $request->details;
            } elseif ($request->request_type == 'featurePhotos') {
                $notes = 'New feature photo uploaded for ' . $selectedPatientName;
                $noteEntity = 'Feature Photo';
                $responseMessage  = __('messages.feature_photo_uploaded');
                $images = $request->file('image');
                if($request->hasFile('image')) {
                    foreach($images as $image) {
                        $currentDateTime = date('Y-m-d H:i:s');
                        $type = $image->getClientOriginalExtension();
                        $imageContents = file_get_contents($image->getRealPath());
                        $document_link = 'data:image/' . $type . ';base64,' . base64_encode($imageContents );
                        $aocDocumentData = ['entity_id' => $requestData['aoc_patient_id'], 'entity_type' => 'mobileapp', 'document_type' =>'feature_photos','document_filename' => 0,'send_date' => date('Y-m-d'),'document_link' => $document_link, 'created_at' =>$currentDateTime, 'updated_at' =>$currentDateTime];
                        DB::table('aoc_documents')->insert($aocDocumentData);
                    }
                }
            }
            if(!empty($assignedTo)) {
                foreach($assignedTo as $assignedUser) {
                    $notesData = ['entity' => $noteEntity,
                    'entity_id' => $requestData['aoc_patient_id'],
                    'notes' =>$notes,
                    'completed' => 0,
                    'created_by' => $request->id,
                    'assigned_to' => $assignedUser,
                    'misc4' => $miscellaneous,
                    'misc1' => 'mobileapp'];
                    Helper::setActivityNotes($notesData);
                }
            }
            if($notificationNotes !='' && $notificationTitle !='') {
                $notificationData = ['type' => 'client', 'related_item' => $requestData['aoc_patient_id'], 'notes' =>$notificationNotes,'title' => $notificationTitle,'created_by' => $requestData['aoc_patient_id'],'user_id' => $patientDetails->ccc_id];
                Helper::addNotification($notificationData);
            }
            return Helper::response(config('constants.response_code.OK'), [], $responseMessage);
        } catch (\Exception $e) {
            Helper::ExcelLog('submitFormRequest Error: ' . $e->getMessage(), 'submitFormRequest.log');
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }

    private function validatesubmitFormRequest($request){
        
        return Validator::make($request,
        [
            'patient_id' => 'required'
        ],
        [
            'patient_id.required' => config('constants.validationsError.patient_id_required')
        ]
        );
    }

    public function payers(){
        try{
            $payer = DB::table('mssql_patients')->select(DB::raw('DISTINCT Payer'))
            ->where(function($q) {
                $q->where("mssql_patients.Payer",'!=', '')
                ->whereNotNull('mssql_patients.Payer');
            })->whereRaw("ifnull(hh_chartid,'') <> ''")->OrderBy('Payer')->get();
            $payer_data = [];
            foreach($payer as $key => $data){
                $payer_data[$key]['payer_id'] = $data->Payer;
                $payer_data[$key]['payer_name'] = $data->Payer;
            }
            return Helper::response(config('constants.response_code.OK'), $payer_data);
        }catch(\Exception $e){
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }

    public function status(){
        try{
            //$status = DB::table("mssql_patientstatus")->select('PatientStatus','id as Patient_CurrentStatusID')->where("PatientStatus","!=", "")->get();
            $status = [
                ['PatientStatus'=>'Declined Enrollment','Patient_CurrentStatusID' => 1],
                ['PatientStatus'=>'Declined Enrollment (Do Not Contact)','Patient_CurrentStatusID' => 2],
                ['PatientStatus'=>'Discharged','Patient_CurrentStatusID' => 3],
                ['PatientStatus'=>'Enrolled','Patient_CurrentStatusID' => 4],
                ['PatientStatus'=>'Never Serviced','Patient_CurrentStatusID' => 5],
                ['PatientStatus'=>'On Hold','Patient_CurrentStatusID' => 6],
                ['PatientStatus'=>'Pending','Patient_CurrentStatusID' => 7],
                ['PatientStatus'=>'Referral','Patient_CurrentStatusID' => 8],
                ['PatientStatus'=>'Unenrolled','Patient_CurrentStatusID' => 9]
            ];
            return Helper::response(config('constants.response_code.OK'), $status);
        }catch(\Exception $e){
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }

    public function state(){
        try{
            $response['state'] = config('constants.dashboard_state');
            return Helper::response(config('constants.response_code.OK'), $response);
        }catch(\Exception $e){
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }

    public function facilities(Request $request){
        try{
            $validation = $this->validateState($request->all());
            if ($validation->fails()) {
                return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], $validation->errors()->all());
            }
            $state = $request->state;
            $facilities = DB::table('mssql_facilities')->select('id','FacilityName')->where(function($query){
                $query->where('hide', '!=', '1')->orWhereNull('hide');
            })->where(function($query) use ($state){
                if (!is_array($state))
                    $st = explode(',',$state);
                else
                $st = $state;
                foreach($st as $s) {
                    $query->orWhere("state",'=',$s);
                }
            })->orderBy('FacilityName');
            $data = $facilities->get();
            return Helper::response(config('constants.response_code.OK'), $data);
        }catch(\Exception $e){
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }
    private function validateState($request)
    {
    return Validator::make(
            $request,
            [
                'state' => 'required'
            ],
            [
                'state.required' => __('messages.state_required')
            ]
        );
    }

    public function getNotifications(Request $request) {
        try {
            $patient_id = $request->patient_id;
            $notifications = $notificationsList =[];
            $notificationsQuery = Notification::select('id','title','notes')->where('related_item', $patient_id)->where('cleared', 0)->where('type','mobileParent')->orderBy('created_at','desc')->limit(100);
            if( $request->type =='count') {
                $notifications['count']= $notificationsQuery->count();
                $notifications['list']= $notificationsList;
            } else {
                $notificationsList = $notificationsQuery->get();
                $notifications['count']= count($notificationsList);
                $notifications['list']= $notificationsList;
            }
            return Helper::response(config('constants.response_code.OK'), $notifications);
        }catch(\Exception $e){
            Helper::ExcelLog('getNotifications Error: ' . $e->getMessage(), 'getNotifications.log');
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }
    public function clearNotification(Request $request) {
        try {
            $notification_ids = $request->notification_id;
            foreach($notification_ids as $id) {
                Notification::where('id', $id)->update(['cleared'=>1,'cleared_at'=>date('Y-m-d H:i:s')]);
            }
            $message = (count($notification_ids) > 1)?__('messages.notifications_marked_read'):__('messages.notification_marked_read');
            return Helper::response(config('constants.response_code.OK'), [],$message );
        }catch(\Exception $e){
            Helper::ExcelLog('clearNotification Error: ' . $e->getMessage(), 'clearNotification.log');
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }

    public function getPhysiciansName(Request $request) {
        try {
            $physiciansNameList = DB::table('mssql_patients')->leftJoin('ReferringProviders', 'ReferringProviders.ReferringProviderID', '=','mssql_patients.PrimarycarePhysicianID')->select(DB::raw("'Primary Physician Name' as title"),DB::raw("ifnull(ReferringProviders.ReferringProviderName,'') as name"))->get();
            return Helper::response(config('constants.response_code.OK'), $physiciansNameList );
        }catch(\Exception $e){
            Helper::ExcelLog('getPhysiciansName Error: ' . $e->getMessage(), 'getPhysiciansName.log');
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }

    public function getPatientList(Request $request)
    {   try {
            $patientList = ExternalUserPatients::getPatientList($request->id);
            return Helper::response(config('constants.response_code.OK'), $patientList );
        } catch (\Throwable $th) {
            Helper::ExcelLog('verifyLoginOtp Error: ' . $th->getMessage(), 'getPatientList.log');
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }

    public function getCareTeamList(Request $request)
    {   try {
            $careTeamIds = ['rdod_id'=>'Regional Director of Operations',
                        'dbo_id'=>'Director of Branch Operations',
                        'ccc_id'=>'Client Care Coordinator',
                        'ic_id'=>'Intake Coordinator',
                        'r_id'=>'Recruiter',
                        'ccm_id'=>'Clinical Care Manager'];
            $patientData = DB::table('mssql_patients')->select("mssql_patients.*")
            ->where("mssql_patients.id",$request->patient_id)->first();
            $careTeam = [];
            $i=0;
            foreach($careTeamIds as $k => $v){
                if($patientData->$k != '' && $patientData->$k != 1){
                    $user = User::select('users.*')
                    ->where('id',$patientData->$k)->first();
                    if(!empty($user)){
                        $careTeam[$i]['designation'] = $v;
                        $careTeam[$i]['profilePic'] = $user->profile_picture;
                        $careTeam[$i]['name'] = $user->name;
                        $i++;
                    }
                }
            }
            return Helper::response(config('constants.response_code.OK'), $careTeam );
        } catch (\Throwable $th) {
            Helper::ExcelLog('verifyLoginOtp Error: ' . $th->getMessage(), 'getCareTeamList.log');
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }
}
