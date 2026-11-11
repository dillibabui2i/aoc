<?php
namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PaycomKantimeException
{
    public function getDataTableHtml($type)
    {
        $dataTable = [];
        switch($type) {
            case 'paycom_kantime_exception':
            $dataTable = [
                'Employee'=>'Employee',
                'ID'=>"ID",
                'Location'=>'Location',
                'Message'=>'Message',
                'Paycom'=>'Paycom',
                'KanTime'=>'KanTime',
                'oc'=>'Orientation coordinator'
            ];
            break;
            default:
                break;
        }
        return view('orientation.paycomedatatable',["type"=>$type,'dataTable'=>$dataTable]);
    }

    public function getPaycomKantimeExceptionData($viewType="dashboard",$bucketType = '',$searchfilter=[],$showCount=0,$request='') {
        
        $empStatus = ['Active', 'On Leave', 'Deceased', 'Inactive', 'Retired', 'Terminated'];
        $ktEmpStatus = "('Active','Active Coverage','Active Advocacy','Cover Nurse','PPEC Agreement','PRN Agreement')";
        $limit = 100;
        $loggedInUser = H::getLoggedInUser();
         // Filter Section
         $isMeMode = 0;
         $state = $region = $branch = $oc = $discrepancyType = [];
 
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
                     if($filterName=='discrepancy_type') {
                         $discrepancyType[] =$filterValue;
                     }
             }
 
         }


        H::ExcelLog('start bucket','dbPaycom.log');
        $aocProviderId = "mssql_providers.id as aoc_provider_id";
        $paycomId = "paycom.id as paycomId";
        $employee = "concat(paycom.FirstName,' ',paycom.LastName) as Employee";
        $paycomClinicianID = "paycom.EmployeeCode as ID";
        $paycomLocation = "paycom.PaycomFacilityName as Location";
        $orientationCoordinator = "(SELECT users.name from users
                left join users_has_roles on users.id = users_has_roles.user_id
                left join roles on users_has_roles.role_id = roles.id where roles.name='Orientation Coordinator'
                and users.status='1' and find_in_set(mssql_facilities.branchid, users.branchid) limit 1) as oc";

        //ID
        $idDescripancy = DB::table("paycom")->select(
            $aocProviderId,
            $paycomId,
            DB::raw($employee),
            $paycomClinicianID,
            $paycomLocation,
            DB::raw("'ID Discrepancy' as Message"),
            "paycom.EmployeeCode as Paycom",
            DB::raw("case when ifnull(mssql_providers.ClinicianID,'') = '' then 'Not Found' else
            mssql_providers.ClinicianID end as KanTime"),
            DB::raw($orientationCoordinator)
        )
        ->leftJoin('mssql_providers','paycom.EmployeeCode', '=', 'mssql_providers.ClinicianID')
        ->leftJoin('mssql_facilities','mssql_facilities.FacilityName', '=', 'paycom.PaycomFacilityName')
        ->leftJoin("branch_locations","branch_locations.id","=","mssql_facilities.branchid")
        ->leftJoin("regions","regions.id","=","mssql_facilities.region")
        ->leftJoin('mssql_employmentstatus','mssql_providers.EmploymentStatusID','=',
            'mssql_employmentstatus.EmploymentStatusID')
        ->whereRaw(" ifnull(mssql_employmentstatus.EmploymentStatus,'Active') in " . $ktEmpStatus)
        ->whereRaw("ifnull(mssql_providers.ClinicianID,'Not Found') <> paycom.EmployeeCode and paycom.id_fixed = 0");
        //->limit($limit);

        //Employment Type
        $employmentTypeDescripancy =  DB::table("paycom")->select(
            $aocProviderId,
            $paycomId,
            DB::raw($employee),
            $paycomClinicianID,
            $paycomLocation
            ,DB::raw("'Employment Type Discrepancy' as Message"),
            "paycom.DOLStatus as Paycom",
            DB::raw("EmployementType as KanTime"),
            DB::raw($orientationCoordinator)
        )
        ->leftJoin('mssql_providers','paycom.EmployeeCode', '=', 'mssql_providers.ClinicianID')
        ->leftJoin('mssql_facilities','mssql_facilities.FacilityName', '=', 'paycom.PaycomFacilityName')
        ->leftJoin("branch_locations","branch_locations.id","=","mssql_facilities.branchid")
        ->leftJoin("regions","regions.id","=","mssql_facilities.region")
        ->leftJoin('mssql_employmentstatus','mssql_providers.EmploymentStatusID','=',
            'mssql_employmentstatus.EmploymentStatusID')
        ->whereIn("paycom.PaycomEmploymentStatus",$empStatus)
        ->whereRaw("LOWER(REPLACE(paycom.DOLStatus,'-',' ')) != mssql_providers.EmployementType")
        ->whereRaw("not(mssql_providers.EmploymentStatusID = '12' and date(mssql_providers.updated_at) < '2022-12-01')");
        //->limit($limit);

        //Status - DISABLE FOR NOW
        $stautsDescripancy =  DB::table("paycom")->select(
            $aocProviderId,
            $paycomId,
            DB::raw($employee),
            $paycomClinicianID,
            $paycomLocation,
            DB::raw("'Status Discrepancy' as Message"),
            "paycom.PaycomEmploymentStatus as Paycom",
            DB::raw("mssql_employmentstatus.EmploymentStatus as KanTime"),
            DB::raw($orientationCoordinator)
        )
        ->leftJoin('mssql_providers','paycom.EmployeeCode', '=', 'mssql_providers.ClinicianID')
        ->leftJoin('mssql_employmentstatus','mssql_employmentstatus.EmploymentStatusID', '=',
            'mssql_providers.EmploymentStatusID')
        ->leftJoin('mssql_facilities','mssql_facilities.FacilityName', '=', 'paycom.PaycomFacilityName')
        ->leftJoin("branch_locations","branch_locations.id","=","mssql_facilities.branchid")
        ->leftJoin("regions","regions.id","=","mssql_facilities.region")
        ->whereIn("paycom.PaycomEmploymentStatus",$empStatus)
        //->where("paycom.PaycomEmploymentStatus",'DISABLED') //added to force empty results for now
        ->whereRaw("(
			CASE 
				WHEN `paycom`.`PaycomEmploymentStatus` = 'Active' THEN 
					`mssql_employmentstatus`.`EmploymentStatus` NOT IN
					('Active', 'Active Advocacy', 'Active Coverage', 'Cover Nurse', 'Pending Orientation',
					'Pending NCLEX', 'Pending Renewable', 'PPEC Agreement', 'Standby')
				WHEN `mssql_employmentstatus`.`EmploymentStatus` = 'Terminated' THEN 
					`paycom`.`PaycomEmploymentStatus` NOT IN ('Deceased', 'Inactive', 'Retired', 'Terminated')
				ELSE 
					`paycom`.`PaycomEmploymentStatus` != `mssql_employmentstatus`.`EmploymentStatus`
			END
		)")
        ->whereRaw("not(mssql_providers.EmploymentStatusID = '12' and date(mssql_providers.updated_at) < '2022-12-01')");
        //->limit($limit);

        //Location
        $locationDescripancy =  DB::table("paycom")->select(
            $aocProviderId,
            $paycomId,
            DB::raw($employee),
            $paycomClinicianID,
            $paycomLocation,
            DB::raw("'Location Discrepancy' as Message"),
            "paycom.PaycomFacilityName as Paycom",
            DB::raw("mssql_facilities.FacilityName as KanTime"),
            DB::raw($orientationCoordinator)
        )
        ->leftJoin('mssql_providers','paycom.EmployeeCode', '=', 'mssql_providers.ClinicianID')
        ->leftJoin('mssql_facilities','mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')
        ->leftJoin("branch_locations","branch_locations.id","=","mssql_facilities.branchid")
        ->leftJoin("regions","regions.id","=","mssql_facilities.region")
        ->leftJoin('mssql_employmentstatus','mssql_providers.EmploymentStatusID','=',
            'mssql_employmentstatus.EmploymentStatusID')
        ->whereRaw("ifnull(mssql_employmentstatus.EmploymentStatus,'Active') in " . $ktEmpStatus)
        ->whereRaw("paycom.PaycomFacilityName != mssql_facilities.FacilityName")
		->where('mssql_providers.IsOfficeStaff', '<>', '1')
        ->whereRaw("not(mssql_providers.EmploymentStatusID = '12' and date(mssql_providers.updated_at) < '2022-12-01')");
        //->limit($limit);

        //Email
        $emailDescripancy= DB::table("paycom")->select(
            $aocProviderId,
            $paycomId,
            DB::raw($employee),$paycomClinicianID,$paycomLocation,
            DB::raw("'Official/Work Email Discrepancy' as Message"),
            "paycom.WorkEmail as Paycom",
            DB::raw("mssql_providers.AOC_Email as KanTime"),
            DB::raw($orientationCoordinator)
        )
        ->leftJoin('mssql_providers','paycom.EmployeeCode', '=', 'mssql_providers.ClinicianID')
        ->leftJoin('mssql_facilities','mssql_facilities.FacilityName', '=', 'paycom.PaycomFacilityName')
        ->leftJoin("branch_locations","branch_locations.id","=","mssql_facilities.branchid")
        ->leftJoin("regions","regions.id","=","mssql_facilities.region")
        ->leftJoin('mssql_employmentstatus','mssql_providers.EmploymentStatusID','=',
            'mssql_employmentstatus.EmploymentStatusID')
        ->whereIn("paycom.PaycomEmploymentStatus",$empStatus)
        ->whereRaw("paycom.WorkEmail <> mssql_providers.AOC_Email and mssql_providers.IsOfficeStaff = 1")
        ->whereRaw("not(mssql_providers.EmploymentStatusID = '12' and date(mssql_providers.updated_at) < '2022-12-01')");
        //->limit($limit);

        //Name
        $nameDescripancy= DB::table("paycom")->select(
            $aocProviderId,
            $paycomId,
            DB::raw($employee),
            $paycomClinicianID,
            $paycomLocation,
            DB::raw("'Name Discrepancy' as Message"),
            DB::raw("concat(paycom.Firstname,' ',paycom.Lastname) as Paycom"),
            DB::raw("concat(mssql_providers.FirstName,' ',mssql_providers.LastName) as KanTime"),
            DB::raw($orientationCoordinator)
        )
        ->leftJoin('mssql_providers','paycom.EmployeeCode', '=', 'mssql_providers.ClinicianID')
        ->leftJoin('mssql_facilities','mssql_facilities.FacilityName', '=', 'paycom.PaycomFacilityName')
        ->leftJoin("branch_locations","branch_locations.id","=","mssql_facilities.branchid")
        ->leftJoin("regions","regions.id","=","mssql_facilities.region")
        ->leftJoin('mssql_employmentstatus','mssql_providers.EmploymentStatusID','=',
            'mssql_employmentstatus.EmploymentStatusID')
        ->whereIn("paycom.PaycomEmploymentStatus",$empStatus)
        ->whereRaw("paycom.EmployeeCode = ifnull(mssql_providers.ClinicianID,0) and
            concat(paycom.Firstname,' ',paycom.Lastname) <>
            concat(mssql_providers.FirstName,' ',mssql_providers.LastName)")
            ->whereRaw("not(mssql_providers.EmploymentStatusID = '12' and date(mssql_providers.updated_at) < '2022-12-01')");
        //->limit($limit);
        H::ExcelLog('end subquery','dbPaycom.log');

       

        $typesArray = ['ID Discrepancy'=>'idDescripancy',
        'Employment Type Discrepancy'=> 'employmentTypeDescripancy',
        'Status Discrepancy' => 'stautsDescripancy',
        'Location Discrepancy' => 'locationDescripancy',
        'Official/Work Email Discrepancy' => 'emailDescripancy',
        'Name Discrepancy' => 'nameDescripancy'];

           
        $bucket = null;
        $cntr = 0;

        if($isMeMode) {
            $oc[] = $loggedInUser['id'];
        }

        foreach($typesArray as $typeKey => $typeValue) {

            if(count($oc)) {
                $$typeValue->whereRaw("(SELECT users.id from users
                    left join users_has_roles on users.id = users_has_roles.user_id
                    left join roles on users_has_roles.role_id = roles.id
                    where roles.name='Orientation Coordinator' and users.status='1' and
                    find_in_set(mssql_facilities.branchid, users.branchid) limit 1)
                    IN ('" . implode("','", $oc) . "')");
                
            }
            if(count($state)>0) {
                $$typeValue->where(function($q) use ($state){
                    foreach($state as $s) {
                        $q->orWhere("branch_locations.state","=",$s);
                    }
                });
            }
    
            if(count($branch)>0) {
                $$typeValue->where(function($q) use ($branch){
                    foreach($branch as $b) {
                        $q->orWhere("mssql_facilities.branchid","=",$b);
                    }
                });
            }
    
            if(count($region)>0) {
                $$typeValue->where(function($q) use ($region){
                    foreach($region as $b) {
                        $q->orWhere("regions.id","=",$b);
                    }
                });
            }

        }

        if(!empty($discrepancyType)) {
            foreach($discrepancyType as $type){
                $var = $typesArray[$type];
                if(!$bucket) {
                    $bucket = $$var;
                } else {
                    $bucket->unionAll($$var);
                }
            }
        } else {
            $bucket = $idDescripancy
                ->unionAll($employmentTypeDescripancy)
                ->unionAll($stautsDescripancy)
                ->unionAll($locationDescripancy)
                ->unionAll($emailDescripancy)
                ->unionAll($nameDescripancy);
        }

        H::ExcelLog('end bucket','dbPaycom.log');
       

        if($showCount) {
            return '('.$bucket->count().')';
        }
        H::ExcelLog('List: ' . $bucket->toSql(), 'getStatusChangeRequests.log');
        return $bucket->get();
    }

}
