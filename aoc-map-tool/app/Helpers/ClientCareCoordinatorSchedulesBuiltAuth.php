<?php
namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\LD;

class ClientCareCoordinatorSchedulesBuiltAuth
{
    public $includedServices = [
        "CNA Group Additional",
        "CNA Group Primary",
        "CNA Long Term",
        "CNA Long Term EVV EXEMPT",
        "CNA Long Term Relative Caregiver",
        "Coverage LVN/LPN",
        "Coverage LVN/LPN MDCP",
        "Coverage LVN/LPN Specialized",
        "Coverage RN",
        "Coverage RN MDCP",
        "Coverage RN Specialized",
        "Coverage RN Specialized MDCP",
        "Coverage RN to LVN/LPN",
        "IHA CNA",
        "LHA In Home Caregiver",
        "LPN",
        "LPN GROUP ADDITIONAL",
        "LPN GROUP PRIMARY",
        "LVN/LPN",
        "LVN/LPN MDCP",
        "LVN/LPN SPECIALIZED",
        "LVN/LPN SPECIALIZED MDCP",
        "PNA CNA",
        "RN",
        "RN EVV EXEMPT",
        "RN MDCP",
        "RN SPECIALIZED",
        "RN SPECIALIZED MDCP",
        "RN TO LVN SPECIALIZED",
        "RN TO LVN/LPN"
    ];
    
    public $buckets = [
        "new_auth"=>["heading"=>"New Auth"],
        "current_auth"=>["heading"=>"Current Auth"],
        "clients_without_auth"=>["heading"=>"Clients without Auth"]
    ];


    public function getBuckets(){
        return $this->buckets;
    }

    public function getBucketArray() {
        return [
        "new_auth"=>"New Auth",
        "current_auth"=>"Current Auth",
        "clients_without_auth"=>"Clients without Auth"];
    }
    public function getBucketTitle($type) {
        return $this->buckets[$type]['heading'];
    }

    public function getDataTableHtml($type)
    {
        $dataTable = [];
        if($type == 'clients_without_auth') {
            $dataTable = ['PatientName'=>'Client',
            'PatientAccount'=>'Client ID',
            'PatientStatus'=>'Status',
            'FacilityName'=>'Location'];
        } else {
            $dataTable = ['PatientName'=>'Client',
            'PatientAccount'=>'Client ID',
            'PatientStatus'=>'Status',
            'FacilityName'=>'Location',
            'AuthPeriod' => 'Auth Period',
            'HoursNeeded' => 'Authorized Hours',
            'TotalHours' => 'Hours Scheduled'];
        }
        
        return view('ccc.schedulesBuiltAuthDatatable',["type"=>$type,'dataTable'=>$dataTable]);
    }
    private function setFilters(&$bucket, $searchfilter)
    {
        $filters = $this->extractFilters($searchfilter);
        foreach ($filters as $key => $values) {
            switch ($key) {
                case 'scheduleBuiltAuthMeMode':
                    $bucket->where("mssql_patients.ccc_id","=",Auth::user()->id);
                    break;
                case 'scheduleBuiltAuthState':
                    $this->applyFacilityFilter($bucket, 'mssql_patients.aoc_facility_id', $values);
                    break;
                case 'scheduleBuiltAuthBranch':
                    $this->applyFilter($bucket, 'mssql_patients.aoc_facility_id', $values);
                    break;
                case 'scheduleBuiltAuthRegion':
                    $this->applyFilter($bucket, 'mssql_facilities.region', $values);
                    break;
                case 'scheduleBuiltAuthCccId':
                    $this->applyFilter($bucket, 'mssql_patients.ccc_id', $values);
                    break;
                case 'scheduleBuiltAuthPayer':
                    $this->applyFilter($bucket, 'mssql_patients.Payer', $values);
                    break;
                case 'scheduleBuiltAuthPayerGroup':
                        $this->applyFilter($bucket, 'mssql_patients.payer_group', $values);
                        break;
                case 'scheduleBuiltAuthPatient_CurrentStatusID':
                    $this->applyFilter($bucket, 'mssql_patients.Patient_CurrentStatusID', $values);
                    break;
                case 'scheduleBuiltAuthIntake':
                    H::ExcelLog(print_r($values,true),'ztest.log');
                    $this->applyIntakeFilter($bucket, 'mssql_patients.Patient_CurrentStatusID', $values[0], '<=');
                    break;
                case 'builtVsAuthLob':
                    $this->applyFilter($bucket, 'mssql_patients.LOBID', $values);
                    break;
                default:
                    // Do nothing
                    break;
            }
        }
    }

    private function extractFilters($searchfilter){
        $filters = [];
        if (!empty($searchfilter)) {
            foreach ($searchfilter as $filter) {
                if (!empty($filter['value'])) {
                    $filters[$filter['name']][] = $filter['value'];
                }
            }
        }
        return $filters;
    }

    private function applyFacilityFilter(&$bucket, $column, $values){
        $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($values);
        $facilities = H::objToArr($facilities);
        $this->applyFilter($bucket, $column, array_map(function ($facility) {
            return $facility['id'];
        }, $facilities));
    }

    private function applyFilter(&$bucket, $column, $values){
        if (!empty($values)) {
            $bucket->where(function ($bucket) use ($column, $values) {
                foreach ($values as $value) {
                    $bucket->orWhere($column, '=', $value);
                }
            });
        }
    }
    private function applyIntakeFilter(&$bucket, $column, $value){
        $convertedVal = '<>';
        if($value=='Yes'){
            $convertedVal = '=';
        } 
        $bucket->where($column, $convertedVal, 4);
    }
    public function getClientCareCoordinatorSchedulesBuiltAuth($request = '',$showCount=0) {
        H::ExcelLog(print_r($request['type'],true),'ClientCareCoordinatorSchedulesBuiltAuth.log');
        $bucketType = '';
        if(isset($request['type'])){
            $bucketType = $request['type'];
        }
        $bucket = DB::table("authorizations");
        $bucket->select(
        DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS PatientName"),
        'mssql_patients.PatientAccount',
        'mssql_patientstatus.PatientStatus',
        'mssql_patients.PatientID',
        'mssql_patients.AuthorizedWeeklyHours',
        'mssql_patients.AuthorizedWeeklyVisits',
        'mssql_patients.id as aoc_patient_id',
        DB::raw("CONCAT(DATE_FORMAT(authorizations.start_date,'".config('constant')['mysqlsearchdateformat']."'),
        ' - ',
        DATE_FORMAT(authorizations.end_date,'".config('constant')['mysqlsearchdateformat']."')) AS AuthPeriod"),
        DB::raw("datediff(end_date,start_date)+1 as DayCount,
        round(mssql_patients.AuthorizedWeeklyHours / 7 * (datediff(end_date,start_date)+1),2) as HoursNeeded"),
        'mssql_facilities.FacilityName'
        );
        if(LD::checkFeatureFlag('considering-parentchild-schedules-built-auth')) {
            $bucket->addSelect(DB::raw("round(sum(mssql_schedulemaster.PlannedHours), 2) as TotalHours"));
        } else {
            $bucket->addSelect(DB::raw("round(sum(case when mssql_schedulemaster.isChild <> 1 then mssql_schedulemaster.PlannedHours else 0 end), 2) as TotalHours"));
        }
        if($bucketType=='current_auth'){
            $bucket->leftJoin("mssql_patients",function($query){
                $query->on("mssql_patients.PatientID","=","authorizations.PatientID");
                if(LD::checkFeatureFlag('considering-parentchild-schedules-built-auth')) {
                    $query->on("mssql_patients.active_auth_id","=","authorizations.id");
                } else {
                    $query->on(DB::raw("IFNULL(mssql_patients.active_auth_id, mssql_patients.active_respite_auth_id)"),"=","authorizations.id");
                }
            });
        }
        if($bucketType=='new_auth'){
            $bucket->leftJoin("mssql_patients",function($query){
                $query->on("mssql_patients.PatientID","=","authorizations.PatientID");
                if(LD::checkFeatureFlag('considering-parentchild-schedules-built-auth')) {
                    $query->on("mssql_patients.future_auth_id","=","authorizations.id");
                } else {
                    $query->on(DB::raw("IFNULL(mssql_patients.future_auth_id, mssql_patients.future_respite_auth_id)"),"=","authorizations.id");
                }
            });
        }
        $bucket->leftJoin("mssql_facilities",function($query){
            $query->on("mssql_facilities.id","=","mssql_patients.aoc_facility_id");
        });
        $bucket->leftJoin("mssql_patientstatus",function($query){
            $query->on("mssql_patientstatus.PatientStatusID","=","mssql_patients.Patient_CurrentStatusID");
        });
        $bucket->leftJoin("payment_source as payers",function($query){
            $query->on("authorizations.payer_id","=","payers.payersource_id");
        });
        $bucket->leftJoin("mssql_schedulemaster", function ($query) {
            if(LD::checkFeatureFlag('considering-parentchild-schedules-built-auth')) {
                $query->on('mssql_schedulemaster.PatientID', '=', 'mssql_patients.PatientID')
                ->whereColumn('mssql_schedulemaster.ScheduleDate', '>=', 'authorizations.start_date')
                ->whereColumn('mssql_schedulemaster.ScheduleDate', '<=', 'authorizations.end_date')
                ->where(function($query) {
                    $query->where(function ($q) {
                        $q->where('mssql_schedulemaster.ParentCgTaskID', '=', 0)
                        ->where('mssql_schedulemaster.HasChild', '=', 0);
                    })->orWhere('mssql_schedulemaster.ParentCgTaskID', '>', 0);
                });
            } else {
                $query->on("mssql_schedulemaster.PatientID", "=", "mssql_patients.PatientID")
                ->whereRaw("mssql_schedulemaster.ScheduleDate >= authorizations.start_date")
                ->whereRaw("mssql_schedulemaster.ScheduleDate <= authorizations.end_date");
            }
        });
        $bucket->leftJoin("service_codes_daas", function ($query) {
            $query->on("service_codes_daas.service_id", "=", "mssql_schedulemaster.ServiceCodeID");
        });
        if(LD::checkFeatureFlag('add-lob-filter-to-built-vs-auth-bucket')){
            $bucket->leftJoin("lob",function($query){
                $query->on("lob.LOBID","=","mssql_patients.LOBID");
            });
            $bucket->addSelect('mssql_patients.LOBID');
        }
        $bucket->whereIn("mssql_patients.Patient_CurrentStatusID",[1,4,6,8]);
        $bucket->whereRaw("ifnull(mssql_patients.id,0) <> 0");
        $bucket->whereRaw("authorizations.end_date >= CURRENT_DATE");
        $bucket->whereRaw("ifnull(mssql_patients.auth_corrected_at,'2000-01-01') < CURRENT_DATE");
        $bucket->where(function ($query) {
            $query->whereIn('mssql_patients.LOBID', [1002])
            ->orWhereRaw("(mssql_facilities.State = 'CO' AND mssql_patients.LOBID = 1023)");
        });
		if (LD::checkFeatureFlag('client-new-changes')) {
            $bucket->where('mssql_patients.IsLatestAdmit', 1);
        }
        if(!LD::checkFeatureFlag('considering-parentchild-schedules-built-auth')) {
            $bucket->where('mssql_schedulemaster.isChild', '<>', 1);
        }
        $bucket->whereIn('service_codes_daas.name', $this->includedServices);
        $bucket->where('payers.name', 'NOT LIKE', '%RESPITE%');
        if(LD::checkFeatureFlag('remove-prn-and-test-client-from-built-vs-auth-buckets')){
            $bucket->where('mssql_patients.isPRN', '<>', 1);
            $bucket->where('mssql_patients.Search_Name', 'NOT LIKE', '%test%');
        }
        $bucket->groupBy(
            'mssql_patients.PatientID',
            'authorizations.id'
        );
        //Filters
        $filters = isset($request['filters']) ? $request['filters'] : [];
        //Setting the filters
        $this->setFilters($bucket,$filters);
        
        if(LD::checkFeatureFlag('exclude-record-from-new-and-current-auth-bucket-where-are-hours-are-same')){
            $results = $bucket->get()->filter(function ($row) {
                return $row->HoursNeeded != $row->TotalHours;
            })->values();
            H::ExcelLog($bucket->toSql(),'ClientCareCoordinatorSchedulesBuiltAuth.log');
            if($showCount) {
                return '('.$results->count().')';
            }
            H::ExcelLog("Bucket==".$bucket->toSql(),'ClientCareCoordinatorSchedulesBuiltAuth.log');
            return $results;
        }else{
            H::ExcelLog($bucket->toSql(),'ClientCareCoordinatorSchedulesBuiltAuth.log');
            if($showCount) {
                return '('.$bucket->count().')';
            }
            H::ExcelLog("Bucket==".$bucket->toSql(),'ClientCareCoordinatorSchedulesBuiltAuth.log');
            return $bucket->get();
        }
    }

    public function getClientCareCoordinatorSchedulesBuiltClientWithoutAuth($request = '',$showCount=0) {
        $bucket = DB::table("mssql_patients");
        $bucket->select(
            DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS PatientName"),
            'mssql_patients.PatientAccount',
            'mssql_patientstatus.PatientStatus',
            'mssql_patients.PatientID',
            'mssql_patients.AuthorizedWeeklyHours',
            'mssql_patients.AuthorizedWeeklyVisits',
            'mssql_patients.id as aoc_patient_id',
            DB::raw("mssql_patients.PatientID as HoursNeeded"),
            DB::raw("'' as TotalHours"),
            'mssql_facilities.FacilityName'
        );
        $bucket->leftJoin("mssql_facilities",function($query){
            $query->on("mssql_facilities.id","=","mssql_patients.aoc_facility_id");
        });
        $bucket->leftJoin("mssql_patientstatus",function($query){
            $query->on("mssql_patientstatus.PatientStatusID","=","mssql_patients.Patient_CurrentStatusID");
        });
        $bucket->whereIn("mssql_patients.Patient_CurrentStatusID",[1,4,6,8]);
        $bucket->whereNull("mssql_patients.active_auth_id");
        $bucket->whereNull("mssql_patients.active_respite_auth_id");
        $bucket->whereRaw("ifnull(mssql_patients.auth_corrected_at,'2000-01-01') < CURRENT_DATE");
        $bucket->where(function ($query) {
            $query->whereIn('mssql_patients.LOBID', [1002])
            ->orWhereRaw("(mssql_facilities.State = 'CO' AND mssql_patients.LOBID = 1023)");
        });
		if (LD::checkFeatureFlag('client-new-changes')) {
            $bucket->where('mssql_patients.IsLatestAdmit', 1);
        }
        $bucket->whereExists(function ($query) {
            $query->select(DB::raw(1))
                  ->from('mssql_schedulemaster')
                  ->leftJoin('service_codes_daas', 'service_codes_daas.service_id', '=', 'mssql_schedulemaster.ServiceCodeID')
                  ->whereColumn('mssql_schedulemaster.PatientID', 'mssql_patients.PatientID')
                  ->where('mssql_schedulemaster.isChild', '<>', 1)
                  ->whereIn('service_codes_daas.name', $this->includedServices);
        });
        $bucket->where('mssql_patients.Payer', 'NOT LIKE', '%RESPITE%');
        if(LD::checkFeatureFlag('remove-prn-and-test-client-from-built-vs-auth-buckets')){
            $bucket->where('mssql_patients.isPRN', '<>', 1);
            $bucket->where('mssql_patients.Search_Name', 'NOT LIKE', '%test%');
        }
        //Filters
        $filters = isset($request['filters']) ? $request['filters'] : [];
        //Setting the filters
        $this->setFilters($bucket,$filters);
        H::ExcelLog($bucket->toSql(),'getClientCareCoordinatorSchedulesBuiltClientWithoutAuth.log');
        if($showCount) {
            return '('.$bucket->count().')';
        }
        return $bucket->get();
    }
}
