<?php

namespace App\Http\Controllers;


use App\Helpers\H;
use App\Helpers\LD;
use DataTables;
use Illuminate\Http\Request;
use App\Renewables as RenewalbesTable;
use DB;
use Illuminate\Support\Facades\Auth;

class PaycomKantimeController extends Controller
{   
    public function getPaycomKantimeExceptionData(Request $request) {
       
        
        $data = H::getHelper("PaycomKantimeException")->getPaycomKantimeExceptionData("dashboard",$request->input("type"), $request->input("filters"));
        return Datatables::of($data)->addIndexColumn()
        ->editColumn('Paycom', function ($row) {
            if($row->Message== 'ID Discrepancy'){
                return '<strong>Employee GL Code: </strong>'.$row->Paycom;
            } else if($row->Message== 'Employment Type Discrepancy'){
                return '<strong>DOL Status: </strong>'.$row->Paycom;
            } else if($row->Message== 'Status Discrepancy'){
                return '<strong>Employee Status: </strong>'.$row->Paycom;
            } else if($row->Message== 'Location Discrepancy'){
                return '<strong>Work Location: </strong>'.$row->Paycom;
            } else if($row->Message== 'Official/Work Email Discrepancy'){
                return '<strong>Email: </strong>'.$row->Paycom;
            }  else if($row->Message== 'Name Discrepancy'){
                return '<strong>Name: </strong>'.$row->Paycom;
            } else {
                return $row->Paycom;
            }
        
        }) 
        ->editColumn('KanTime', function ($row) {
            if($row->Message== 'ID Discrepancy'){
                return '<strong>Clinician ID: </strong>'.$row->KanTime;
            } else if($row->Message== 'Employment Type Discrepancy'){
                return '<strong>Employment Type: </strong>'.$row->KanTime;
            } else if($row->Message== 'Status Discrepancy'){
                return '<strong>Status: </strong>'.$row->KanTime;
            } else if($row->Message== 'Location Discrepancy'){
                return '<strong>Payroll Location: </strong>'.$row->KanTime;
            } else if($row->Message== 'Official/Work Email Discrepancy'){
                return '<strong>Email: </strong>'.$row->KanTime;
            }  else if($row->Message== 'Name Discrepancy'){
                return '<strong>Name: </strong>'.$row->KanTime;
            } else {
                return $row->KanTime;
            }
        
        }) 
        ->escapeColumns('KanTime') ->escapeColumns('Paycom') ->escapeColumns('Action')->make(true);
    }

    public function reloadPaycomKantimeExceptionData(Request $request) {
        return json_encode(["counter"=>H::getHelper("PaycomKantimeException")->getPaycomKantimeExceptionData("dashboard",$request->input("type"), $request->input("filters"),1),"type"=>$request->input("type")]);
    }

    public function completedPaycomeDiscrepancy(Request $request) {
        $req = $request->all();
        try
        {
            switch ($req['Message']) {
                case "ID Discrepancy":
                    $data = [
                        'id_fixed' => 1,
                        'id_fixed_by' => Auth::user()->id,
                        'id_fixed_at' => date('Y-m-d H:i:s')
                    ];
                    DB::table("paycom")->where('id', $req['paycomId'])->update($data);
                    break;
                case "Employment Type Discrepancy":
                    break;
                case "Status Discrepancy":
                    $EmploymentStatusID = DB::table("paycom")
                    ->leftJoin('mssql_employmentstatus','mssql_employmentstatus.EmploymentStatus', '=', 'paycom.PaycomEmploymentStatus')
                    ->where("paycom.id", $req['paycomId'])->pluck('mssql_employmentstatus.EmploymentStatusID');
                    $data = [
                        'EmploymentStatusID' => $EmploymentStatusID[0]
                    ];
                    DB::table("mssql_providers")->where('id', $req['providerId'])->update($data);
                    break;
                case "Location Discrepancy":
                    $FacilityID = DB::table("paycom")
                    ->leftJoin('mssql_facilities','mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')
                    ->where("paycom.id", $req['paycomId'])->pluck('mssql_facilities.FacilityID');
                    $data = [
                        'FacilityID' => $FacilityID[0]
                    ];
                    DB::table("mssql_providers")->where('id', $req['providerId'])->update($data);
                    break;
                case "Official/Work Email Discrepancy":
                    $WorkEmail = DB::table("paycom")->where("id", $req['paycomId'])->pluck('WorkEmail');
                    $data = [
                        'AOC_Email' => $WorkEmail[0]
                    ];
                    DB::table("mssql_providers")->where('id', $req['providerId'])->update($data);
                    break;
                case "Name Discrepancy":
                    $Name = DB::table("paycom")->select("paycom.Firstname", "paycom.Lastname")->where("id", $req['paycomId'])->first();
                    $data = [
                        'LastName' => $Name->Lastname,
                        'FirstName' => $Name->Firstname,
                    ];
                    DB::table("mssql_providers")->where('id', $req['providerId'])->update($data);
                    break;
                default:
                    break;
            }

            $noteData = [];
            $noteData['entity'] = 'clinician';
            $noteData['entity_id'] = $req['providerId'];
            $noteData['notes'] = $req['Message'] . ' was corrected by ' . Auth::user()->name;

            H::getHelper("ActivityNotes")->setActivityNotes($noteData);
            return response()->json(["status" => true]);
        } catch (\Exception $e) {
            return response()->json(["status" => false]);    
        }
    }
}
