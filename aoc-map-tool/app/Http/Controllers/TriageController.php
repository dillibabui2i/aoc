<?php

namespace App\Http\Controllers;

use App\Services\LogSq;
use Illuminate\Http\Request;
use App\Helpers\H;
use App\Helpers\LD;
use App\Helpers\Triage as TriageHelper;
use App\Helpers\Notifications;
use App\MssqlPatients;
use DB;
use App\Triage;
use App\TriageFollowup;
use Illuminate\Support\Facades\Auth;

class TriageController extends Controller
{
    public function getAllCalls(Request $request){
        DB::enableQueryLog();

        $columns = [
            ['name' => 'id','label' => 'id', 'visible' => false, 'width' => '0'],
            ['name' => 'client_name', 'label' => 'Client Name', 'width' => '20%'],
            ['name' => 'original_call_date', 'label' => 'Original Call Day/Time', 'width' => '10%'],
            ['name' => 'bodySystem', 'label' => 'Body Systems(s)', 'width' => '15%'],
            ['name' => 'reason', 'label' => 'Reason(s)', 'width' => '10%'],
            ['name' => 'current_status', 'label' => 'Current Status', 'width' => '10%'],
            ['name' => 'lastDisposition', 'label' => 'Last Disposition', 'width' => '10%'],
            ['name' => 'careCoordination','label' => 'Care Coordination', 'width' => '10%'],
            ['name' => 'isAmbulanceTransfer','label' => 'Ambulance Transfer', 'width' => '10%'],
            ['name' => 'caller_name','label' => 'Caller Name', 'width' => '10%'],
        ];
        $clientId = $request->input('clientId');
        if ($request->ajax()) {
            $res = $this->getFilteredData($request,$columns);

            LogSq::processSqlLogs('TriageController-getAllCalls');
            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($res['totalData']),
                'recordsFiltered' => intval($res['totalFiltered']),
                'data'            => $this->DataProcess($res['data']),
            ]);
        }
        ///return view('clinician.grid', ['columns' => $columns]);
        return view('triage.grid',['columns' => $columns,'clientId'=>$clientId]);
    }
    public function getFilteredData($request,$columns,$mode="showAll"){
        DB::enableQueryLog();

        $reqData = $request->all();
        $clientId = $request->input('clientId');

        $search = $request->input('search.value');
        $limit = $request->input('length');
        $start = $request->input('start');
        $order = isset($columns[$request->input('order.0.column')]) ? $columns[$request->input('order.0.column')]['name'] : 'desc';
        $dir = $request->input('order.0.dir');

        $q = Triage::Select("triageCalls.*",DB::raw("CONCAT(triageCalls.callDate,' ',triageCalls.callStartTime) AS original_call_date"),DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS client_name"),
        DB::raw("CONCAT(triageCalls.callerFirstName,' ',triageCalls.callerLastName) AS caller_name"));

        $q->leftJoin("mssql_patients",function($query){
            $query->on("mssql_patients.id","=","triageCalls.aoc_patient_id");
        });

        $q->addSelect(DB::raw('(select triageFollowup.followUpAction from triageFollowup where triageCalls.id=triageFollowup.triagecall_id and triageFollowup.contactWithCaller=1 order by created_at desc limit 1) as current_status'));
        $q->addSelect(DB::raw('IFNULL((select triageFollowup.finalDisposition from triageFollowup where triageCalls.id=triageFollowup.triagecall_id and triageFollowup.contactWithCaller=1 order by created_at desc limit 1),disposition) as lastDisposition'));
        $q->addSelect(DB::raw('(select triageFollowup.id from triageFollowup where triageCalls.id=triageFollowup.triagecall_id order by created_at desc limit 1) as followUpId'));
        if(isset($clientId) && $clientId != ''){
            $q->where('triageCalls.aoc_patient_id',$clientId);
        }

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if (!empty($search)) {
            $q->whereNested(function($q) use ($search) {
                $q->where('triageCalls.callerFirstName', 'LIKE', "%{$search}%")
                ->orWhere('triageCalls.callerLastName', 'LIKE', "%{$search}%")
                ->orWhere('disposition', 'LIKE', "%{$search}%")
                ->orWhere('reason', 'LIKE', "%{$search}%")
                ->orWhere('bodySystem', 'LIKE', "%{$search}%")
                ->orWhere('mssql_patients.Patient_FirstName', 'LIKE', "%{$search}%")
                ->orWhere('mssql_patients.Patient_LastName', 'LIKE', "%{$search}%")
                ->orWhere(DB::raw('DATE_FORMAT(CONCAT(triageCalls.callDate," ",triageCalls.callStartTime), "' . config('constant')['mysqlsearchdateformat'] . '")'), 'LIKE', "%{$search}%");
            });
            $totalFiltered = $q->count();
        }
        if (!empty($reqData['filters'])) {

            $state = [];
            $branch = [];
            $bodySystems = [];
            $reporter = [];
            $followup = [];
            $fromDate = $toDate = '';
            foreach($request['filters'] as $key => $value) {
                if($value['name']=='triagestate') {
                    $state[] = $value['value'];
                }
                if($value['name']=='triagebranch') {
                    $branch[] = $value['value'];
                }
                if($value['name']=='bodysystems') {
                    $bodySystems[] = $value['value'];
                }
                if($value['name']=='reporter') {
                    $reporter[] = $value['value'];
                }

                if($value['name']=='followup_status') {
                    $followup[] = '%'.$value['value'].'%';
                }

                if (!empty($value['name'] == 'from_date')) {
                    $fromDate = $value['value'];
                }

                if (!empty($value['name'] == 'to_date')) {
                    $toDate = $value['value'];
                }
            }

            if(count($state)>0) {
                $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
                $q->where(function($qr) use ($facilities){
                    foreach($facilities as $facility) {
                        $qr->orWhere("mssql_patients.aoc_facility_id","=",$facility->id);
                    }
                });
            }
            if(count($branch)>0) {
                $q->where(function($qr) use ($branch){
                    foreach($branch as $b) {
                        $qr->orWhere("mssql_patients.aoc_facility_id","=",$b);
                    }
                });
            }
            if(count($reporter)>0) {
                $q->where(function($qr) use ($reporter){
                    foreach($reporter as $r) {
                        $qr->orWhere("triageCalls.user_id","=",$r);
                    }
                });
            }

            if(count($bodySystems)>0) {

                $q->where(function($qr) use ($bodySystems){
                    foreach($bodySystems as $r) {
                        $qr->orWhere("triageCalls.bodySystem",'like',"%{$r}%");
                    }
                });
            }

            if (!empty($followup) && is_array($followup)) {
                $q->havingRaw(implode(' OR ', array_fill(0, count($followup), "current_status LIKE ?")),
                array_map(function($f) {
                    return $f;
                }, $followup));
            }

            if (!empty($fromDate) && !empty($toDate)) {
                $q->whereBetween(DB::raw('CAST(CONCAT(triageCalls.callDate," ",triageCalls.callStartTime) as DATETIME)'), [date('Y-m-d 00:00:00', strtotime($fromDate)), date('Y-m-d 23:59:59', strtotime($toDate))]);
            }
            $totalFiltered = $q->count();
        }

        if($mode != 'export'){
            $data = $q->offset($start)->limit($limit)->orderBy($order, $dir)->get();
        }else{
            $data = $q->get();
        }

        LogSq::processSqlLogs('TriageController-getFilteredData');

        return ['data'=>$data,'totalData'=>$totalData,'totalFiltered'=>$totalFiltered];
    }
    public function getTriageFollowupListOfTriage($ids){
        if(!empty($ids)){
            return DB::table('triageFollowup')->select('triageFollowup.*',DB::raw('DATE_FORMAT(triageFollowup.created_at,"'.config('constant')['mysqlsearchdatetimeformat'].'") as created_at'),DB::raw('CONCAT(triageFollowup.callDate," ",triageFollowup.callStartTime) as callDate'),DB::raw('CASE WHEN triageFollowup.wasClientHospitalized = 1 THEN "Yes" ELSE "No" END as wasClientHospitalized'),DB::raw('CASE WHEN triageFollowup.wasMedPrescribed = 1 THEN "Yes" ELSE "No" END as wasMedPrescribed'),DB::raw('CASE WHEN triageFollowup.wasDiagnosisMade = 1 THEN "Yes" ELSE "No" END as wasDiagnosisMade'),DB::raw('CASE WHEN triageFollowup.survay_sent = 1 THEN "Yes" ELSE "No" END as survay_sent'))->whereIn('triagecall_id',$ids)->get()->toArray();
        }else{
            return [];
        }
    }
    public function setJsonValueInExport($key,$data){
        switch ($key) {
            case 'orderDetails':
                $orders = json_decode($data,true);
                return is_array($orders) ? implode(',',array_column($orders,'order_number')) : '';
            case 'symptomStatus':
                $sym = json_decode($data,true);
                $noteStr = '';
                for ($i=0; $i < count($sym['title']); $i++) {
                    $noteStr .= $sym['title'][$i].' - '. $sym['condition'][$i].': '.$sym['note'][$i]."\n";
                }
                return $noteStr;
            default:
                return $data;
        }
    }
    public function exportTriageCallData(Request $request)
    {
        $dataTableFields = [
            "reporter" => 'Reporter',
            "client_name" => 'Client',
            "clientDOB" => 'Client DOB',
            "FacilityName" => 'Branch Location',
            'original_call_date' => 'Original Call Day/Time',
            "fullName" => 'Triage Nurse',
            'caller_name' => 'Caller Name',
            "clientRelationship" => 'Caller relationship',
            "callerPhone" => 'Caller phone number',
            "callerEmail" => 'Caller Email',
            "Payer" => 'Terminal Payer',
            'bodySystem' => 'Body Systems(s)',
            'reason' => 'Reason(s)',
            "description" => 'Description',
            "disposition" => 'Disposition',
            "careCoordination" => 'Care Coordination',
            "orderDetails" => 'Order #',
            "wasMedPrescribed" => 'Medication',
            "interventionType" => 'Intervention',
            "wentToEmergencyRoom" => 'If you didn\'t talk to us, would you have gone to the Emergency Room?',
            "created_at-1D"=>"24 hr f/u _call_date and time",
            "symptomStatus-1D"=>"24 hr Condition",
            "finalDisposition-1D"=>"24 hr Final disposition",
            "wasClientHospitalized-1D"=>"24 hr Hospitalized",
            "additionCoordination-1D"=>"24 hr Care Coordination",
            "orderDetails-1D"=>"24 hr Order#",
            "wasMedPrescribed-1D"=>"24 hr Medication",
            "interventionType-1D"=>"24 hr Intervention",
            "wasDiagnosisMade-1D"=>"24 hr Diagnosis",
            "survay_sent-1D"=>"24 hr Survey sent",
            "created_at-2D"=>"48 hr f/u _call_date and time",
            "symptomStatus-2D"=>"48 hr Condition",
            "finalDisposition-2D"=>"48 hr Final disposition",
            "wasClientHospitalized-2D"=>"48 hr Hospitalized",
            "additionCoordination-2D"=>"48 hr Care Coordination",
            "orderDetails-2D"=>"48 hr Order#",
            "wasMedPrescribed-2D"=>"48 hr Medication",
            "interventionType-2D"=>"48 hr Intervention",
            "wasDiagnosisMade-2D"=>"48 hr Diagnosis",
            "created_at-7D"=>"7 day f/u _call_date and time",
            "symptomStatus-7D"=>"7 day Condition",
            "finalDisposition-7D"=>"7 day Final disposition",
            "wasClientHospitalized-7D"=>"7 day Hospitalized",
            "additionCoordination-7D"=>"7 day Care Coordination",
            "orderDetails-7D"=>"7 day Order#",
            "wasMedPrescribed-7D"=>"7 day Medication",
            "interventionType-7D"=>"7 day Intervention",
            "wasDiagnosisMade-7D"=>"7 day Diagnosis",
            "survay_sent-7D"=>"7 daySurvey sent",
            "created_at-30D"=>"30 day f/u _call_date and time",
            "symptomStatus-30D"=>"30 day Condition",
            "finalDisposition-30D"=>"30 day Final disposition",
            "wasClientHospitalized-30D"=>"30 day Hospitalized",
            "additionCoordination-30D"=>"30 day Care Coordination",
            "orderDetails-30D"=>"30 day Order#",
            "wasMedPrescribed-30D"=>"30 day Medication",
            "interventionType-30D"=>"30 day Intervention",
            "wasDiagnosisMade-30D"=>"30 day Diagnosis",
        ];
        if(LD::checkFeatureFlag('triage-added-ed-hospitalized-fields')){
            $dataTableFields['lastAttemptOutcome'] = 'Last Attempt Outcome';
        }
        $triageFollowupType = ['1D','2D','7D','30D'];

        $res = $this->getExportTriageCallData($request, [], 'export');
        $data = json_decode(json_encode($res['data']->toArray()), true);
        $triageIds = array_column($data,'id');
        $triageFollowupsDetails = $this->getTriageFollowupListOfTriage($triageIds);
        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=triageCall.csv",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($data, $dataTableFields,$triageFollowupType,$triageFollowupsDetails) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_map(function ($value) {return $value;},array_values($dataTableFields)));
            foreach ($data as $datatemp) {
                $csvData = [];

                //collecting the followup data of the triage
                foreach($triageFollowupType as $d){
                    $followupData[$d] = H::filterMultiArray($triageFollowupsDetails,['triagecall_id'=>$datatemp['id'],'followupType'=>$d]);
                }
                foreach ($dataTableFields as $fields => $val) {
                    if(strpos($fields,'-') !== false){
                        $ex = explode('-',$fields);
                        //Here $ex[1] is 1D,2D ... and the $ex[0] will be the field name
                        if(isset($followupData[$ex[1]][0][$ex[0]])){
                            $csvData[] = H::isJSON($followupData[$ex[1]][0][$ex[0]]) ? $this->setJsonValueInExport($ex[0],$followupData[$ex[1]][0][$ex[0]]) : $followupData[$ex[1]][0][$ex[0]];
                        }else{
                            $csvData[] = '';
                        }
                    }else{
                        $csvData[] = H::isJSON($datatemp[$fields]) ? $this->setJsonValueInExport($fields,$datatemp[$fields]) : $datatemp[$fields];
                    }
                }
                fputcsv($file, $csvData);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
    public function getExportTriageCallData($request,$columns,$mode="showAll"){
        $reqData = $request->all();
        $clientId = $request->input('clientId');

        $search = $request->input('search.value');
        $limit = $request->input('length');
        $start = $request->input('start');
        $order = isset($columns[$request->input('order.0.column')]) ? $columns[$request->input('order.0.column')]['name'] : '';
        $dir = $request->input('order.0.dir');

        if(LD::checkFeatureFlag('triage-added-ed-hospitalized-fields')){
            $q = Triage::Select("triageCalls.*",DB::raw('(CASE WHEN ifnull(mssql_patients.Payer, "") != "" THEN mssql_patients.Payer ELSE mssql_patients.RespitePayer END) as Payer'),DB::raw("CONCAT(triageCalls.callDate,' ',triageCalls.callStartTime) AS original_call_date"),DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS client_name"),
        DB::raw("CONCAT(triageCalls.callerFirstName,' ',triageCalls.callerLastName) AS caller_name"),DB::raw('CASE WHEN triageCalls.emergencyRoom = 1 THEN "Yes" ELSE "No" END as wentToEmergencyRoom'),'mssql_facilities.FacilityName','mssql_providers.fullName', 'users.name as reporter',DB::raw('CASE
                WHEN triageCalls.ed_visited_30d = 1 AND triageCalls.hospitalized_30d = 1 THEN "ED Visit, Currently Hospitalized"
                WHEN triageCalls.ed_visited_30d = 1 AND triageCalls.hospitalized_30d = 0 THEN "ED Visit"
                WHEN triageCalls.ed_visited_30d = 0 AND triageCalls.hospitalized_30d = 1 THEN "Currently Hospitalized"
                WHEN triageCalls.ed_visited_7d = 1 AND triageCalls.hospitalized_7d = 1 THEN "ED Visit, Currently Hospitalized"
                WHEN triageCalls.ed_visited_7d = 1 AND triageCalls.hospitalized_7d = 0 THEN "ED Visit"
                WHEN triageCalls.ed_visited_7d = 0 AND triageCalls.hospitalized_7d = 1 THEN "Currently Hospitalized"
                WHEN triageCalls.ed_visited_2d = 1 AND triageCalls.hospitalized_2d = 1 THEN "ED Visit, Currently Hospitalized"
                WHEN triageCalls.ed_visited_2d = 1 AND triageCalls.hospitalized_2d = 0 THEN "ED Visit"
                WHEN triageCalls.ed_visited_2d = 0 AND triageCalls.hospitalized_2d = 1 THEN "Currently Hospitalized"
                WHEN triageCalls.ed_visited_1d = 1 AND triageCalls.hospitalized_1d = 1 THEN "ED Visit, Currently Hospitalized"
                WHEN triageCalls.ed_visited_1d = 1 AND triageCalls.hospitalized_1d = 0 THEN "ED Visit"
                WHEN triageCalls.ed_visited_1d = 0 AND triageCalls.hospitalized_1d = 1 THEN "Currently Hospitalized"
                ELSE "Unable to Reach" END AS lastAttemptOutcome
        '));
        }else{
            $q = Triage::Select("triageCalls.*",DB::raw('(CASE WHEN ifnull(mssql_patients.Payer, "") != "" THEN mssql_patients.Payer ELSE mssql_patients.RespitePayer END) as Payer'),DB::raw("CONCAT(triageCalls.callDate,' ',triageCalls.callStartTime) AS original_call_date"),DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS client_name"),
        DB::raw("CONCAT(triageCalls.callerFirstName,' ',triageCalls.callerLastName) AS caller_name"),DB::raw('CASE WHEN triageCalls.emergencyRoom = 1 THEN "Yes" ELSE "No" END as wentToEmergencyRoom'),'mssql_facilities.FacilityName','mssql_providers.fullName', 'users.name as reporter');
        }

        $q->leftJoin("mssql_patients",function($query){
            $query->on("mssql_patients.id","=","triageCalls.aoc_patient_id");
        });
        $q->leftJoin("mssql_providers",function($query){
            $query->on("mssql_providers.id","=","triageCalls.aoc_provider_id");
        });
        $q->leftJoin("mssql_facilities",function($query){
            $query->on("mssql_facilities.FacilityID","=","mssql_patients.FacilityID");
        });
        $q->leftJoin("users",function($query){
            $query->on("users.id","=","triageCalls.user_id");
        });
        if(isset($clientId) && $clientId != ''){
            $q->where('triageCalls.aoc_patient_id',$clientId);
        }
        //$q->where('mssql_providers.is_deleted',0);
        $totalData = $q->count();
        $totalFiltered = $totalData;

        if (!empty($search)) {
            $q->whereNested(function($q) use ($search) {
                $q->where('triageCalls.callerFirstName', 'LIKE', "%{$search}%")
                ->orWhere('triageCalls.callerLastName', 'LIKE', "%{$search}%")
                ->orWhere('disposition', 'LIKE', "%{$search}%")
                ->orWhere('reason', 'LIKE', "%{$search}%")
                ->orWhere('bodySystem', 'LIKE', "%{$search}%")
                ->orWhere('mssql_patients.Patient_FirstName', 'LIKE', "%{$search}%")
                ->orWhere('mssql_patients.Patient_LastName', 'LIKE', "%{$search}%")
                ->orWhere(DB::raw('DATE_FORMAT(CONCAT(triageCalls.callDate," ",triageCalls.callStartTime), "' . config('constant')['mysqlsearchdateformat'] . '")'), 'LIKE', "%{$search}%");
            });
            $totalFiltered = $q->count();
        }
        if (!empty($reqData['filters'])) {

            $state = [];
            $branch = [];
            $bodySystems = [];
            $reporter = [];
            $followup = [];
            foreach($request['filters'] as $key => $value) {
                if($value['name']=='triagestate') {
                    $state[] = $value['value'];
                }
                if($value['name']=='triagebranch') {
                    $branch[] = $value['value'];
                }
                if($value['name']=='bodysystems') {
                    $bodySystems[] = $value['value'];
                }
                if($value['name']=='reporter') {
                    $reporter[] = $value['value'];
                }

                if($value['name']=='followup_status') {
                    $followup[] = '%'.$value['value'].'%';
                }
            }
            if(count($state)>0) {
                $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
                $q->where(function($qr) use ($facilities){
                    foreach($facilities as $facility) {
                        $qr->orWhere("mssql_patients.aoc_facility_id","=",$facility->id);
                    }
                });
            }
            if(count($branch)>0) {
                $q->where(function($qr) use ($branch){
                    foreach($branch as $b) {
                        $qr->orWhere("mssql_patients.aoc_facility_id","=",$b);
                    }
                });
            }
            if(count($reporter)>0) {
                $q->where(function($qr) use ($reporter){
                    foreach($reporter as $r) {
                        $qr->orWhere("triageCalls.user_id","=",$r);
                    }
                });
            }

            if(count($bodySystems)>0) {

                $q->where(function($qr) use ($bodySystems){
                    foreach($bodySystems as $r) {
                        $qr->orWhere("triageCalls.bodySystem",'like',"%{$r}%");
                    }
                });
            }
            if(count($followup)>0) {
                $fArray = [];
                foreach($followup as $f) {
                    $fArray[] = "current_status like '%".$f."%'";
                }
                $q->havingRaw(implode(" or ", $fArray));
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
    private function DataProcess($data) {
        foreach ($data as $key => $value) {
            $value->original_call_date = H::getFormatedDate($value->original_call_date).' '.H::getFormatedTime($value->original_call_date);
            if($value->isAmbulanceTransfer==0) {
                $value->isAmbulanceTransfer = "No";
            } else if($value->isAmbulanceTransfer==1) {
                $value->isAmbulanceTransfer = "Yes";

            }
            if(empty($value->current_status)) {
                $value->current_status = "New";
            }
        }
        return $data;
    }

    public function getAjaxCallData(Request $request){
        // Do nothing.
    }

    public function addTriageCallForm(Request $request) {
        if(isset($request->id) && $request->id != NULL){
            $id = $request->id;
            $data = Triage::find($id);
            $patientDetails = [];
            if(isset($data->aoc_patient_id) && $data->aoc_patient_id != ''){
                $patientDetails = MssqlPatients::find($data->aoc_patient_id);
            }
            return view('triage.form', compact('data','patientDetails'));
        }else{
            return view('triage.form');
        }
    }

    public function savePreviousData(Request $request) {
        $reqData = $request->all();
        try {
            $followupExist = TriageFollowup::where("triagecall_id",$reqData['triagecall_id'])->where("followupType",$reqData['followupType'])->first();

            $triageFollowupCallArr = [
                'triagecall_id'=>$reqData['triagecall_id'],
                'followupType'=>$reqData['followupType'],
                'previous_additional_notes'=>$reqData['previous_additional_notes'],
                'previous_action'=>$reqData['previous_action'],
                'created_by'=>$reqData['user_id'],
                'updated_by'=>$reqData['user_id'],
                'contactWithCaller'=>0,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s')
            ];
            if($followupExist) {
                TriageFollowup::where("triagecall_id",$reqData['triagecall_id'])->where("followupType",$reqData['followupType'])->update($triageFollowupCallArr);
            } else {
                TriageFollowup::insertGetId($triageFollowupCallArr);
            }
            return response()->json(['status'=>true]);
        } catch (\Exception $e) {
            H::ExcelLog('Save Triage Original Call:'.$e->getMessage(),'triagefollowup.log');
            return response()->json(['status'=>false,'msg'=>$e->getMessage()]);
        }
    }
    public function getFollowUpForm(Request $request) {
        $id = $request->input('id');
        $type = $request->input('followupType');
        $clientDetails = [];
        $originalCallDetails = [];
        $userName = '';

        if(isset($id)){
            $originalCallDetails = Triage::find($id);
            if(isset($originalCallDetails->aoc_patient_id) && $originalCallDetails->aoc_patient_id != 0){
                $clientDetails = MssqlPatients::find($originalCallDetails->aoc_patient_id);
                $userName = H::getHelper('ConstantHelper')->getUserNameById($originalCallDetails->user_id);
            }
        }
        $followupData = TriageFollowup::where("triagecall_id",$id)->groupBy("followupType")->orderBy('created_at',"desc")->get();

        $followupDataArray = [];
        foreach($followupData as $data) {
            $followupDataArray[$data->followupType] = $data;
        }


        return view('triage.followupForm',['originalCallDetails'=>$originalCallDetails,'followType'=>$type,'clientDetails'=>$clientDetails,'userName'=>$userName,'followupData'=>$followupDataArray]);

    }

    public function saveFollowup(Request $request) {
        try {
            $reqData = $request->all();

            $symptom = json_encode($reqData['symptom']);
            $followupExist = TriageFollowup::where("triagecall_id",$reqData['triagecall_id'])->where("followupType",$reqData['followupType'])->first();
            $additionCoordination = '';
            $orderDetails = '';
            if(isset($reqData['additionCoordination'])){
                if(is_array($reqData['additionCoordination'])){
                    if(in_array('Other',$reqData['additionCoordination'])){
                        $key = array_search('Other', $reqData['additionCoordination']);
                        $reqData['additionCoordination'][$key] = "Other: ".$reqData['additionalCoordinationOther'];
                    }
                    $additionCoordination = implode(',',$reqData['additionCoordination']);
                }else{
                    if(strpos('Other',$reqData['additionCoordination']) !== false){
                        $additionCoordination = "Other: ".$reqData['additionalCoordinationOther'];
                    }else{
                        $additionCoordination = $reqData['additionCoordination'];
                    }
                }
            }

            if((isset($reqData['wereNewOrderRecieived']) || isset($reqData['wasMedPrescribed'])) && isset($reqData['orderdetails']) && !empty($reqData['orderdetails'])) {
                $orderDetails  = $reqData['orderdetails'] != '' ? json_encode($reqData['orderdetails']) : '';
            }else{
                $orderDetails  = NULL;
            }

            $triageFollowupCallArr = [
                'triagecall_id'=>$reqData['triagecall_id'],
                'aoc_patient_id'=>$reqData['aoc_patient_id'],
                'followupType'=> $reqData['followupType'],
                'user_id'=> $reqData['user_id'],
                'symptomStatus'=> $symptom,
                'finalDisposition'=> $reqData['finalDisposition'],
                'despositionActionDate'=>$reqData['despositionActionDate'],
                'clientGoToED'=>isset($reqData['clientGoToED'])?$reqData['clientGoToED']:0,
                'wasClientHospitalized'=>isset($reqData['wasClientHospitalized'])?$reqData['wasClientHospitalized']:0,
                'hospitalizationStartDate'=>$reqData['hospitalizationStartDate'],
                'hospitalizationEndDate'=>$reqData['hospitalizationEndDate'],
                'wasMedPrescribed'=>isset($reqData['wasMedPrescribed'])?$reqData['wasMedPrescribed']:0,
                'drugClassification'=>$reqData['drugClassification'],
                'hasMedicationPickedUp'=>isset($reqData['hasMedicationPickedUp'])?$reqData['hasMedicationPickedUp']:0,
                'wereNewOrderRecieived'=>isset($reqData['wereNewOrderRecieived'])?$reqData['wereNewOrderRecieived']:0,
                'interventionType'=>isset($reqData['interventionType'])?$reqData['interventionType']:'',
                'followUpAction'=>(isset($reqData['followUpAction']) && is_array($reqData['followUpAction'])) ? implode(',',$reqData['followUpAction']) : (isset($reqData['followUpAction']) ? $reqData['followUpAction'] : ''),
                'wasDiagnosisMade'=>isset($reqData['wasDiagnosisMade'])?$reqData['wasDiagnosisMade']:0,
                'ICDCode'=>$reqData['ICDCode'],
                'diagnosis'=>isset($reqData['diagnosis']) ? $reqData['diagnosis'] : '',
                'additionCoordination'=>$additionCoordination,
                'caseConferenceWithCaseManager'=>isset($reqData['caseConferenceWithCaseManager'])?$reqData['caseConferenceWithCaseManager']:0,
                'questionsRegardingPlan'=>$reqData['questionsRegardingPlan'],
                'additionNote'=>$reqData['additionNote'],
                'created_by'=>$reqData['user_id'],
                'updated_by'=>$reqData['user_id'],
                'contactWithCaller'=>isset($reqData['contactWithCaller'])?$reqData['contactWithCaller']:0,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
                "orderDetails"=>$orderDetails
            ];

            $formLabelArr = [
                'symptomStatus'=>'Symptom(s) Status',
                'finalDisposition'=>'Final Disposition',
                'despositionActionDate'=>'Disposition Date of Action',
                'wasClientHospitalized'=>'Was the client hospitalized?',
                'hospitalizationStartDate'=>'Start of Hospitalization',
                'hospitalizationEndDate'=>'End of Hospitalization',
                'wasMedPrescribed'=>'Was medication prescribed?',
                'drugClassification'=>'Drug Classification',
                'hasMedicationPickedUp'=>'Has Medication been picked up?',
                'wereNewOrderRecieived'=>'Were new orders received?',
                'interventionType'=>'Intervention Type',
                'followUpAction'=>'Follow-Up Action',
                'wasDiagnosisMade'=>'Was a diagnosis made?',
                'ICDCode'=>'ICD10 Code',
                'diagnosis'=>'Diagnosis',
                'additionCoordination'=>'Are additional coordination of care services needed?',
                'caseConferenceWithCaseManager'=>'Has a case conference been completed with Care Manager?',
                'questionsRegardingPlan'=>'Any questions regarding care plan. home care services. ER warning s/s?',
                'additionNote'=>'Addition Note',
                'orderDetails'=>'Order Detail'

            ];
            $currentFollowUpData = [];
            $afterUpdateFollowUp = [];
            if (LD::checkFeatureFlag('add-new-survey-sent-toggle-triage-followup') && isset($reqData['survay_sent'])) {
                $triageFollowupCallArr['survay_sent'] = $reqData['survay_sent'];
                $formLabelArr['survay_sent'] = 'Was the survey sent to client?';
            }
            if(isset($reqData['followUpId']) && $reqData['followUpId'] != '') {
                $currentFollowUp = DB::table('triageFollowup')->where('id',$reqData['followUpId']);
                $currentFollowUpData = $currentFollowUp->first();
                $currentFollowUpData = json_decode(json_encode($currentFollowUpData),true);
                $currentFollowUp->update($triageFollowupCallArr);
                $afterUpdateFollowUp = DB::table('triageFollowup')->where('id',$reqData['followUpId'])->first();
                $afterUpdateFollowUp = json_decode(json_encode($afterUpdateFollowUp),true);
                foreach($formLabelArr as $k =>$v){
                    if($currentFollowUpData[$k] == $afterUpdateFollowUp[$k]){
                        unset($formLabelArr[$k]);
                    }
                }
            }
            if($followupExist) {
                TriageFollowup::where("triagecall_id",$reqData['triagecall_id'])->where("followupType",$reqData['followupType'])->update($triageFollowupCallArr);
            } else {
                $entityID = TriageFollowup::insertGetId($triageFollowupCallArr);
            }
            $noteStr = '<div class="p-2 w-150">';
            foreach($formLabelArr as $k => $v){
                if(isset($reqData['followUpId']) && $reqData['followUpId'] != ''){
                    if($triageFollowupCallArr[$k] == '0'){
                        $noteStr .= $v.': changed from Yes to No'.'<br />';
                    }else if($triageFollowupCallArr[$k] == '1'){
                        $noteStr .= $v.': changed from No to Yes'.'<br />';
                    }else if($k == 'symptomStatus'){
                        $sym = json_decode($currentFollowUpData[$k],true);
                        $noteStr .= ' Changed from ';
                        $noteStr .= $v.': <br />';
                        for ($i=0; $i < count($sym['title']); $i++) {
                            $noteStr .= $sym['title'][$i].' - '. $sym['condition'][$i].': '.$sym['note'][$i].'<br>';
                        }
                        $noteStr .= ' to <br/>';
                        $sym = json_decode($triageFollowupCallArr[$k],true);
                        for ($i=0; $i < count($sym['title']); $i++) {
                            $noteStr .= $sym['title'][$i].' - '. $sym['condition'][$i].': '.$sym['note'][$i].'<br>';
                        }
                    } else if($k == 'orderDetails'){
                        $od = json_decode($currentFollowUpData[$k],true);

                        if(!empty($od)) {
                            $noteStr .= ' Changed from ';
                            $noteStr .= $v.': <br />';
                            foreach($od as $ok => $ov) {
                                foreach($ov as $dk => $dv) {
                                    $noteStr.= $dv.'<br>';
                                }$noteStr.="<br/>";
                            }
                            $noteStr .= ' to <br/>';
                        }else{
                            $noteStr .= 'Order Details Added: ';
                        }

                        $od = json_decode($triageFollowupCallArr[$k],true);
                        if(!empty($od)) {
                            $noteStr .= $v.': <br />';
                            foreach($od as $ok => $ov) {
                                foreach($ov as $dk => $dv) {
                                    $noteStr.= $dv.'<br>';
                                }$noteStr.="<br/>";
                            }
                        }
                    }else{
                        $noteStr .= $v.': Changed from '.$currentFollowUpData[$k].' to '.$triageFollowupCallArr[$k].'<br />';
                    }
                }else{
                    if($triageFollowupCallArr[$k] == '0'){
                        $noteStr .= $v.': No'.'<br />';
                    }else if($triageFollowupCallArr[$k] == '1'){
                        $noteStr .= $v.': Yes'.'<br />';
                    }else if($k == 'symptomStatus'){
                        $sym = json_decode($triageFollowupCallArr[$k],true);
                        $noteStr .= $v.': <br />';
                        for ($i=0; $i < count($sym['title']); $i++) {
                            $noteStr .= $sym['title'][$i].' - '. $sym['condition'][$i].': '.$sym['note'][$i].'<br>';
                        }
                    } else if($k == 'orderDetails'){
                        $od = json_decode($triageFollowupCallArr[$k],true);
                        if(!empty($od)) {
                            $noteStr .= $v.': <br />';
                            foreach($od as $ok => $ov) {

                                foreach($ov as $dk => $dv) {
                                    $noteStr.= $dv.'<br>';
                                }$noteStr.="<br/>";
                            }
                        }
                    }else{
                        $noteStr .= $v.': '.$triageFollowupCallArr[$k].'<br />';
                    }
                }
            }
            $noteStr .= "</div>";
            $notesTitle = Auth::user()->name.' submitted a '.H::getHelper("ConstantHelper")->getTriageFollowupType($reqData['followupType']).' Follow-Up call on '.date('m/d/Y H:i A').'.'.': '.$reqData['outcome'].'<br><br>';
            if(isset($reqData['followUpId']) && $reqData['followUpId'] != '') {
                $notesTitle = Auth::user()->name.' updated a '.H::getHelper("ConstantHelper")->getTriageFollowupType($reqData['followupType']).' Follow-Up call on '.date('m/d/Y H:i A').'.'.': '.$reqData['outcome'].'<br><br>';
            }
            $notesData = ['entity' => 'triage', 'notes' => $notesTitle.$noteStr ,'type'=>'followup', 'entity_id' => $reqData['triagecall_id']];
            H::getHelper('ActivityNotes')->setActivityNotes($notesData);
            Triage::where("id",$reqData['triagecall_id'])->update(['step'.$reqData['followupType']=>1,'outcome'=>$reqData['outcome']]);

            return response()->json(['status'=>true]);
        } catch (\Exception $e) {
            H::ExcelLog('Save TriageFollowup Call:'.$e->getMessage(),'triagefollowup.log');
            return response()->json(['status'=>false,'msg'=>$e->getMessage()]);
        }

    }
    function saveNotesTriageFollowUp(Request $request){
        $reqData = $request->all();
        $notesData = ['entity' => 'triage', 'notes' => Auth::user()->name.' submitted a '.H::getHelper("ConstantHelper")->getTriageFollowupType($reqData['followupType']).' Notes on '.date('m/d/Y H:i A').'.<br><br>'.$reqData['outcome'].':<br><br>'.$reqData['notes'] ,'type'=>'followup', 'entity_id' => $reqData['triagecall_id']];
        H::getHelper('ActivityNotes')->setActivityNotes($notesData);
        if(isset($reqData['outcome']) && $reqData['outcome'] != '' && isset($reqData['triagecall_id']) && $reqData['triagecall_id'] != ''){

            if(LD::checkFeatureFlag('triage-attempt-count-changes')){
                //Attempt count Logic
                $prepareUpdateArr = [];
                if(strpos($reqData['outcome'],'Attempt') !== false){
                    $prepareUpdateArr['outcome'] = $reqData['outcome'];
                    $countToUpdate = explode(' ',$reqData['outcome']);
                    $countToUpdate = $countToUpdate[1];
                    switch($reqData['followupType']){
                        case '1D':
                            $prepareUpdateArr['attempts_24'] = $countToUpdate;
                            if($countToUpdate == 1){
                                $prepareUpdateArr['step1D'] = 1;
                            }
                            if(LD::checkFeatureFlag('triage-added-ed-hospitalized-fields')){
                                $prepareUpdateArr['hospitalized_1d'] = $reqData['isHospitalized'] == 'true' ? 1 : 0;
                                $prepareUpdateArr['ed_visited_1d'] = $reqData['isEd'] == 'true' ? 1 : 0;
                            }
                            break;
                        case '2D':
                            $prepareUpdateArr['attempts_48'] = $countToUpdate;
                            if($countToUpdate == 3){
                                $prepareUpdateArr['step2D'] = 1;
                            }
                            if(LD::checkFeatureFlag('triage-added-ed-hospitalized-fields')){
                                $prepareUpdateArr['hospitalized_2d'] = $reqData['isHospitalized'] == 'true' ? 1 : 0;
                                $prepareUpdateArr['ed_visited_2d'] = $reqData['isEd'] == 'true' ? 1 : 0;
                            }
                            break;
                        case '7D':
                            $prepareUpdateArr['attempts_7day'] = $countToUpdate;
                            if($countToUpdate == 3){
                                $prepareUpdateArr['step7D'] = 1;
                            }
                            if(LD::checkFeatureFlag('triage-added-ed-hospitalized-fields')){
                                $prepareUpdateArr['hospitalized_7d'] = $reqData['isHospitalized'] == 'true' ? 1 : 0;
                                $prepareUpdateArr['ed_visited_7d'] = $reqData['isEd'] == 'true' ? 1 : 0;
                            }
                            break;
                        case '30D':
                            $prepareUpdateArr['attempts_30day'] = $countToUpdate;
                            if($countToUpdate == 3){
                                $prepareUpdateArr['step30D'] = 1;
                            }
                            if(LD::checkFeatureFlag('triage-added-ed-hospitalized-fields')){
                                $prepareUpdateArr['hospitalized_30d'] = $reqData['isHospitalized'] == 'true' ? 1 : 0;
                                $prepareUpdateArr['ed_visited_30d'] = $reqData['isEd'] == 'true' ? 1 : 0;
                            }
                            break;
                        default:
                            break;
                    }
                    Triage::where('id', $reqData['triagecall_id'])->update($prepareUpdateArr);
                }else{
                    Triage::where('id', $reqData['triagecall_id'])->update(['outcome'=>$reqData['outcome']]);
                }
            }else{
                if($reqData['outcome'] == 'Attempt 1'){
                    $count = 1;
                    Triage::where('id', $reqData['triagecall_id'])->update(['outcome'=>$reqData['outcome'], 'outcome_attempt' => $count]);
                }elseif($reqData['outcome'] == 'Attempt 2'){
                    $count = 2;
                    Triage::where('id', $reqData['triagecall_id'])->update(['outcome'=>$reqData['outcome'], 'outcome_attempt' => $count]);
                }elseif($reqData['outcome'] == 'Attempt 3'){
                    $count = 0;
                    $setp = '';
                    switch($reqData['followupType']){
                        case '1D':
                           $setp = 'step1D';
                           break;
                        case '2D':
                            $setp = 'step2D';
                            break;
                        case '7D':
                            $setp = 'step7D';
                            break;
                        case '30D':
                            $setp = 'step30D';
                            break;
                        default:
                            break;
                    }
                    Triage::where('id', $reqData['triagecall_id'])->update(['outcome'=>$reqData['outcome'], 'outcome_attempt' => $count, $setp => 1]);
                }else{
                    Triage::where('id', $reqData['triagecall_id'])->update(['outcome'=>$reqData['outcome']]);
                }
            }
        }
        return response()->json(['status'=>true]);
    }
    public function save(Request $request){
        $reqData = $request->all();
         try {
            $callDate = date('Y-m-d');
            $callStartTime = date('H:i:s');
            // $callDate = date('Y-m-d',strtotime($reqData['callDate']));
            // $callStartTime = date('H:i:s',strtotime($reqData['callStartTime']));
            $careCoordination = '';
            if(isset($reqData['careCoordination'])){
                if(is_array($reqData['careCoordination']) && !empty($reqData['careCoordination'])){
                    $careCoordination = implode(',',$reqData['careCoordination']);
                }else{
                    $careCoordination = $reqData['careCoordination'];
                }
            }
            $triageCallArr = [
                'user_id'=>$reqData['user_id'],
                'aoc_patient_id'=>$reqData['aoc_patient_id'],
                'callDate'=>$callDate,
                'callStartTime'=>$callStartTime,
                'callerFirstName'=>$reqData['callerFirstName'],
                'callerLastName'=>$reqData['callerLastName'],
                'callerPhone'=>$reqData['callerPhone'],
                'callerEmail'=>$reqData['callerEmail'],
                'clientRelationship'=>$reqData['clientRelationship'],
                'clientDOB'=>date('Y-m-d',strtotime($reqData['clientDOB'])),
                'bodySystem'=>is_array($reqData['bodySystem']) && !empty($reqData['bodySystem']) ? implode(',',$reqData['bodySystem']) : $reqData['bodySystem'],
                'reason'=>is_array($reqData['reason']) && !empty($reqData['reason']) ? implode(',',$reqData['reason']) : $reqData['reason'],
                'disposition'=>$reqData['disposition'],
                'isAmbulanceTransfer'=>isset($reqData['isAmbulanceTransfer']) && $reqData['isAmbulanceTransfer'] == 'on' ? 1 : 0,
                'suggestedDatetime'=>isset($reqData['suggestedDatetime']) ? $reqData['suggestedDatetime'] : '',
                'emergencyRoom'=>isset($reqData['emergencyRoom']) && $reqData['emergencyRoom'] == 'on' ? 1 : 0,
                'description'=>$reqData['description'],
                'careCoordination'=>$careCoordination,
                'whereYouGone'=>isset($reqData['whereYouGone']) ? $reqData['whereYouGone'] : '',
                'created_at'=>date('Y-m-d H:i:s'),
                'created_by'=>Auth::user()->id,
                'updated_at'=>date('Y-m-d H:i:s'),
                'updated_by'=>Auth::user()->id,
                'terminalPlayer' => $reqData['terminalPlayer'] ?? '',
            ];

            if (LD::checkFeatureFlag('triage-add-notes')) {
                $triageCallArr['notes'] = $reqData['notes'] ?? '';
            }
            if (LD::checkFeatureFlag('add-order-detail-field-to-triage-incoming-call-form')) {
                $orderDetails = '{}';
                if (LD::checkFeatureFlag('incoming-call-form-changes-triage')) {
                    if((isset($reqData['wereNewOrderRecieived']) || isset($reqData['wasMedPrescribed'])) && !empty($reqData['orderdetails'])) {
                        $orderDetails  = json_encode($reqData['orderdetails']);
                    }
                } else {
                    if(isset($reqData['wereNewOrderRecieived']) && !empty($reqData['orderdetails'])) {
                        $orderDetails  = json_encode($reqData['orderdetails']);
                    }
                }
                $triageCallArr['wereNewOrderRecieived'] = isset($reqData['wereNewOrderRecieived'])?$reqData['wereNewOrderRecieived']:0;
                $triageCallArr['interventionType'] = isset($reqData['interventionType'])?$reqData['interventionType']:'';
                $triageCallArr['followUpAction'] = (isset($reqData['followUpAction']) && is_array($reqData['followUpAction'])) ? implode(',',$reqData['followUpAction']) : (isset($reqData['followUpAction']) ? $reqData['followUpAction'] : '');
                $triageCallArr['orderDetails'] = $orderDetails;
            }
            if (LD::checkFeatureFlag('triage-incoming-call-form-changes')) {
                $triageCallArr['wasMedPrescribed']= isset($reqData['wasMedPrescribed'])?$reqData['wasMedPrescribed']:0;
                $triageCallArr['drugClassification']= $reqData['drugClassification'];
                $triageCallArr['hasMedicationPickedUp']= isset($reqData['hasMedicationPickedUp'])?$reqData['hasMedicationPickedUp']:0;
                $triageCallArr['emergencyDepartment']=$reqData['emergencyDepartment'];
                $triageCallArr['whereYouGone']=$reqData['emergencyDepartment'];
                if($reqData['emergencyDepartment'] == 'other') {
                    $triageCallArr['emergencyDepartment']=$reqData['emergencyDepartmentOther'];
                }
            }
            if(LD::checkFeatureFlag('pull-information-from-kantime-triage-incoming-call-form')) {
                $triageCallArr['aoc_provider_id'] = $reqData['aoc_provider_id'] ?? '';
            }
            $id = Triage::insertGetId($triageCallArr);

            if(LD::checkFeatureFlag('add-original-call-notes-to-activity-log-triage')){
                H::getHelper('Triage')->formateNotes($reqData, $id);
            }else{
                $notesData = ['entity' => 'triage', 'notes' => Auth::user()->name.'
                submitted new triage call on '.date('m/d/Y H:i A').'.', 'entity_id' => $id];
               H::getHelper('ActivityNotes')->setActivityNotes($notesData);
            }
            //Sending Notification if Care Coordination is been selected as "Other" and "Not Available"
            if(isset($reqData['careCoordination']) && $reqData['careCoordination'] == 'Not Listed'){
                foreach(config('constant')['triageCareCoordinationNotification'] as $v){
                    $notificationData['user_id'] = $v;
                    $notificationData['title'] = 'Triage Call Added';
                    $notificationData['type'] = 'triageCall';
                    $notificationData['related_item'] = $id;
                    $notificationData['notes'] = 'Triage Call Added';
                    Notifications::addNotification($notificationData);
                }
            }
            return response()->json(['status'=>true]);
        } catch (\Exception $e) {
            H::ExcelLog('Save Triage Call:'.$e->getMessage(),'triage.log');
            return response()->json(['status'=>false,'msg'=>$e->getMessage().'-'.$e->getLine()]);
        }
    }

    public function editTriageCallDate(Request $request){
        $reqData = $request->all();
        try {
            $triage = Triage::find($reqData['triage_id']);
            //update Triage Call
            $callDate = date('Y-m-d',strtotime($reqData['changeCallDate']));
            $callStartTime = date('H:i:s',strtotime($reqData['changeCallStartTime']));
            $reporter = $reqData['user_id'];
            Triage::where('id', $reqData['triage_id'])->update(['callDate'=>$callDate,'callStartTime'=>$callStartTime,'user_id'=>$reporter]);
            $notesData = ['entity' => 'triage', 'notes' => Auth::user()->name.'
            changed the triage call from '.H::getFormatedDateTime($triage->callDate.' '.$triage->callStartTime).' to '.H::getFormatedDateTime($callDate.' '.$callStartTime).'.', 'entity_id' => $reqData['triage_id']];
            H::getHelper('ActivityNotes')->setActivityNotes($notesData);

            //Notes for the reporter change
            if($reporter != $triage->user_id){
                $notesData = ['entity' => 'triage', 'notes' => Auth::user()->name.'
                changed the triage call reporter from '.H::getUserNameById($triage->user_id).' to '.H::getUserNameById($reporter).'.', 'entity_id' => $reqData['triage_id']];
                H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                H::ExcelLog('Triage call(Triage ID:'.$reqData['triage_id'].') reporter changed from user ID '.$triage->user_id.' to '.$reporter.' by '.Auth::user()->name,'triage.log');
            }

            H::ExcelLog('Triage call(Triage ID:'.$reqData['triage_id'].') date and time changes from '.$triage->callDate.' '.$triage->callStartTime.' to '.$callDate.' '.$callStartTime.' by '.Auth::user()->name,'triage.log');
            return response()->json(['status'=>true,'msg'=>'Date and time has been changed!']);
        } catch (\Exception $e) {
            H::ExcelLog('editTriageCallDate:'.$e->getMessage(),'triage.log');
            return response()->json(['status'=>false,'msg'=>$e->getMessage()]);
        }
    }

    public function editTriage(Request $request){
        $reqData = $request->all();
         try {
            $callDate = date('Y-m-d');
            $callStartTime = date('H:i:s');
            $careCoordination = '';
            if(isset($reqData['careCoordination'])){
                if(is_array($reqData['careCoordination']) && !empty($reqData['careCoordination'])){
                    $careCoordination = implode(',',$reqData['careCoordination']);
                }else{
                    $careCoordination = $reqData['careCoordination'];
                }
            }

            $triageCallArr = [
                'user_id'=>$reqData['user_id'],
                'aoc_patient_id'=>$reqData['aoc_patient_id'],
                // 'callDate'=>$callDate,
                // 'callStartTime'=>$callStartTime,
                'callerFirstName'=>$reqData['callerFirstName'],
                'callerLastName'=>$reqData['callerLastName'],
                'callerPhone'=>$reqData['callerPhone'],
                'callerEmail'=>$reqData['callerEmail'],
                'clientRelationship'=>$reqData['clientRelationship'],
                'clientDOB'=>date('Y-m-d',strtotime($reqData['clientDOB'])),
                'bodySystem'=>is_array($reqData['bodySystem']) && !empty($reqData['bodySystem']) ? implode(',',$reqData['bodySystem']) : $reqData['bodySystem'],
                'reason'=>is_array($reqData['reason']) && !empty($reqData['reason']) ? implode(',',$reqData['reason']) : $reqData['reason'],
                'disposition'=>$reqData['disposition'],
                'isAmbulanceTransfer'=>isset($reqData['isAmbulanceTransfer']) && $reqData['isAmbulanceTransfer'] == 'on' ? 1 : 0,
                'suggestedDatetime'=>isset($reqData['suggestedDatetime']) ? $reqData['suggestedDatetime'] : '',
                'emergencyRoom'=>isset($reqData['emergencyRoom']) && $reqData['emergencyRoom'] == 'on' ? 1 : 0,
                'description'=>$reqData['description'],
                'careCoordination'=>$careCoordination,
                'whereYouGone'=>isset($reqData['whereYouGone']) ? $reqData['whereYouGone'] : '',
                'created_at'=>date('Y-m-d H:i:s'),
                'created_by'=>Auth::user()->id,
                'updated_at'=>date('Y-m-d H:i:s'),
                'updated_by'=>Auth::user()->id,
                'terminalPlayer' => $reqData['terminalPlayer'] ?? '',
            ];
            // if(H::canPermissions(['edit-triage-call-date'])){
            //     if(isset($reqData['callDate']) && $reqData['callDate'] != ''){
            //         $triageCallArr['callDate'] = date('Y-m-d',strtotime($reqData['callDate']));
            //     }

            //     if(isset($reqData['callStartTime']) && $reqData['callStartTime'] != ''){
            //         $triageCallArr['callStartTime'] = date('H:i:s',strtotime($reqData['callStartTime']));
            //     }
            // }

            if (LD::checkFeatureFlag('triage-add-notes')) {
                $triageCallArr['notes'] = $reqData['notes'] ?? '';
            }
            if (LD::checkFeatureFlag('add-order-detail-field-to-triage-incoming-call-form')) {
                $orderDetails = '{}';
                if (LD::checkFeatureFlag('incoming-call-form-changes-triage')) {
                    if((isset($reqData['wereNewOrderRecieived']) || isset($reqData['wasMedPrescribed'])) && !empty($reqData['orderdetails'])) {
                        $orderDetails  = json_encode($reqData['orderdetails']);
                    }
                } else {
                    if(isset($reqData['wereNewOrderRecieived']) && !empty($reqData['orderdetails'])) {
                        $orderDetails  = json_encode($reqData['orderdetails']);
                    }
                }
                $triageCallArr['wereNewOrderRecieived'] = isset($reqData['wereNewOrderRecieived'])?$reqData['wereNewOrderRecieived']:0;
                $triageCallArr['interventionType'] = isset($reqData['interventionType'])?$reqData['interventionType']:'';
                $triageCallArr['followUpAction'] = (isset($reqData['followUpAction']) && is_array($reqData['followUpAction'])) ? implode(',',$reqData['followUpAction']) : (isset($reqData['followUpAction']) ? $reqData['followUpAction'] : '');
                $triageCallArr['orderDetails'] = $orderDetails;
            }
            if (LD::checkFeatureFlag('triage-incoming-call-form-changes')) {
                $triageCallArr['wasMedPrescribed']= isset($reqData['wasMedPrescribed'])?$reqData['wasMedPrescribed']:0;
                $triageCallArr['drugClassification']= $reqData['drugClassification'];
                $triageCallArr['hasMedicationPickedUp']= isset($reqData['hasMedicationPickedUp'])?$reqData['hasMedicationPickedUp']:0;
                $triageCallArr['emergencyDepartment']= isset($reqData['emergencyDepartment']) ? $reqData['emergencyDepartment'] : NULL;
                $triageCallArr['whereYouGone']= isset($reqData['emergencyDepartment']) ? $reqData['emergencyDepartment'] : NULL;
                if(isset($reqData['emergencyDepartment'])  && $reqData['emergencyDepartment'] == 'other') {
                    $triageCallArr['emergencyDepartment']=$reqData['emergencyDepartmentOther'];
                }
            }
            if(LD::checkFeatureFlag('pull-information-from-kantime-triage-incoming-call-form')) {
                $triageCallArr['aoc_provider_id'] = $reqData['aoc_provider_id'] ?? '';
            }
            //update Triage Call
            Triage::where('id', $reqData['trige_id'])->update($triageCallArr);
            $id = $reqData['trige_id'];
            $notesData = ['entity' => 'triage', 'notes' => Auth::user()->name.'
             Edited triage call on '.date('m/d/Y H:i A').'.', 'entity_id' => $id];
            H::getHelper('ActivityNotes')->setActivityNotes($notesData);
            //Sending Notification if Care Coordination is been selected as "Other" and "Not Available"
            if(isset($reqData['careCoordination']) && $reqData['careCoordination'] == 'Not Listed'){
                foreach(config('constant')['triageCareCoordinationNotification'] as $v){
                    $notificationData['user_id'] = $v;
                    $notificationData['title'] = 'Triage Call Edited';
                    $notificationData['type'] = 'triageCall';
                    $notificationData['related_item'] = $id;
                    $notificationData['notes'] = 'Triage Call Edited';
                    Notifications::addNotification($notificationData);
                }
            }
            return response()->json(['status'=>true]);
        } catch (\Exception $e) {
            H::ExcelLog('Edit Triage Call:'.$e->getMessage(),'triage.log');
            return response()->json(['status'=>false,'msg'=>$e->getMessage()]);
        }
    }

    public function checkingClientCallsAvailability(Request $request){
        $clientId = $request->input('clientId');
        $count = 0;
        $patientDetails = [];
        if(isset($clientId) && $clientId != ''){
            $count = Triage::where('aoc_patient_id',$clientId)->count();
            $patientDetails = MssqlPatients::find($clientId)->toArray();
            if(LD::checkFeatureFlag('add-client-id-and-client-location-triage-form')){
                $patientDetails['facilityName'] = DB::table('mssql_facilities')
                ->where('id', $patientDetails['aoc_facility_id'])
                ->value('facilityName');
            }
            $patientDetails['Patient_DOB'] = isset($patientDetails['Patient_DOB']) ? date('m/d/Y',strtotime($patientDetails['Patient_DOB'])) : '';
        }
        return response()->json(['count'=>$count,'patientDetails'=>$patientDetails]);
    }
    public function getTriageCallOfClient(Request $request){
        $columns = TriageHelper::getCallViewFields();
        $clientId = $request->input('clientId');
        $filters = H::getFilterInputArr($request->all());

        $search = $request->input('search.value');
        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')]['name'];
        $dir = $request->input('order.0.dir');

        if(isset($clientId) && $clientId != ''){
            $q = DB::table('triageCalls')->select('id',DB::raw('CONCAT(callerFirstName," ",callerLastName) as callerName'),DB::raw('DATE_FORMAT(CONCAT(callDate," ",callStartTime),"'.config('constant')['mysqlsearchdatetimeformat'].'") as callDate'),'bodySystem','reason','careCoordination',DB::raw('CASE WHEN isAmbulanceTransfer = 1 THEN "Yes" ELSE "No" END as isAmbulanceTransfer'))->where('aoc_patient_id',$clientId);
            $totalData = $q->count();
            $totalFiltered = $totalData;
            if (!empty($search)) {
                $q->where(function ($query) use ($search){
                    $query->where('callerFirstName', 'LIKE', "%{$search}%");
                    $query->orWhere('callerLastName', 'LIKE', "%{$search}%");
                    $query->orWhere('bodySystem', 'LIKE', "%{$search}%");
                    $query->orWhere('careCoordination', 'LIKE', "%{$search}%");
                    $query->orWhere('reason', 'LIKE', "%{$search}%");
                });
                $totalFiltered = $q->count();
            }
            if ($order == 'callDate') {
                $q->orderBy(DB::raw('triageCalls.callDate'), $dir)
                  ->orderBy(DB::raw('triageCalls.callStartTime'), $dir);
            } else {
                $q->orderBy($order, $dir);
            }
            $data = $q->offset($start)->limit($limit)->get();
            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($totalData),
                'recordsFiltered' => intval($totalFiltered),
                'data'            => $data,
            ]);
        }

    }
    public function getTriageCallDetails(Request $request){
        $dataTableFields = TriageHelper::getCallViewFields();
        $id = $request->input('id');
        $triage = [];
        $triageFollowUpDetails = [];
        $patientsDetails = [];
        $urgentDisposition = ['Go to ED Now','Go to ED Now or to PCP Office with PCP Approval','Office Visit/Telehealth Visit Now or Urgent Care Now'];
        if(isset($id) && $id != ''){
            $triage = Triage::find($id);
            if(isset($triage->aoc_patient_id)){
                $patientsDetails = MssqlPatients::find($triage->aoc_patient_id);
                $triageFollowUpDetails = DB::table('triageCalls')->leftJoin('triageFollowup', 'triageFollowup.triagecall_id', '=', 'triageCalls.id')->where('triageCalls.aoc_patient_id',$triage->aoc_patient_id)->select('bodySystem','triageCalls.disposition as triageCallsDisposition','triageFollowup.finalDisposition as triageFollowupDisposition','isAmbulanceTransfer',)->get();
            }
        }
        $countArray = ['bodySystem'=>[],'disposition'=>0,'ambulance'=>0,'openCall'=>count($triageFollowUpDetails)];
        foreach ($triageFollowUpDetails as $key => $value) {
            $countArray['bodySystem'] = array_merge($countArray['bodySystem'],explode(',',$value->bodySystem));
            if(in_array($value->triageCallsDisposition,$urgentDisposition)){
                $countArray['disposition']++;
            }
            if(in_array($value->triageFollowupDisposition,$urgentDisposition)){
                $countArray['disposition']++;
            }
            if($value->isAmbulanceTransfer == 1){
                $countArray['ambulance']++;
            }
        }
        $countArray['bodySystem'] = array_unique($countArray['bodySystem']);
        $countArray['bodySystem'] = count($countArray['bodySystem']);
        return view('triage.details',['triage'=>$triage,'patientsDetails'=>$patientsDetails,'countArray'=>$countArray,'dataTableFields'=>$dataTableFields]);
    }
    public function getFollowUpCallDetails(Request $request){
        $reqData = $request->all();
        if(isset($reqData['id'])){
            $triageDetails = Triage::find($reqData['id']);
            $triageFollowUpDetails = TriageFollowup::where('triagecall_id',$reqData['id'])->get()->toArray();
            return view('triage._gridFollowUpDetails',['data'=>$reqData,'triageFollowUpDetails'=>$triageFollowUpDetails,'triageDetails'=>$triageDetails]);
        }

    }
    public function getTriageEditForm(Request $request){
        $reqData = $request->all();
        if(isset($reqData['id'])){
            $triageDetails = Triage::find($reqData['id']);
            $triageFollowUpDetails = TriageFollowup::where('triagecall_id',$reqData['id'])->get()->toArray();
            return view('triage._gridFollowUpDetailsEdit',['data'=>$reqData,'triageFollowUpDetails'=>$triageFollowUpDetails,'triageDetails'=>$triageDetails]);
        }
    }
    public function loadTriageEditForm(Request $request){
        if(isset($request->id) && $request->id != NULL && $request->type == 'original'){
            $id = $request->id;
            $data = Triage::find($id);
            $patientDetails = [];
            if(isset($data->aoc_patient_id) && $data->aoc_patient_id != ''){
                $patientDetails = MssqlPatients::find($data->aoc_patient_id);
            }
            return view('triage.form', compact('data','patientDetails'));
        }else{
            $id = $request->input('id');
            $triage = [];
            $followUpDetails = [];
            $attemptCount = 0;
            if(isset($id) && $id != ''){
                $followUpDetails = TriageFollowup::find($id);
                if(isset($followUpDetails->triagecall_id)){
                    $triage = Triage::find($followUpDetails->triagecall_id);
                    $attemptCount = Triage::where('id', $id)->value('outcome_attempt');
                }
            }
            return view('triage.outcomeDetails',['followUpDetails'=>$followUpDetails,'triage'=>$triage,'followUpId'=>$id,'followupData'=>[],'followType'=>$followUpDetails->followupType,'attempt_count' => $attemptCount]);
            // return view('triage.followupFormOutcome',['followUpDetails'=>$followUpDetails,'originalCallDetails'=>$triage,'followUpId'=>$id,'followupData'=>[],'followType'=>$followUpDetails->followupType]);
        }
    }


    public function getOutcomeDetails(Request $request){
        $id = $request->input('id');
        $type = $request->input('followupType');
        $triage = [];
        $patientsDetails = [];
        if(isset($id) && $id != ''){
            $triage = Triage::find($id);
            if(isset($triage->aoc_patient_id)){
                $patientsDetails = MssqlPatients::find($triage->aoc_patient_id);
            }
        }
        $followupData = TriageFollowup::where("triagecall_id",$id)->groupBy("followupType")->orderBy('created_at',"desc")->get();
        $attemptCount = Triage::where('id', $id)->value('outcome_attempt');
        $followupDataArray = [];
        foreach($followupData as $data) {
            $followupDataArray[$data->followupType] = $data;
        }
        return view('triage.outcomeDetails',['triage'=>$triage,'patientsDetails'=>$patientsDetails,'followType'=>$type, 'followupData'=>$followupDataArray, 'attempt_count' => $attemptCount]);
    }

    public function getTerminalPayer(Request $request)
    {
        $term = str_replace('  ', ' ', str_replace(',', '', $request->term));
        $payerList = MssqlPatients::distinct()->select('Payer')
        ->where('Payer','like','%'.$term.'%')
        ->where('Payer','not like','%EVV%')
        ->where('Payer','not like','%Therapy%')
        ->limit(15)
        ->orderBy('Payer')
        ->get()->toArray();
        $payerListData = [];
        foreach ($payerList as $payerSubData) {
            $payerListData[] = ['payer'=>$payerSubData['Payer'],'label' => $payerSubData['Payer']];
        }
        return response()->json($payerListData);
    }

    public function editFollowUp(Request $request){
        $id = $request->input('id');
        $triage = [];
        $followUpDetails = [];
        $patientsDetails = [];
        $attemptCount = 0;
        if(isset($id) && $id != ''){
            $followUpDetails = TriageFollowup::find($id);
            if(isset($followUpDetails->aoc_patient_id)){
                $patientsDetails = MssqlPatients::find($followUpDetails->aoc_patient_id);
            }
            if(isset($followUpDetails->triagecall_id)){
                $triage = Triage::find($followUpDetails->triagecall_id);
                $attemptCount = $triage->outcome_attempt;
            }
        }
        return view('triage.outcomeDetails',['followUpDetails'=>$followUpDetails,'patientsDetails'=>$patientsDetails,'triage'=>$triage,'followUpId'=>$id,'followupData'=>[],'followType'=>$followUpDetails->followupType, 'attempt_count' => $attemptCount]);
    }
    public function getEmergencyDepartment(Request $request)
    {
        $emergencyDepartmentData = [];
        if (LD::checkFeatureFlag('incoming-call-form-changes-triage')) {
            $facilityNameData = DB::table('mssql_facilities')->select('FacilityName')->get();
            foreach ($facilityNameData as $facilityName) {
                $emergencyDepartmentList =  DB::table('emergency_department')->whereRaw("FIND_IN_SET(?, location)",[$facilityName->FacilityName])->get()->toArray();
                foreach ($emergencyDepartmentList as $emergencyDepartmentSubData) {
                    $emergencyDepartmentData[] = ['text'=>$emergencyDepartmentSubData->facility_name,'label' => $emergencyDepartmentSubData->facility_name];
                }
            }
        } else {
            $aoc_patient_id =$request->aoc_patient_id;
            $facilityNameData = DB::table('mssql_patients')->select('mssql_facilities.FacilityName')->leftJoin('mssql_facilities', 'mssql_patients.aoc_facility_id', '=', 'mssql_facilities.id')->where('mssql_patients.id',$aoc_patient_id)->first();
            if(!empty($facilityNameData)) {
                $emergencyDepartmentList =  DB::table('emergency_department')->whereRaw("FIND_IN_SET(?, location)",[$facilityNameData->FacilityName])->get()->toArray();
                foreach ($emergencyDepartmentList as $emergencyDepartmentSubData) {
                    $emergencyDepartmentData[] = ['text'=>$emergencyDepartmentSubData->facility_name,'label' => $emergencyDepartmentSubData->facility_name];
                }
            }
        }
        return response()->json($emergencyDepartmentData);
    }

    public function getClientName(Request $request){
        $aoc_patient_id = $request->id;
        $clientlist = DB::table('mssql_patients')
        ->select(
            'mssql_patients.Patient_FirstName',
            'mssql_patients.Patient_LastName',
            'mssql_patients.Payer',
        )->where('id', $aoc_patient_id)->first();

        $name = $clientlist->Patient_FirstName. ' ' .$clientlist->Patient_LastName;
        $responseData = array('name' => $name, 'payer' => $clientlist->Payer);
        return response()->json($responseData);
    }

    public function getReporterList(Request $request){
        $list = H::getHelper('ConstantHelper')->getReporterList(true);
        $arr = [];
        foreach($list as $k => $l){
            $arr[] = ['id'=>$l['value'],'label'=>$l['text']];
        }
        return $arr;
    }
}
