<?php

namespace App\Helpers;

use App\RecruitingSchedule as RecruitingSchedule;
use App\User;
use App\MssqlProviders;
use App\Renewables as RenewalbesTable;
use Carbon\Carbon;
use DB;

class Advocacy
{

    public function getAdvocacyData($viewType="dashboard",$bucketType = '',$searchfilter=[],$showCount=0,$request='') {
        $loggedInUser = H::getLoggedInUser();
        $showAllData = [];
        $search = '';$limit = '';$start = '';$order ='';$dir = '';

        $bucket = MssqlProviders::select(
            'mssql_providers.id',
            'mssql_providers.fullName',
            'mssql_providers.ClinicianID',
            'branch_locations.branch',
            "mssql_employmentstatus.EmploymentStatus as CurrentStatus",
            "rqStatus.EmploymentStatus as NewStatus",
            'mssql_providers.request_reason',
            'mssql_providers.request_last_followup',
            "users.name  as requested_by",
            'mssql_providers.request_outcome',
            DB::raw('CASE WHEN (ifnull(request_pending_document,"")) != "" THEN request_pending_document ELSE request_outcome_reason END as last_activity'),
            DB::raw("DATE_FORMAT(mssql_providers.request_date, '%m/%d/%Y') as request_last_followup_date"),
            DB::raw("(SELECT users.name from users left join users_has_roles on users.id = users_has_roles.user_id left join roles on users_has_roles.role_id = roles.id where roles.name='Orientation Coordinator' and users.status='1' and find_in_set(mssql_facilities.branchid, users.branchid) limit 1) as oc"),
            DB::raw("(SELECT users.name from users where users.id = mssql_providers.request_advocacy_specialist limit 1) as assigned_to")
        )
        ->leftJoin("mssql_facilities","mssql_facilities.FacilityID","=","mssql_providers.FacilityID")
        ->leftJoin("branch_locations","branch_locations.id","=","mssql_facilities.branchid")
        ->leftJoin('mssql_employmentstatus', 'mssql_providers.scr_original_status_id', '=', 'mssql_employmentstatus.EmploymentStatusID')
        ->leftJoin('mssql_employmentstatus as rqStatus', 'mssql_providers.requested_status', '=', 'rqStatus.EmploymentStatusID')
        ->leftJoin('users', 'mssql_providers.request_by', '=', 'users.id')
        ->leftJoin("regions", function($query){
            $query->on("regions.id","=","mssql_facilities.region");
        });

        switch($viewType) {
            case 'dashboard':
                switch($bucketType) {
                    case 'advocacy_statuschangerequest':
                        
                        $bucket->where('mssql_providers.is_deleted',"!=",1);
                        $allowedStatus = $this->allowedTaskStatus();
                        $requestedStatus = "Requested";
                        $bucket->where(function($q) use($allowedStatus,$requestedStatus) {
                            $q->whereIn('mssql_providers.request_outcome',$allowedStatus);
                            $q->orWhere(function($qq) use($requestedStatus) {
                                $qq->where("mssql_providers.request_outcome",$requestedStatus);
                                $qq->where("mssql_employmentstatus.EmploymentStatus","=","Active Advocacy");
                            });
                        });
                        break;
                    default:
                        break;
                }
                break;
            default:
                break;
        }

       

        // Filter Section
            $isMeMode = 0;
            $state = $region = $branch = $ad = $tasksStatus = $clinicianStatus =[];
            if(!empty($searchfilter)) {
                foreach($searchfilter as $filter) {
                    $filterName = $filter['name'];
                    $filterValue = $filter['value'];

                    if($filterName=='addefault') {
                        $isMeMode =1;
                    }
                    if($filterName=='adstate') {
                        $state[] =$filterValue;
                    }
                    if($filterName=='adregion') {
                        $region[] =$filterValue;
                    }
                    if($filterName=='adbranch') {
                        $branch[] =$filterValue;
                    }
                    if($filterName=='adspecialist') {
                        $ad[] =$filterValue;
                    }
                    if($filterName=='adstatus') {
                        $clinicianStatus[] =$filterValue;
                    }

                    if($filterName=='adtaskstatus') {
                        $tasksStatus[] =$filterValue;
                    }
                }
            }
            
            if($isMeMode) {
                $ad[] = $loggedInUser['id'];
            }

            if(count($clinicianStatus)>0){
                $bucket->where(function($qq) use ($clinicianStatus){
                    foreach($clinicianStatus as $c) {
                        $qq->orWhere("mssql_providers.requested_status","=",$c);
                    }
                });
            }
            if(count($ad)) {
                $bucket->whereIn("mssql_providers.request_advocacy_specialist",$ad);
            }
            if(count($state)>0) {
                $bucket->where(function($qq) use ($state){
                    foreach($state as $s) {
                        $qq->orWhere("branch_locations.state","=",$s);
                    }
                });
            }
            if(count($branch)>0) {
                $bucket->where(function($qq) use ($branch){
                    foreach($branch as $b) {
                        $qq->orWhere("mssql_facilities.branchid","=",$b);
                    }
                });
            }

            if(count($region)>0) {
                $bucket->where(function($qq) use ($region){
                    foreach($region as $b) {
                        $qq->orWhere("regions.id","=",$b);
                    }
                });
            }

            if(count($tasksStatus)>0) {
                $bucket->where(function($qq) use ($tasksStatus){
                    foreach($tasksStatus as $b) {
                        $qq->orWhere("mssql_providers.request_outcome","=",$b);
                    }
                });
            }




        if($showCount) {
            return '('.$bucket->count().')';
        }

        if($viewType == 'showall') {
            $showAllData['totalFiltered'] = $bucket->count();
            $showAllData['data'] = $bucket->offset($start)->limit($limit)->orderBy($order, $dir)->get();
            return $showAllData;
        }else if($viewType == 'export') {
            return json_decode(json_encode($bucket->get()->toArray()), true);
        }else{
            return $bucket->get();
        }
    }

    public function getDataTableHtml($type)
    {

        $dataTable = [];
        switch($type) {

            case 'advocacy_statuschangerequest':
                $dataTable = [
                    'select'=>'select',
                    'fullName'=>'Clinician Name',
                    'ClinicianID'=>"Clinician ID",
                    'branch'=>"Clinician Branch",
                    'CurrentStatus'=>'Current Status',
                    'NewStatus'=>'New Status',
                    'request_reason'=>'Reason',
                    'requested_by'=>'Requested By',
                    'request_outcome'=>'Task Status',
                    'last_activity'=>'Last Activity',
                    'request_last_followup_date'=>'Date',
                    'oc'=>'Coordinator',
                    'assigned_to' => 'Assigned To'
                ];
                break;
            default:
                break;
        }
        
       return view('advocacy.datatable',["type"=>$type,'dataTable'=>$dataTable]);
    }

    public function allowedTaskStatus(){
        return ["Pending Advocacy","Pending Documents"];
    }

    public function getAdvocacyTaskStatus(){
        $data = $this->allowedTaskStatus();
        $status = [];
        foreach($data as $d) {
            $status[] = ["value"=>$d,"text"=>$d];
        }
        return $status;
      
    }



}
