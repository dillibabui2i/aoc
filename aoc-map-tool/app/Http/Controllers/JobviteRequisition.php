<?php

namespace App\Http\Controllers;


use App\Helpers\H;
use App\Helpers\LD;
use DataTables;
use Illuminate\Http\Request;
use DB;


class JobviteRequisition extends Controller
{

   
    public function getJobivteRequisitionData(Request $request) {
       
        
        $data = H::getHelper("JobviteRequisition")->getRequisitionData("dashboard",$request->input("type"), $request->input("filters"));
        return Datatables::of($data) ->addIndexColumn()
       ->escapeColumns('Action')->make(true);
    }

    public function reloadJobviteRequisitionData(Request $request) {
        return json_encode(["counter"=>H::getHelper("JobviteRequisition")->getRequisitionData("dashboard",$request->input("type"), $request->input("filters"),1),"type"=>$request->input("type")]);
    }

    public function completeCloseRequisition(Request $request) {
        $req = $request->all();
        $loggedInUser = H::getLoggedInUser();
        if(!empty($req)) {
            $data['no_active_nth'] = 0;
            $data['no_active_nth_resolved_by'] = $loggedInUser['id'];
            $data['no_active_nth_resolved_on'] = date("Y-m-d H:i:s");
            DB::table("jobs")->where("id",$req['id'])->update($data);
            return response()->json(["status" => true]);
        }
        return response()->json(["status" => false]);
    }

    public function completeOpenRequisition(Request $request) {
        $req = $request->all();
        $serviceArray = [];
        $serviceArray["PDN"] = ['PDN/Respite', 'Respite', 'Skilled Nursing', 'PDN'];
        $serviceArray["CNA"] = ['CNA', 'IHSS'];
        
        $stateService =  DB::table("jobvite_category")->where("category",$req['jobviteCategory'])->first();
     
        $checkList = DB::table("NeedToHire_Checklist")->select("NeedToHire_Checklist.id")
        ->leftJoin('NeedToHire_Accounts','NeedToHire_Accounts.id', '=', 'NeedToHire_Checklist.aoc_nthaccounts_id')
        ->leftJoin('mssql_patients','mssql_patients.id', '=', 'NeedToHire_Accounts.aoc_patient_id')
        ->join('NeedToHire_Hub', function ($join) {
            $join->on('NeedToHire_Hub.City', '=', 'mssql_patients.Patient_City')
                    ->on('NeedToHire_Hub.ShortState', '=', 'mssql_patients.Patient_State');
        })
        ->join('jobvite_category', function ($join) {
            $join->on('jobvite_category.state', '=', 'NeedToHire_Hub.State')
                    ->on('jobvite_category.service', '=', DB::raw("(case when NeedToHire_Accounts.ServicesNeeded in ('PDN/Respite', 'Respite', 'Skilled Nursing', 'PDN') then 'PDN'
                     when NeedToHire_Accounts.ServicesNeeded in ('CNA', 'IHSS') then 'CNA'
                     else '' end)"));
        })
        ->where("NeedToHire_Checklist.HubCity",0)
        ->where("NeedToHire_Hub.Hub",$req['hub'])
        ->whereIn("NeedToHire_Accounts.ServicesNeeded",$serviceArray[$stateService->service])->get()->toArray();
       
        if($checkList){
           $data['HubCity'] = 1;
           $checkListArray = array_column(json_decode(json_encode($checkList), true),"id");
           DB::table("NeedToHire_Checklist")->whereIn("id",$checkListArray)->update($data);
           return response()->json(["status" => true]);
        }
        return response()->json(["status" => false]);
       
    
    }


}
