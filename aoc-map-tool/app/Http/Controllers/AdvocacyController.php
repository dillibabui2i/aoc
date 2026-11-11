<?php

namespace App\Http\Controllers;


use App\Helpers\H;
use App\Helpers\LD;
use DataTables;
use Illuminate\Http\Request;
use App\MssqlProviders;
use DB;

class AdvocacyController extends Controller
{

   

    public function getAdvocacyStatusChangeRequest(Request $request) {
       
        
        $data = H::getHelper("Advocacy")->getAdvocacyData("dashboard",$request->input("type"), $request->input("filters"));
        return Datatables::of($data)
         ->editColumn('request_last_followup_date', function($data) {
                if($data->request_last_followup_date!='00/00/0000') {
                    return [
                        'display' => $data->request_last_followup_date,
                        'timestamp' => strtotime($data->request_last_followup_date),
                    ];
                } else {
                    return [
                        'display' => '',
                        'timestamp' => ''
                    ];
                }
            })
            ->editColumn('last_activity', function($data) {
                return H::getFormatedDateTime($data->request_last_followup).'-'.$data->last_activity;
            }) 
        ->addColumn('select', function ($row) use($request){
            $checkbox = '<input type="checkbox" name="checkbox-'.$request->input("type").'[]" class="form-input td select-all-' . str_replace(' ', '_', $request->input("type")) . '" value="' . $row->id . '" />';
            return $checkbox;
        })->addIndexColumn()
       ->escapeColumns('Action')->make(true);
    }

    public function reloadAdvocacyStatusChangeRequest(Request $request) {
        return json_encode(["counter"=>H::getHelper("Advocacy")->getAdvocacyData("dashboard",$request->input("type"), $request->input("filters"),1),"type"=>$request->input("type")]);
    }

    public function setAdvocacySpecialist(Request $request) {

        $reqData = $request->all();
        $noteData = [];
        if (!empty($reqData['advocacyspecialist']) && !empty($reqData['ids'])) {
            $selectedIds = explode(",",$reqData['ids']);
            MssqlProviders::whereIn('id', $selectedIds)->update(['request_advocacy_specialist' => $reqData['advocacyspecialist']]);
            

            return response()->json(['success' => 1, 'message' => 'Infomation saved successfully']);
        } else {
            return response()->json(['success' => 0, 'message' => 'Something went wrong please try after some time.']);
        }

    }

    public function showStatusChangeRequestForm(Request $request) {
        try {
            if ($request->input('id') != '') {
                $clinicianDetails = MssqlProviders::select('mssql_providers.*','mssql_employmentstatus.EmploymentStatus')
                ->leftjoin('mssql_employmentstatus',"mssql_employmentstatus.EmploymentStatusID","=","mssql_providers.requested_status")
                ->where('mssql_providers.id',$request->input('id'))->first();
                return view('advocacy.statuschangerequestsoutcome', ['clinicianDetails'=>$clinicianDetails]);
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
           
            if($req['outcome_key']=='requested' ) {
                $data['request_outcome_reason'] = $req['requested_reason']??'';
            } else if($req['outcome_key']=='rejected' ) {
                $data['request_outcome_reason'] = $req['rejected_reason']??'';
                $data['request_outcome_comment'] = $req['comment']??'';
            } else if($req['outcome_key']=='pending_documents' ) {
                if(!is_array($req['pending_documents_reason'])) {
                    $data['request_pending_document'] = $req['pending_documents_reason'];
                } else {
                    $data['request_pending_document'] = implode(",",$req['pending_documents_reason']);
                }
            }
           

           
            $data['request_last_followup'] = date('Y-m-d H:i:s');
            DB::table("mssql_providers")->where("id",$req['id'])->update($data);

            //Notes
            $text= "Status Change Request\n";
            $text.=date("m/d/Y")."\n";
            
            if(isset($data['request_pending_document']) && !empty($data['request_pending_document'])) {
                $text.=$data['request_pending_document'] ."\n";
            } else {
                if(isset($data['request_outcome_reason']) && !empty($data['request_outcome_reason'])) {
                    $text.=$data['request_outcome_reason'] ."\n";
                }
            }
            $text.="Outcome:".$req['outcome'];

            $noteData['entity'] = 'Clinician';
            $noteData['entity_id'] = $req['id'];
            $noteData['type'] = 'status_change_request';
            $noteData['notes'] =$text;
            H::getHelper("ActivityNotes")->setActivityNotes($noteData);




            return response()->json(["status" => true]);

        }
        return response()->json(["status" => false]);



    }
    


}
