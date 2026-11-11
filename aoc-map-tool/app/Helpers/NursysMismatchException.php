<?php

namespace App\Helpers;

use App\RecruitingSchedule as RecruitingSchedule;
use App\User;
use App\MssqlProviders;
use App\Renewables as RenewalbesTable;
use Carbon\Carbon;
use DB;

class NursysMismatchException
{

    public function getNursysMismatchData($viewType="dashboard",$bucketType = '',$searchfilter=[],$showCount=0,$request='') {
        $loggedInUser = H::getLoggedInUser();
        $showAllData = [];
        $search = '';$limit = '';$start = '';$order ='';$dir = '';

        $bucket = RenewalbesTable::select(
            'renewables.id',
            'renewables.aoc_provider_id',
            'mssql_providers.fullName',
            'mssql_providers.ClinicianID',
            'mssql_facilities.FacilityName',
            'mssql_employmentstatus.EmploymentStatus',
            'mssql_providers.DisciplineName',
            'renewables.license_no',
            'renewables.license_state',
            'renewables.expiration_date',
            'renewables.nursys_error',
            'renewables.nursys_mismatch_created_at'
        )
        ->leftJoin('mssql_providers','renewables.aoc_provider_id', '=', 'mssql_providers.id')
        ->leftJoin("mssql_facilities","mssql_facilities.FacilityID","=","mssql_providers.FacilityID")
        ->leftJoin("branch_locations","branch_locations.id","=","mssql_facilities.branchid")
        ->leftJoin("regions","regions.id","=","mssql_facilities.region")
        ->leftJoin("mssql_employmentstatus","mssql_employmentstatus.EmploymentStatusID","=","mssql_providers.EmploymentStatusID")
        ->whereNotNull('renewables.aoc_provider_id');


        if(LD::checkFeatureFlag('nursys-mismatch-show-all')){

            switch($viewType) {
                case 'dashboard':
                        switch($bucketType) {
                            case 'nursyslicensemismatch':
                               $bucket->whereRaw("ifnull(renewables.nursys_updated_at, '2001-01-01') < '".date('Y-m-d') . "'");
                               $bucket->whereNotNull("renewables.UniqueKey");
                               $bucket->where("renewables.is_deleted",0);
                               $bucket->where("renewables.renewables_type",46);
                               $bucket->where("renewables.nursys_sync_status",0);
                               $bucket->where("renewables.nursys_mismatch",1);
                               $bucket->whereIn("mssql_providers.DisciplineName",["RN","LPN","LVN"]);
                               $bucket->whereIn("mssql_employmentstatus.EmploymentStatus",
                               ["Active","Active Advocacy","Active Coverage","Cover Nurse","PPEC Agreement","PRN Agreement","Standby"]);

                            break;

                            default:
                                break;
                        }
                        break;
                case 'showall':
                        $bucket->whereNotNull("renewables.UniqueKey");
                        $bucket->where("renewables.is_deleted",0);
                        $bucket->where("renewables.renewables_type",46);
                        $bucket->where("renewables.nursys_sync_status",0);
                        $bucket->whereIn("mssql_providers.DisciplineName",["RN","LPN","LVN"]);
                        $bucket->whereIn("mssql_employmentstatus.EmploymentStatus",
                        ["Active","Active Advocacy","Active Coverage","Cover Nurse","PPEC Agreement","PRN Agreement","Standby"]);

                        $filters = H::getFilterInputArr($request->all());
                        $columns = H::getHelper('NursysMismatchException')->getDataTableHtml('');
                        $search = $request->input('search.value');
                        $limit = $request->input('length');
                        $start = $request->input('start');
                        $order = $columns[$request->input('order.0.column')]['name'];
                        $dir = $request->input('order.0.dir');
                        $showAllData['totalData'] = $bucket->count();
                        $showAllData['totalFiltered'] = $showAllData['totalData'];

                        if (!empty($search)) {
                            $bucket->where(function ($query) use ($search){
                                $query->where('mssql_providers.fullName', 'LIKE', "%{$search}%");
                                $query->orWhere('mssql_providers.ClinicianID', 'LIKE', "%{$search}%");
                                $query->orWhere('mssql_facilities.FacilityName', 'LIKE', "%{$search}%");
                                $query->orWhere('mssql_employmentstatus.EmploymentStatus', 'LIKE', "%{$search}%");
                                $query->orWhere('mssql_providers.DisciplineName', 'LIKE', "%{$search}%");
                                $query->orWhere('renewables.license_no', 'LIKE', "%{$search}%");
                                $query->orWhere('renewables.license_state', 'LIKE', "%{$search}%");
                            });
                        }
                        break;
                case 'export':
                    $bucket->whereNotNull("renewables.UniqueKey");
                    $bucket->where("renewables.is_deleted",0);
                    $bucket->where("renewables.renewables_type",46);
                    $bucket->where("renewables.nursys_sync_status",0);
                    $bucket->whereIn("mssql_providers.DisciplineName",["RN","LPN","LVN"]);
                    $bucket->whereIn("mssql_employmentstatus.EmploymentStatus",
                    ["Active","Active Advocacy","Active Coverage","Cover Nurse","PPEC Agreement","PRN Agreement","Standby"]);
                    $filters = H::getFilterInputArr($request->all());
                    $search = $request->input('search.value');

                    if (!empty($search)) {
                        $bucket->where(function ($query) use ($search){
                            $query->where('mssql_providers.fullName', 'LIKE', "%{$search}%");
                            $query->orWhere('mssql_providers.ClinicianID', 'LIKE', "%{$search}%");
                            $query->orWhere('mssql_facilities.FacilityName', 'LIKE', "%{$search}%");
                            $query->orWhere('mssql_employmentstatus.EmploymentStatus', 'LIKE', "%{$search}%");
                            $query->orWhere('mssql_providers.DisciplineName', 'LIKE', "%{$search}%");
                            $query->orWhere('renewables.license_no', 'LIKE', "%{$search}%");
                            $query->orWhere('renewables.license_state', 'LIKE', "%{$search}%");
                        });
                    }
                    break;
                default:
                    break;

            }

        } else {
            switch($viewType) {
                case 'dashboard':
                        switch($bucketType) {
                            case 'nursyslicensemismatch':
                               $bucket->whereRaw("ifnull(renewables.nursys_updated_at, '2001-01-01') < '".date('Y-m-d') . "'");
                               $bucket->whereNotNull("renewables.UniqueKey");
                               $bucket->where("renewables.is_deleted",0);
                               $bucket->where("renewables.renewables_type",46);
                               $bucket->where("renewables.nursys_sync_status",0);
                               $bucket->whereIn("mssql_providers.DisciplineName",["RN","LPN","LVN"]);
                               $bucket->whereIn("mssql_employmentstatus.EmploymentStatus",
                               ["Active","Active Advocacy","Active Coverage","Cover Nurse","PPEC Agreement","PRN Agreement","Standby"]);

                            break;
                            default:
                                break;
                        }
                        break;
                default:
                    break;

            }
        }



        // Filter Section
        $isMeMode = 0;
        $state = $region = $branch = $oc = $clinicianStatus = $renewableItems=[];
        $fromExpirationDate = '';
        $toExpirationDate = '';
        $ccc = '';

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
                    if($filterName=='nursys_clinicianstatus') {
                        $clinicianStatus[] =$filterValue;
                    }
            }

        }

        if($isMeMode) {
            $oc[] = $loggedInUser['id'];
        }


        if(count($clinicianStatus)>0){
            $bucket->where(function($q) use ($clinicianStatus){
                foreach($clinicianStatus as $c) {
                    $q->orWhere("mssql_employmentstatus.EmploymentStatus","=",$c);
                }
            });

        }
        if(count($oc)) {
            $bucket->whereRaw("(SELECT users.id from users left join users_has_roles on users.id = users_has_roles.user_id left join roles on users_has_roles.role_id = roles.id where roles.name='Orientation Coordinator' and users.status='1' and find_in_set(mssql_facilities.branchid, users.branchid ) limit 1)  IN ('" . implode("','", $oc) . "')");

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

        if(LD::checkFeatureFlag('nursys-mismatch-show-all')) {
            if($showCount) {
                return '('.$bucket->count().')';
            }
            if($viewType == 'showall') {

                $showAllData['totalFiltered'] = $bucket->count();
                $showAllData['data'] = $bucket->offset($start)->limit($limit)->orderBy($order, $dir)->get();

                return $showAllData;
            } else if($viewType == 'export') {
                return json_decode(json_encode($bucket->get()->toArray()), true);
            }
            H::ExcelLog($bucket->toSql(),'zzzznurshs.log');
            return $bucket->get();
        } else {
            if($showCount) {
                return '('.$bucket->count().')';
            }
            H::ExcelLog($bucket->toSql(),'zzzznurshs.log');
            return $bucket->get();
        }
    }

    public function getDataTableHtml($type)
    {

        $dataTable = [];

        if(LD::checkFeatureFlag('nursys-mismatch-show-all')) {
            switch($type) {
                case 'nursyslicensemismatch':
                    if(LD::checkFeatureFlag('nursys-error-field-bucket')) {

                        $dataTable = [
                            'nursys_mismatch_created_at'=>'Created Date/Time',
                            'fullName'=>'Clinician',
                            'ClinicianID'=>"ClinicianID",
                            'FacilityName'=>' Branch',
                            'EmploymentStatus'=>'Status',
                            'DisciplineName'=>'Discipline',
                            'license_no'=>'License No ',
                            'license_state'=>'License State',
                            'expiration_date'=>'Expiring',
                            'nursys_error'=>"Error Message"
                        ];

                    } else {
                        $dataTable = [
                            'nursys_mismatch_created_at'=>'Created Date/Time',
                            'fullName'=>'Clinician',
                            'ClinicianID'=>"ClinicianID",
                            'FacilityName'=>' Branch',
                            'EmploymentStatus'=>'Status',
                            'DisciplineName'=>'Discipline',
                            'license_no'=>'License No ',
                            'license_state'=>'License State',
                            'expiration_date'=>'Expiring',
                        ];
                    }

                    break;
                case 'export':
                    $dataTable = [
                        'nursys_mismatch_created_at'=>'Created Date/Time',
                        'fullName'=>'Clinician',
                        'ClinicianID'=>"ClinicianID",
                        'FacilityName'=>' Branch',
                        'EmploymentStatus'=>'Status',
                        'DisciplineName'=>'Discipline',
                        'license_no'=>'License No ',
                        'license_state'=>'License State',
                        'expiration_date'=>'Expiring',
                    ];
                    return $dataTable;
                default:
                    $dataTable = [
                        ['name'=>'nursys_mismatch_created_at','label'=>'Created Date/Time'],
                        ['name'=>'fullName','label'=>'Clinician'],
                        ['name'=>'ClinicianID','label'=>'ClinicianID'],
                        ['name'=>'FacilityName','label'=>'Branch'],
                        ['name'=>'EmploymentStatus','label'=>'Status'],
                        ['name'=>'DisciplineName','label'=>'Discipline'],
                        ['name'=>'license_no','label'=>'License No'],
                        ['name'=>'license_state','label'=>'License State'],
                        ['name'=>'expiration_date','label'=>'Expiring'],
                    ];
                    return $dataTable;
            }

           return view('orientation.nursysmismatchdatatable',["type"=>$type,'dataTable'=>$dataTable]);

        } else {
            switch($type) {
                case 'nursyslicensemismatch':
                    if(LD::checkFeatureFlag('nursys-error-field-bucket')) {
                        $dataTable = [
                            'nursys_mismatch_created_at'=>'Created Date/Time',
                            'fullName'=>'Clinician',
                            'ClinicianID'=>"ClinicianID",
                            'FacilityName'=>' Branch',
                            'EmploymentStatus'=>'Status',
                            'DisciplineName'=>'Discipline',
                            'license_no'=>'License No ',
                            'license_state'=>'License State',
                            'expiration_date'=>'Expiring',
                            'nursys_error'=>"Error Message"
                        ];

                    } else {
                        $dataTable = [
                            'nursys_mismatch_created_at'=>'Created Date/Time',
                            'fullName'=>'Clinician',
                            'ClinicianID'=>"ClinicianID",
                            'FacilityName'=>' Branch',
                            'EmploymentStatus'=>'Status',
                            'DisciplineName'=>'Discipline',
                            'license_no'=>'License No ',
                            'license_state'=>'License State',
                            'expiration_date'=>'Expiring',
                        ];
                    }
                    break;
                default:
                    break;
            }

           return view('orientation.nursysmismatchdatatable',["type"=>$type,'dataTable'=>$dataTable]);
        }

    }

}
