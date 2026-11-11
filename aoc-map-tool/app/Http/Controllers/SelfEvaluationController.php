<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\SelfEvaluation;
use App\Helpers\H;

class SelfEvaluationController extends Controller
{
    public function edit(Request $request)
    {
        $user = H::getLoggedInUser();
        $mode = 'add';
        
        $clinicianData = DB::table("mssql_providers")
            ->select("mssql_providers.*",
            DB::Raw("(SELECT name from users where users.id = u.recruiting_manager) as supervisor_name"),
            DB::Raw("(SELECT name from users where users.id = u.manager_id) as manager"),
            DB::Raw("u.title  as user_title")
          
       )    
            ->leftJoin('users as u', 'mssql_providers.AOC_Email', 'u.email')
            ->where('mssql_providers.AOC_Email', $user['email'])->where('mssql_providers.is_deleted',0)->first();
        
        if (empty($request->input('id'))) {
            $selfEvaluationExistingData = SelfEvaluation::where('aoc_provider_id', $clinicianData->id)
			->whereRaw('year(self_evaluations.created_at) = ' . date('Y'))
			->first();
            if ($selfEvaluationExistingData) {
                $lastInsertedId = $selfEvaluationExistingData->id;
                if($selfEvaluationExistingData->status=='Submitted'){
                    $mode = 'view';
                }
            } else {
                $data = [];
                $data['aoc_provider_id'] = $clinicianData->id;
                $data['manager'] = $clinicianData->manager;
                $data['status'] = 'Draft';
                SelfEvaluation::insert($data);
                $lastInsertedId = DB::getPdo()->lastInsertId();
            }
            $selfEvaluationData = SelfEvaluation::find($lastInsertedId);

        } else {
            $selfEvaluationData = SelfEvaluation::find($request->input('id'));

            $clinicianData = DB::table("mssql_providers")
            ->select("mssql_providers.*", 
            DB::Raw("(SELECT name from users where users.id = u.recruiting_manager) as supervisor_name"),
            DB::Raw("(SELECT name from users where users.id = u.manager_id) as manager"),
            DB::Raw("u.title  as user_title")
            )      
            ->leftJoin('users as u', 'mssql_providers.AOC_Email', 'u.email')
            ->where('mssql_providers.id', $selfEvaluationData->aoc_provider_id)->where('mssql_providers.is_deleted',0)->first();

            $mode = 'view';
        }
        
        return view('selfEvaluation.form', ["mode" => $mode, 'selfEvaluationData' => $selfEvaluationData, 'clinicianData' => $clinicianData]);
    }
    
    public function save(Request $request)
    {
        try {
            $data = [];
            if ($request->input('id') != '') {
                $data = $request->input('evaluation');
                if (!empty($request->input('last_review_contributions'))) {
                    $data['last_review_contributions'] = $request->input('last_review_contributions');
                }
                if (!empty($request->input('performed_additional_duties'))) {
                    $data['performed_additional_duties'] = $request->input('performed_additional_duties');
                }
                if (!empty($request->input('areas_improve'))) {
                    $data['areas_improve'] = $request->input('areas_improve');
                }

                if ($request->input("action") == 'submit') {
                    $data['status'] = 'Submitted';
                } else if ($request->input("action") == 'save') {
                    $data['status'] = 'Save';
                }
                if(!empty($data)) {
                    SelfEvaluation::where('id', $request->input('id'))->update($data);
                }
                return response()->json(["status" => true]);
            } else {
                return response()->json(['status' => false]);
            }
        }catch(\Exception $e){
            H::ExcelLog("Error : " . $e->getMessage(),'save_evaluation.log');
            return response()->json(['status' => false]);
        }
    }    
}
