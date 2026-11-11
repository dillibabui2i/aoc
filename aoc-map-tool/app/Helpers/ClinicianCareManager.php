<?php
namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\MssqlRecruitertracker;
use App\RecruitingSchedule;
use App\Helpers\LD;
use App\Helpers\H;
use App\FieldEvaluation;

class ClinicianCareManager
{
   

    public function getDataTableHtml($type)
    {
        $dataTable = [];
        switch($type) {
           
            case 'unsent_documents':
                $dataTable = [
                    'BranchName'=>'Branch',
                    'ClinicianID'=>"Clinician",
                    'clinicianName'=>"Clinician Name",
                    'fcreated_at'=>'Date Created',
                    'name'=>'Manager',
                    'status'=>'Status',
                ];
                break;
            default:
                break;
        }
        
        return view('fieldEvaluation.datatable',["type"=>$type,'dataTable'=>$dataTable]);
    }

    
    public function getUnSentDocuments($type,$request,$count=0) {
        H::ExcelLog('in getUnSentDocuments','ts_evaluation.log');
        $request = $request->all();

        $q = FieldEvaluation::select('field_evaluations.id', 'field_evaluations.aoc_provider_id', 'mssql_facilities.BranchName', 'mssql_providers.ClinicianID', DB::raw("DATE_FORMAT(field_evaluations.created_at,'%m/%d/%Y') as fcreated_at"), 'users.name', DB::raw("CONCAT(mssql_providers.FirstName,' ', mssql_providers.LastName) as clinicianName"),'field_evaluations.status')
        ->leftJoin('mssql_providers', 'aoc_provider_id', '=', 'mssql_providers.id')
        ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')
        ->leftJoin('users', 'users.id', '=', 'field_evaluations.manager');

        $q->whereNotIn("aoc_provider_id",function($query){
            $query->select("entity_id")->from("aoc_documents")->whereIn("document_type",["field_eval","therapy_eval"]);
        });
        $q->where('mssql_providers.is_deleted',0);
        if (LD::checkFeatureFlag('add-delete-reason-field-evaluation')) {
            $q->whereNested(function ($q) {
                $q->where('field_evaluations.is_deleted', 0)
                    ->orWhereNull('field_evaluations.is_deleted');
            });
        }
        $user =H::getLoggedInUser();
        $q->where("manager",$user['id']);
        if($count==1) {
            return '('.$q->count().')';
        } else {
            return $q->get();
        }
    }
}
