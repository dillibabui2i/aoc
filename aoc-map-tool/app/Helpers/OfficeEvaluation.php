<?php
namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\MssqlRecruitertracker;
use App\RecruitingSchedule;
use App\Helpers\LD;
use App\OfficeEvaluation as OfficeEV;

class OfficeEvaluation
{
   

    public function getDataTableHtml($type)
    {
        $dataTable = [];
        switch($type) {
           
            case 'office_evaluation':
                if(LD::checkFeatureFlag('add-new-field-furthest-step-in-office-evaluations')){
                    $dataTable = [
                        'EmployeeName'=>'Employee Name',
                        'BranchName'=>"Branch",
                        'supervisor'=>'Supervisor Name',
                        'manager'=>'Manager',
                        'status2'=>'Furthest Step',
                        'status'=>'Current Status',
                        'fupdated_at'=>'Last Updated',
                    ];
                }else{
                    $dataTable = [
                        'EmployeeName'=>'Employee Name',
                        'BranchName'=>"Branch",
                        'supervisor'=>'Supervisor Name',
                        'manager'=>'Manager',
                        'status'=>'Status',
                        'fupdated_at'=>'Last Updated',
                    ];
                }
                break;
            default:
                break;
            
        }
        
        return view('officeEvaluation.datatable',["type"=>$type,'dataTable'=>$dataTable]);
    }

    
    public function getUnSentDocuments($type,$request,$count=0) {
        $request = $request->all();
        $bucket = OfficeEV::select(
            'office_evaluations.id',
            'office_evaluations.aoc_provider_id',
            DB::Raw("CONCAT(mssql_providers.FirstName,' ',mssql_providers.LastName) as EmployeeName"),
            'mssql_facilities.BranchName',
            DB::Raw("DATE_FORMAT(office_evaluations.updated_at,'%m/%d/%Y') as fupdated_at"),
            DB::Raw("(SELECT name from users where users.id = u.recruiting_manager) as supervisor"),
            DB::Raw("(SELECT name from users where users.id = u.manager_id) as manager"),
            DB::Raw("u.manager_id as manager_id"),
            DB::Raw("u.recruiting_manager as supervisor_id"),
            'office_evaluations.status','office_evaluations.status as status2',
            DB::Raw("IFNULL(self_evaluations.id,'') as selfid")
        )
        ->leftJoin('mssql_providers', 'aoc_provider_id', '=', 'mssql_providers.id')
        ->leftJoin('users as u' , 'mssql_providers.AOC_Email', '=', 'u.email')
        ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')
		->leftJoin('self_evaluations', function($join)
		{
			$join->on('self_evaluations.aoc_provider_id', '=', 'office_evaluations.aoc_provider_id')
			->whereRaw("year(self_evaluations.created_at) = year(office_evaluations.created_at)")
			->whereRaw("(self_evaluations.created_at) <> (self_evaluations.updated_at)");
		});
           $state = [];
           $branch = [];
           $region = [];
           $supervisor = [];
           $manager = [];
           $execManager = [];
           $status = [];

        if(isset($request['filters'])) {
               
            foreach($request['filters'] as $key => $value){
                
                if($value['name']=='officestate') {
                    $state[] = $value['value'];
                }
                if($value['name']=='officebranch') {
                    $branch[] = $value['value'];
                }
                if($value['name']=='officeregion') {
                    $region[] = $value['value'];
                }
                if($value['name']=='officesupervisor'){
                    $supervisor[] = $value['value'];
                }

                if($value['name']=='officemanager'){
                    $manager[] = $value['value'];
                }
                if($value['name']=='officeexecmanager') {
                    $execManager[] = $value['value'];
                }

                if($value['name']=='officestatus'){
                    $status[] = $value['value'];
                }
               


            }
        }


        if(count($state)>0) {
            $bucket->whereIn('mssql_facilities.State', $state);
        }
        if(count($branch)>0) {
            $bucket->whereIn('mssql_facilities.id', $branch);
        }

        if(count($region)>0) {
            $bucket->whereIn('mssql_facilities.region', $region);
        }

        if(count($status)>0) {
            $bucket->whereIn('office_evaluations.status', $status);
        }

        if(count($supervisor)>0) {
            $bucket->whereIn('u.recruiting_manager',$supervisor);
        }

        if(count($manager)>0) {
            $bucket->whereIn('u.manager_id',$manager);
        }

        

        $user = H::getLoggedInUser();
        $userId = $user['id'];
        //don't show Jessica's evaluations unless it's Jessica
        if($userId!=156){
            $bucket->where('u.recruiting_manager','<>','156');
        }
        
        if($this->isEvaluationHR($userId)){
            // no filter
        } else {
            $userArray[] = $userId;
            $bucket->where(function($q) use($userArray){
                    $q->whereRaw("(SELECT id from users where users.id IN (SELECT recruiting_manager from users where mssql_providers.AOC_Email = users.email)) IN ('" . implode("','", $userArray) . "')");
                    
                    if($this->isManagerIgnored($userArray[0])) {

                        $q->orWhereRaw("(SELECT id from users where users.id IN (SELECT manager_id from users where mssql_providers.AOC_Email = users.email)) IN ('" . implode("','", $userArray) . "')");
                   
                    }
                    
 
            });
        }
       
		$bucket->whereRaw('year(office_evaluations.created_at) =
		(select year(created_at) from office_evaluations order by created_at desc limit 1)');

//        $bucket->whereRaw('year(self_evaluations.created_at) = year(office_evaluations.created_at)');

        if($count==1) {
            return '('.$bucket->count().')';
        } else {
            return $bucket->get();
        }
    }

    public function isEvaluationSupervisor($userId) {

       $supervisors = H::getHelper("ConstantHelper")->getOfficeEvaluationSupervisor();
       
        foreach($supervisors as $supervisor) {
            if($userId==$supervisor['value']){
                return true;
            }
        }
        return false;

    }

    public function isEvaluationManager($userId){
        $managers = H::getHelper("ConstantHelper")->getOfficeEvaluationManagers();
      
        foreach($managers as $manager) {
            if($userId==$manager['value']){
                return true;
            }
        }
        return false;
    }

    public function isEvaluationExecManager($userId){
        $execmanagers = H::getHelper("ConstantHelper")->getOfficeEvaluationExecManagers();
      
        foreach($execmanagers as $execmanager) {
            if($userId==$execmanager['value']){
                return true;
            }
        }
        return false;
    }

    public function isEvaluationHR($userId) {
       return H::getHelper("UserPermissionResolver")->isRoleHasPermissionNew("evaluationhr");
    }


    public function isExecManagerRole($userId) {
       $roles =  DB::table("users")->select("roles.name")->leftJoin("users_has_roles","users.id","=","users_has_roles.user_id")->leftJoin("roles","roles.id","=","users_has_roles.role_id")->where("users.id",$userId)->get();
       foreach($roles as $role) {
           if($role->name=='Executive Team') {
                return true;
           }
       }



        return false;
     }


     public function isManagerIgnored($managerId) {
        $data = [156];
        // 156 is jessica's id

        if(in_array($managerId,$data)) {
            return false;
        } else {
            return true;
        }
     }
}
