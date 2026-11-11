<?php
namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\LD;

class StatusChangeRequests
{
    public function getDataTableHtml($type)
    {
        $dataTable = [];
        switch($type) {
            case 'showall':
                return [
                    ['name' => 'id','label' => 'id', 'visible' => false, 'width' => '0'],
                    ['name' => 'clinicianName', 'label' => 'Clinician Name', 'width' => '10%'],
                    ['name' => 'ClinicianID', 'label' => 'Clinician ID', 'width' => '10%'],
                    ['name' => 'FacilityName', 'label' => 'Location', 'width' => '10%'],
                    ['name' => 'EmploymentStatus', 'label' => 'Status', 'width' => '10%'],
                    ['name' => 'NewStatus', 'label' => 'New Status', 'width' => '10%'],
                    ['name' => 'request_reason','label' => 'Reason', 'width' => '10%'],
                    ['name' => 'requestedBy','label' => 'Requested By', 'width' => '10%'],
                    ['name' => 'request_outcome','label' => 'Task Status', 'width' => '10%'],
                    ['name' => 'lastActivity','label' => 'Last Activity', 'width' => '10%'],
                    ['name' => 'request_date','label' => 'Date Requested', 'width' => '10%'],
                    ['name' => 'oc','label' => 'Coordinator', 'width' => '10%']
                ];
            case 'oc_status_change_requests':
            default:
            if(LD::checkFeatureFlag('status-change-request-request-oc-checkbox')) {
                $dataTable = ['select'=>'select','clinicianName'=>'Clinician Name','ClinicianID'=>"Clinician ID",'FacilityName'=>'Location','EmploymentStatus'=>'Current Status','NewStatus'=>'New Status','request_reason'=>'Reason','requestedBy'=>'Requested By','request_outcome'=>'Task Status','lastActivity' => 'Last Activity','request_date'=>'Date Requested','oc'=>'Coordinator'];
            } else {
                $dataTable = ['clinicianName'=>'Clinician Name','ClinicianID'=>"Clinician ID",'FacilityName'=>'Location','EmploymentStatus'=>'Current Status','NewStatus'=>'New Status','request_reason'=>'Reason','requestedBy'=>'Requested By','request_outcome'=>'Task Status','lastActivity' => 'Last Activity','request_date'=>'Date Requested','oc'=>'Coordinator'];
            }
           
                break;
        }
        return view('statuschangerequests.datatable',["type"=>$type,'dataTable'=>$dataTable]);
    }

    public function getStatusChangeRequests($viewType="dashboard",$bucketType = '',$searchfilter=[],$showCount=0,$request='') {
        $loggedInUser = H::getLoggedInUser();
        $search = '';$limit = '';$start = '';$order ='';$dir = '';
        $bucket = DB::table("mssql_providers");
        $bucket->leftJoin("users",function($query){
            $query->on("users.id","=","mssql_providers.request_by");
        });
        $bucket->leftJoin("mssql_employmentstatus",function($query){
            $query->on("mssql_employmentstatus.EmploymentStatusID","=","mssql_providers.scr_original_status_id");
        });
        $bucket->leftJoin("mssql_employmentstatus  as emps",function($query){
            $query->on("emps.EmploymentStatusID","=","mssql_providers.requested_status");
        });
        $bucket->leftJoin("mssql_facilities", function($query){
            $query->on('mssql_providers.FacilityID', '=', 'mssql_facilities.FacilityID');
        });
        $bucket->leftJoin("branch_locations", function($query){
            $query->on("branch_locations.id","=","mssql_facilities.branchid");
        });
        $bucket->leftJoin("regions", function($query){
            $query->on("regions.id","=","mssql_facilities.region");
        });
        if(LD::checkFeatureFlag('status-change-request-request-oc-checkbox')) {
            $bucket->select('mssql_providers.id',
            'mssql_providers.request_last_followup',
            DB::raw("CONCAT(mssql_providers.FirstName,' ',
            IF(mssql_providers.MiddleName != '',
            CONCAT(mssql_providers.MiddleName, ' '),''),
            mssql_providers.LastName) as clinicianName"),
            'mssql_providers.ClinicianID',
            'mssql_facilities.FacilityName',
            'mssql_employmentstatus.EmploymentStatus',
            'emps.EmploymentStatus as NewStatus',
            'mssql_providers.request_reason',
            DB::raw("users.name AS requestedBy"),"mssql_providers.request_outcome",
            DB::raw('CASE WHEN (mssql_providers.request_outcome = "Pending Advocacy"
            OR mssql_providers.request_outcome = "Rejected")
            THEN mssql_providers.request_outcome_reason
            WHEN mssql_providers.request_outcome = "Pending Documents"
            THEN mssql_providers.request_pending_document
            WHEN mssql_providers.request_outcome = "Completed"
            THEN (SELECT misc1 FROM notes WHERE
            mssql_providers.id = notes.entity_id
            AND notes.entity = \'Clinician\'
            AND notes.type = \'status_change_request\'
            ORDER BY notes.created_at DESC
            LIMIT 1
            )
            ELSE ""
            END as lastActivity'),
            DB::raw("DATE_FORMAT(mssql_providers.request_date, '%m/%d/%Y') as request_date"),
            DB::raw("(SELECT users.name from users where users.id = mssql_providers.request_oc
             limit 1) as oc"));
        } else {
            $bucket->select('mssql_providers.id',
            'mssql_providers.request_last_followup',
            DB::raw("CONCAT(mssql_providers.FirstName,' ',
            IF(mssql_providers.MiddleName != '',
            CONCAT(mssql_providers.MiddleName, ' '),''),
            mssql_providers.LastName) as clinicianName"),
            'mssql_providers.ClinicianID',
            'mssql_facilities.FacilityName',
            'mssql_employmentstatus.EmploymentStatus',
            'emps.EmploymentStatus as NewStatus',
            'mssql_providers.request_reason',
            DB::raw("users.name AS requestedBy"),"mssql_providers.request_outcome",
            DB::raw('CASE WHEN (mssql_providers.request_outcome = "Pending Advocacy"
            OR mssql_providers.request_outcome = "Rejected")
            THEN mssql_providers.request_outcome_reason
            WHEN mssql_providers.request_outcome = "Pending Documents"
            THEN mssql_providers.request_pending_document
            WHEN mssql_providers.request_outcome = "Completed"
            THEN (SELECT misc1 FROM notes WHERE
            mssql_providers.id = notes.entity_id
            AND notes.entity = \'Clinician\'
            AND notes.type = \'status_change_request\'
            ORDER BY notes.created_at DESC
            LIMIT 1
            )
            ELSE ""
            END as lastActivity'),
            DB::raw("DATE_FORMAT(mssql_providers.request_date, '%m/%d/%Y') as request_date"),
            DB::raw("(SELECT users.name from users
            left join users_has_roles on users.id = users_has_roles.user_id
            left join roles on users_has_roles.role_id = roles.id
            where roles.name='Orientation Coordinator'
            and users.status='1'
            and find_in_set(mssql_facilities.branchid, users.branchid) limit 1) as oc"));
        }

        switch($viewType) {
            case 'dashboard':
                $requestOutcome = config('constant')['statusChangeRequestTaskStatusDashboard'];
                $removedOutcome = '';
                // removed outcome "Requested"
                if (($key = array_search('Requested', $requestOutcome)) !== false) {
                    $removedOutcome = $requestOutcome[$key];
                    unset($requestOutcome[$key]);
                }
                
                $bucket->where('mssql_providers.requested_status','>',0);
                $bucket->where(function($q) use($requestOutcome,$removedOutcome) {
                    $q->whereIn('mssql_providers.request_outcome',$requestOutcome);
                    $q->orWhere(function($qq) use($removedOutcome) {
                        $qq->where("mssql_providers.request_outcome",$removedOutcome);
                        $qq->where("mssql_employmentstatus.EmploymentStatus","!=","Active Advocacy");
                    });
                });
                $search = $request->input('search.value');
                if (!empty($search)) {
                    $bucket->where(function ($query) use ($search){
                        $query->where('mssql_providers.Search_Name', 'LIKE', "%{$search}%");
                        $query->orWhere('mssql_facilities.FacilityName', 'LIKE', "%{$search}%");
                        $query->orWhereRaw("CONCAT(mssql_providers.FirstName, ' ', IF(mssql_providers.MiddleName != '', CONCAT(mssql_providers.MiddleName, ' '), ''), mssql_providers.LastName) LIKE ?", ["%{$search}%"]);
                    });
                }
            break;
            case 'showall':
                $columns = H::getHelper('StatusChangeRequests')->getDataTableHtml('showall');
                $search = $request->input('search.value');
                $limit = $request->input('length');
                $start = $request->input('start');
                $order = $columns[$request->input('order.0.column')]['name'];
                $dir = $request->input('order.0.dir');
                $showAllData['totalData'] = $bucket->count();
                $showAllData['totalFiltered'] = $showAllData['totalData'];
                $bucket->whereNotNull('request_outcome')->where('request_outcome','!=','');
                if (!empty($search)) {
                    $bucket->whereNested(function($q) use ($search) {
                        $q->where('mssql_providers.ProviderName', 'LIKE', "%{$search}%")
                        ->orWhere('mssql_providers.ClinicianID', 'LIKE', "%{$search}%")
                        ->orWhere('mssql_facilities.BranchName', 'LIKE', "%{$search}%")
                        ->orWhere('mssql_employmentstatus.EmploymentStatus', 'LIKE', "%{$search}%")
                        ->orWhereRaw("CONCAT(mssql_providers.FirstName, ' ', IF(mssql_providers.MiddleName != '', CONCAT(mssql_providers.MiddleName, ' '), ''), mssql_providers.LastName) LIKE ?", ["%{$search}%"]);
                    });
                }
                break;
            default:
                break;
        }
        // Filter Section
        $isMeMode = 0;
        $state = $region = $branch = $oc = $ocNewStatus = $ocTaskStatus=[];

        if(!empty($searchfilter)) {
            foreach($searchfilter as $filter) {
                $filterName = $filter['name'];
                $filterValue = $filter['value'];

                if($filterName=='ocdefault') {
                    $isMeMode =1;
                }
                if($filterName=='ocstate') {
                    $state[] =$filterValue;
                }
                if($filterName=='ocregion') {
                    $region[] =$filterValue;
                }
                if($filterName=='ipea_location_id') {
                    $branch[] =$filterValue;
                }
                if($filterName=='oc') {
                    $oc[] =$filterValue;
                }
                if($filterName=='ocNewStatus') {
                    $ocNewStatus[] =$filterValue;
                }
                if($filterName=='ocTaskStatus') {
                    $ocTaskStatus[] =$filterValue;
                }
            }
        }
        $bucket->where('mssql_providers.is_deleted', '0');
        $userCurrentRole = H::getHelper('ConstantHelper')->getCurrentRolesNameByUserId($loggedInUser['id']);
        if($isMeMode) {
            $oc[] = $loggedInUser['id'];
        }
        
        if(count($oc)) {
            if(LD::checkFeatureFlag('status-change-request-request-oc-checkbox')) {
                $bucket->whereIn("mssql_providers.request_oc",$oc);
            } else {
                if(in_array('Advocacy Specialist',$userCurrentRole)){
                    $bucket->whereIn("mssql_providers.request_advocacy_specialist",$oc);
                }else{
                    $questionMarkString = H::generateQuestionMarksArray($oc);
                    $bucket->whereRaw("(SELECT users.id from users
                    left join users_has_roles on users.id = users_has_roles.user_id
                    left join roles on users_has_roles.role_id = roles.id
                    where roles.name='Orientation Coordinator' and users.status='1'
                    and find_in_set(mssql_facilities.branchid, users.branchid ) limit 1)
                    IN (".$questionMarkString.")", $oc);
                }
            }
            
        }
        
        if(count($state)>0) {
            $bucket->where(function($q) use ($state){
                foreach($state as $s) {
                    $q->orWhere("branch_locations.state","=",$s);
                }
            });
        }

        if(count($branch)>0) {
            $bucket->where(function($q) use ($branch){
                foreach($branch as $b) {
                    $q->orWhere("mssql_facilities.branchid","=",$b);
                }
            });
        }

        if(count($region)>0) {
            $bucket->where(function($q) use ($region){
                foreach($region as $b) {
                    $q->orWhere("regions.id","=",$b);
                }
            });
        }
        if(count($ocNewStatus)>0) {
            $bucket->where(function($q) use ($ocNewStatus){
                foreach($ocNewStatus as $b) {
                    $q->orWhere("mssql_providers.requested_status","=",$b);
                }
            });
        }
        if(count($ocTaskStatus)>0) {
            $bucket->where(function($q) use ($ocTaskStatus){
                foreach($ocTaskStatus as $b) {
                    $q->orWhere("mssql_providers.request_outcome","=",$b);
                }
            });
        }
        if($showCount) {
            return '('.$bucket->count().')';
        }
        H::ExcelLog('List: ' . $bucket->toSql(), 'getStatusChangeRequests.log');
        if($viewType == 'showall') {
            $showAllData['totalFiltered'] = $bucket->count();
            if($order == 'request_date') {
                $order = DB::raw('DATE('.$order.')');
            }
            $showAllData['data'] = $bucket->offset($start)->limit($limit)->orderBy($order, $dir)->get();
            return $showAllData;
        }else{
            return $bucket->get();
        }
    }

    public function getNewClinicianStatus(){
        $empStatus = DB::table('mssql_employmentstatus')->whereIn('mssql_employmentstatus.EmploymentStatus', config('constant')['statusChangeRequestStatus'])->get();
        $ocNewStatus = [];
        foreach($empStatus as $e) {
            $ocNewStatus[] = ["value"=>$e->id,'text'=>$e->EmploymentStatus];
        }
        return $ocNewStatus;
    }

    public function canShowCheckMark($status,$requested) {

        
        $currentStatus = ['Active Advocacy',"On Leave","Pending NCLEX","Pending Orientation","Pending Renewable","Standby","Terminated"];
        $requestedStatus = ["Active","Active Coverage","Cover Nurse","PRN Agreement","PPEC Agreement"];

        if(in_array($status,$currentStatus) && in_array($requested,$requestedStatus)) {
            return 1;
        }
        return 0;
        
    }
}
