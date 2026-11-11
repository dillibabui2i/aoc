<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\H;
use DataTables;
use App\MssqlProviders;
use DB;
use App\Helpers\LD;
use App\Helpers\Notifications;

class StatusChangeRequestController extends Controller
{
    public function reloadCountStatusChangeRequests(Request $request) {
        return json_encode(["counter"=>H::getHelper("StatusChangeRequests")->getStatusChangeRequests("dashboard",$request->input("type"), $request->input("filters"),1,$request),"type"=>$request->input("type")]);
    }
    public function getStatusChangeRequestsDashboard(Request $request) {

        $data = H::getHelper("StatusChangeRequests")->getStatusChangeRequests("dashboard",$request->input("type"), $request->input("filters"),0,$request);
        return Datatables::of($data)->addColumn('Action', function ($row){
            $btn = '';
            if (isset($row->id) && $row->id != '') {
                $btn .= '<a href="javascript:void(0)" onclick=\'showStatusChangeRequestNextStepForm("'.$row->id.'");\' ><i class="fa fa-calendar" aria-hidden="true"></i></a> | ';
                $btn .= '<a href="javascript:void(0)" onclick="getClinicianProfile('.$row->id.')"  ><i class="fa fa-eye" aria-hidden="true"></i></a> |';
                $btn .= '<a data-bs-dismiss="modal" href="javascript:void(0);" onclick="event.preventDefault();openNotes('.$row->id.',\'Clinician\',\'Status Change Request Notes Added\')"><i class="fa  fa-sticky-note-o"></i></a>';
            }
            return $btn;
        }) ->editColumn('lastActivity', function($data) {
            return H::getFormatedDateTime($data->request_last_followup).'-'.$data->lastActivity;
        })
        ->editColumn('request_date', function ($row) {
            if(isset($row->request_date) && $row->request_date != '00/00/0000'){
                return [
                    'display' => e(H::getFormatedDate($row->request_date)),
                    'timestamp' => strtotime($row->request_date)
                ];
            } else {
                return [
                    'display' => '',
                    'timestamp' => ''
                ];
            }
        })->addColumn('select', function ($row) use($request){
            $checkbox = '';
            if(LD::checkFeatureFlag('status-change-request-request-oc-checkbox')) {
                $checkbox = '<input type="checkbox" name="checkbox-'.$request->input("type").'[]" class="form-input td select-all-' . str_replace(' ', '_', $request->input("type")) . '" value="' . $row->id . '" />';
            } 
            return $checkbox;
        })->addIndexColumn()->escapeColumns('Action')->make(true);
     }

     public function assignRequestOC(Request $request){

        $reqData = $request->all();
       
        $noteData = [];
        if (!empty($reqData['requestoc']) && !empty($reqData['ids'])) {
            $selectedIds = explode(",",$reqData['ids']);
            MssqlProviders::whereIn('id', $selectedIds)->update(['request_oc' => $reqData['requestoc']]);
            

            return response()->json(['success' => 1, 'message' => 'Infomation saved successfully']);
        } else {
            return response()->json(['success' => 0, 'message' => 'Something went wrong please try after some time.']);
        }

     }

     /**
    * This is will show the next step form for status change request.
    */
    public function showStatusChangeRequestNextStepForm(Request $request)
    {
        try {
            if ($request->input('id') != '') {
                $clinicianDetails = MssqlProviders::select('mssql_providers.*','mssql_employmentstatus.EmploymentStatus',DB::raw("emps.EmploymentStatus as CurrentStatus"))

                ->leftjoin('mssql_employmentstatus',"mssql_employmentstatus.EmploymentStatusID","=","mssql_providers.requested_status")
                ->leftJoin("mssql_employmentstatus as emps",function($query){
                    $query->on("emps.EmploymentStatusID","=","mssql_providers.EmploymentStatusID");
                })
                ->where('mssql_providers.id',$request->input('id'))->first();
                return view('statuschangerequests.statuschangerequestsoutcome', ['clinicianDetails'=>$clinicianDetails]);
            }
        } catch (\Exception $e) {
            return false;
        }
    }
    public function saveOutcomeForm(Request $request) {
        $req = $request->all();
        $data = [];
        if(!empty($req['outcome'])) {
            $user = H::getLoggedInUser();
            $userId = $user['id'];
            $data['request_outcome'] = $req['outcome'];
            $data['request_outcome_reason'] = $data['request_pending_document'] =
            $data['request_outcome_comment']='';

            if($req['outcome_key']=='pending_advocacy' ) {
                $data['request_outcome_reason'] = $req['pending_advocacy_reason']??'';
                $data['request_outcome_comment'] = $req['comment']??'';
            } elseif($req['outcome_key']=='rejected' ) {
                $data['request_outcome_reason'] = $req['rejected_reason']??'';
                $data['request_outcome_comment'] = $req['comment']??'';
            } elseif($req['outcome_key']=='pending_documents' ) {
                if(!is_array($req['pending_documents_reason'])) {
                    $data['request_pending_document'] = $req['pending_documents_reason'];
                }else{
                    $data['request_pending_document'] = implode(",",$req['pending_documents_reason']);
                }
            }
            $data['request_last_followup'] = date('Y-m-d H:i:s');
            DB::table("mssql_providers")->where("id",$req['id'])->update($data);
            $providerData = DB::table("mssql_providers")->where("id",$req['id'])->first();

            //Notification
            if(LD::checkFeatureFlag('create-notification-for-statuschange-request-update')){
                if($req['outcome'] == 'Completed' || $req['outcome'] == 'Cleared by Advocacy'
                || $req['outcome'] == 'Rejected'){
                    $notificationData['user_id'] = $providerData->request_by;
                    $notificationData['title'] = "".$providerData->fullName." Status Change Request has been
                    ".$req['outcome']."";
                    $notificationData['type'] = 'statusChangeRequest';
                    $notificationData['related_item'] = $req['id'];
                    $notificationData['notes'] = '';
                    Notifications::addNotification($notificationData);
                }
            }



            //Notes
            $text= "";
            $text.= $user['name'].' changed outcome to '.$req['outcome'].' on a Status Change Request'."<br>";
            if($data['request_outcome_reason'] != '' && $data['request_outcome_reason'] != 'Other') {
                $text.=$data['request_outcome_reason'] ."<br>";
            }
            if($data['request_pending_document'] != '') {
                $text.=$data['request_pending_document'] ."<br>";
            }
            if($data['request_outcome_comment'] != '') {
                $text.=$data['request_outcome_comment'] ."<br>";
            }
            $misc1 = '';
            if($req['outcome'] == 'Completed') {
                $misc1 = 'Completed by '.$user['name'].' on '.date("m/d/Y H:i:s");
            }
            $noteData['entity'] = 'Clinician';
            $noteData['entity_id'] = $req['id'];
            $noteData['notes'] =$text;
            $noteData['misc1'] =$misc1;
            $noteData['type'] = 'status_change_request';
            H::getHelper("ActivityNotes")->setActivityNotes($noteData);
            return response()->json(["status" => true]);

        }
        return response()->json(["status" => false]);

    }
    //  Show all page for Status change requests
     public function showAll(Request $request){
        try {
            $ocNewStatus = H::getHelper('StatusChangeRequests')->getNewClinicianStatus();
            $columns = H::getHelper('StatusChangeRequests')->getDataTableHtml('showall');
            if ($request->ajax()) {
                $data = H::getHelper("StatusChangeRequests")->getStatusChangeRequests("showall",'',$request->input("filters") ,false,$request);

                return response()->json([
                    'draw'            => intval($request->input('draw')),
                    'recordsTotal'    => intval($data['totalData']),
                    'recordsFiltered' => intval($data['totalFiltered']),
                    'data'            => $data['data'],
                ]);
            }
            return view('statuschangerequests.grid',['columns' => $columns,'ocNewStatus'=>$ocNewStatus]);
        } catch (\Exception $e) {
            H::ExcelLog('StatusChangeRequestController::showAll:'.$e->getMessage(),'getStatusChangeRequests.log');
        }
    }

    public function checkRequestStatus(Request $request){
        $id = $request->id;
        $status = MssqlProviders::where('id', $id)->value('request_outcome');
        $statusValue = 0;
        if($status == 'Completed' || $status == 'Rejected'){
            $statusValue = 1;
        }
        return response()->json(["statusValue" => $statusValue]);
    }
}
