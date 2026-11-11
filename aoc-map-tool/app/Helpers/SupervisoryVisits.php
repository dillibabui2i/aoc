<?php
namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SupervisoryVisits
{
    private function getEffectiveAssignedToExpr()
    {
        return DB::raw('IF(supervisory_visits.assigned_to IS NOT NULL AND supervisory_visits.assigned_to <> 0, supervisory_visits.assigned_to, mssql_patients.ccm_id)');
    }

    public function getDataTableHtml($type)
    {
        $dataTable = [];
        if($type == 'showall'){
            return [
                ['name' => 'id','label' => 'id', 'visible' => false, 'width' => '0'],
                ['name' => 'ccmName', 'label' => 'CCM', 'width' => '10%'],
                ['name' => 'clinicianName', 'label' => 'Clinician Name', 'orderable' => false, 'width' => '10%'],
                ['name' => 'DisciplineName', 'label' => 'Clinician Discipline', 'width' => '10%'],
                ['name' => 'clientName', 'label' => 'Client Name', 'orderable' => false, 'width' => '10%'],
                ['name' => 'LOBName', 'label' => 'LOB', 'width' => '10%'],
                ['name' => 'FacilityName', 'label' => 'Branch', 'width' => '10%'],
                ['name' => 'last_sup_visit','label' => 'Last Sup Visit', 'width' => '10%'],
                ['name' => 'days_since_last_sup_visit','label' => 'Days Since Last Sup Visit', 'width' => '10%'],
                ['name' => 'visit_date_due','label' => 'Next Sup Visit', 'width' => '10%'],
            ];
        }else{
            if($type == 'dashboard'){
                if (LD::checkFeatureFlag('client-clinician-status-columns-and-filters-supervisory-visit-tracking-bucket')) {
                    $dataTable = [
                        ['name' => 'select', 'label' => 'select'],
                        ['name' => 'ccmName', 'label' => 'CCM'],
                        ['name' => 'clinicianName', 'label' => 'Clinician Name'],
                        ['name' => 'DisciplineName', 'label' => 'Clinician Discipline'],
                        ['name' => 'EmploymentStatus', 'label' => 'Clinician Status'],
                        ['name' => 'clientName', 'label' => 'Client Name'],
                        ['name' => 'PatientStatus', 'label' => 'Client Status'],
                        ['name' => 'LOBName', 'label' => 'LOB'],
                        ['name' => 'FacilityName', 'label' => 'Branch'],
                        ['name' => 'last_sup_visit', 'label' => 'Last Sup Visit'],
                        ['name' => 'days_since_last_sup_visit', 'label' => 'Days Since Last Sup Visit'],
                        ['name' => 'visit_date_due', 'label' => 'Next Sup Visit'],
                    ];
                } else {
                    $dataTable = [
                        ['name' => 'select', 'label' => 'select'],
                        ['name' => 'ccmName', 'label' => 'CCM'],
                        ['name' => 'clinicianName', 'label' => 'Clinician Name'],
                        ['name' => 'DisciplineName', 'label' => 'Clinician Discipline'],
                        ['name' => 'clientName', 'label' => 'Client Name'],
                        ['name' => 'LOBName', 'label' => 'LOB'],
                        ['name' => 'FacilityName', 'label' => 'Branch'],
                        ['name' => 'last_sup_visit', 'label' => 'Last Sup Visit'],
                        ['name' => 'days_since_last_sup_visit', 'label' => 'Days Since Last Sup Visit'],
                        ['name' => 'visit_date_due', 'label' => 'Next Sup Visit'],
                    ];
                }

                $dataTable[] = ['name' => 'visit_not_matched', 'label' => 'Status'];

            }else{
                if(LD::checkFeatureFlag('client-clinician-status-columns-and-filters-supervisory-visit-tracking-bucket')){
                    $dataTable = [
                        'select'=>'select',
                        'ccmName' => 'CCM',
                        'clinicianName' => 'Clinician Name',
                        'DisciplineName' => 'Clinician Discipline',
                        'EmploymentStatus' => 'Clinician Status',
                        'clientName' => 'Client Name',
                        'PatientStatus' => 'Client Status',
                        'LOBName' => 'LOB',
                        'FacilityName' => 'Branch',
                        'last_sup_visit' => 'Last Sup Visit',
                        'days_since_last_sup_visit' => 'Days Since Last Sup Visit',
                        'visit_date_due' => 'Next Sup Visit',
                    ];
                } else {
                    $dataTable = [
                        'select'=>'select',
                        'ccmName' => 'CCM',
                        'clinicianName' => 'Clinician Name',
                        'DisciplineName' => 'Clinician Discipline',
                        'clientName' => 'Client Name',
                        'LOBName' => 'LOB',
                        'FacilityName' => 'Branch',
                        'last_sup_visit' => 'Last Sup Visit',
                        'days_since_last_sup_visit' => 'Days Since Last Sup Visit',
                        'visit_date_due' => 'Next Sup Visit',
                    ];
                }
                $dataTable['visit_not_matched'] = 'Status';
            }
        }
        return view('supervisoryvisits.datatable',["type"=>$type,'dataTable'=>$dataTable]);
    }

    public function getSupervisoryVisits($viewType="dashboard",$searchfilter=[],$showCount=0,$request='') {
        $search = '';$limit = '';$start = '';$order ='';$dir = '';
        $bucket = DB::table("supervisory_visits");
        $ccmIdExpr = $this->getEffectiveAssignedToExpr();
        $bucket->leftJoin("mssql_providers",function($query){
            $query->on("mssql_providers.id","=","supervisory_visits.aoc_provider_id");
        });
        $bucket->leftJoin("mssql_patients",function($query){
            $query->on("mssql_patients.id","=","supervisory_visits.aoc_patient_id");
        });
        $bucket->leftJoin('users',function($query) use ($ccmIdExpr){
            $query->on('users.id','=',$ccmIdExpr);
        });
        $bucket->leftJoin("mssql_facilities", function($query){
            $query->on('mssql_patients.aoc_facility_id', '=', 'mssql_facilities.id');
        });
        $bucket->leftJoin("lob",function($query){
            $query->on("lob.LOBID","=","mssql_patients.LOBID");
        });
        $bucket->select('supervisory_visits.id',
        'supervisory_visits.aoc_provider_id',
        'supervisory_visits.aoc_patient_id',
        DB::raw("users.name AS ccmName"),
        DB::raw("CONCAT(mssql_providers.FirstName,' ',
        IF(mssql_providers.MiddleName != '',CONCAT(mssql_providers.MiddleName, ' '),''),
        mssql_providers.LastName) as clinicianName"),
        'mssql_providers.DisciplineName',
        DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',
        IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),
        mssql_patients.Patient_LastName) as clientName"),
        'lob.LOBName','mssql_facilities.FacilityName',
        DB::raw("supervisory_visits.last_visit AS last_sup_visit"),
        DB::raw("DATEDIFF(now(),supervisory_visits.last_visit)
        AS days_since_last_sup_visit"),
        DB::raw("DATE_FORMAT(supervisory_visits.visit_date_due, '%m/%d/%Y') as visit_date_due"),
        'supervisory_visits.completed_at',
        'supervisory_visits.completed_by',
        DB::raw("DATEDIFF(now(),supervisory_visits.visit_date_due)
        AS days_visit_due"),
        'supervisory_visits.visit_not_matched',);
        $todayDate = date('Y-m-d');
        switch($viewType) { // +1
            case 'dashboard':
                if(LD::checkFeatureFlag('client-clinician-status-columns-and-filters-supervisory-visit-tracking-bucket')
                ){
                    $bucket->addSelect('mssql_employmentstatus.EmploymentStatus');
                    $bucket->addSelect('mssql_patientstatus.PatientStatus as PatientStatus');
                    $bucket->leftJoin("mssql_employmentstatus",
                    "mssql_employmentstatus.id","=","mssql_providers.EmploymentStatusID");
                    $bucket->leftJoin("mssql_patientstatus", function ($query) {
                        $query->on("mssql_patients.Patient_CurrentStatusID", '=', 'mssql_patientstatus.id');
                    });
                }
                $bucket->where(function ($query) use($todayDate){
                    $query->where(function ($query){
                        $query->whereNull('supervisory_visits.completed_at')
                        ->whereRaw('supervisory_visits.visit_date_due <= DATE_ADD(now(),INTERVAL 60 DAY)');
                    });
                        $query->orWhere(function ($query) use($todayDate){
                            $query->where('supervisory_visits.completed_at', '<', $todayDate);
                            $query->whereNotNull('supervisory_visits.form_sup_visit_date');
                            $query->where(DB::raw("ifnull((SELECT Date_Format(sm.form_date,'%m/%d/%Y') as date
                            FROM supervisory_form_master sm
                            WHERE sm.client_id = mssql_patients.BillingInternalPatientID
                            and sm.supervisee_cgid = mssql_providers.BillingProviderID
                            and Date_Format(sm.form_date,'%m/%d/%Y') = Date_Format(supervisory_visits.form_sup_visit_date,'%m/%d/%Y') limit 1),
                            '2000-01-01')"),
                            '!=',DB::raw("Date_Format(supervisory_visits.form_sup_visit_date,'%m/%d/%Y')"));
                        });
                });
            break;
            case 'export':
                $bucket->addSelect('supervisory_visits.item1_score','supervisory_visits.item2_score',
                'supervisory_visits.item3_score',
                'supervisory_visits.item4_score',
                DB::raw("DATE_FORMAT(supervisory_visits.completed_at, '%m/%d/%Y') as completed_date"),
                DB::raw("completed_users.name AS completed_by_name"),
                DB::raw("CASE WHEN supervisory_visits.completed_at is null THEN 'Pending'
                ELSE 'Completed' END as current_status"),
            );
                $bucket->leftJoin("users as completed_users",function($query){
                    $query->on("completed_users.id","=","supervisory_visits.completed_by");
                });
                if(!empty($request->input('type'))) {
                    $bucket->where(function ($query) use($todayDate){
                        $query->where(function ($query){
                            $query->whereNull('supervisory_visits.completed_at')
                            ->whereRaw('supervisory_visits.visit_date_due <= DATE_ADD(now(),INTERVAL 60 DAY)');
                        });
                            $query->orWhere(function ($query) use($todayDate){
                                $query->where('supervisory_visits.completed_at', '<', $todayDate);
                                $query->whereNotNull('supervisory_visits.form_sup_visit_date');
                                $query->whereRaw('supervisory_visits.visit_date_due >= DATE_SUB(now(), INTERVAL 90 DAY)');
                                $query->where(DB::raw("ifnull((SELECT Date_Format(sm.form_date,'%m/%d/%Y') as date
                                FROM supervisory_form_master sm
                                WHERE sm.client_id = mssql_patients.BillingInternalPatientID
                                and sm.supervisee_cgid = mssql_providers.BillingProviderID
                                and Date_Format(sm.form_date,'%m/%d/%Y') = Date_Format(supervisory_visits.form_sup_visit_date,'%m/%d/%Y') limit 1),
                                '2000-01-01')"),
                                '!=',DB::raw("Date_Format(supervisory_visits.form_sup_visit_date,'%m/%d/%Y')"));
                            });
                    });
                }
                break;
            case 'showall':
                $columns = H::getHelper('SupervisoryVisits')->getDataTableHtml('showall');
                $limit = $request->input('length');
                $start = $request->input('start');
                $order = $columns[$request->input('order.0.column')]['name'];
                $dir = $request->input('order.0.dir');
                $showAllData['totalData'] = $bucket->count();
                $showAllData['totalFiltered'] = $showAllData['totalData'];
                break;
            default:
                break;
        }
        // Filter Section
        $isMeMode = 0;
        $state = $region = $branch = $users = $discipline = $lob=$clinicianStatus=$clientStatus=[];
        $startDate = $endDate ='';
        if(!empty($searchfilter)) { // +1
            foreach($searchfilter as $filter) { // +2
                $filterName = $filter['name'];
                $filterValue = $filter['value'];

                if($filterName=='svdefault') { // +3
                    $isMeMode =1;
                }
                if($filterName=='svstate') { // +3
                    $state[] =$filterValue;
                }
                if($filterName=='svregion') { // +3
                    $region[] =$filterValue;
                }
                if($filterName=='svbranch') { // +3
                    $branch[] =$filterValue;
                }
                if($filterName=='svclinicalcaremanager'){ // +3
                    $users[] = $filterValue;
                }
                if($filterName=='discipline') { // +3
                    $discipline[] =$filterValue;
                }
                if($filterName=='LOBID') { // +3
                    $lob[] =$filterValue;
                }
                if($filterName=='fromvisit_date_due' && !empty($filterValue)) {
                    $startDate = $filterValue;
                }
                if($filterName=='tovisit_date_due' && !empty($filterValue)) {
                    $endDate = $filterValue;
                }
                if($filterName=='svClinicianStatus') {
                    $clinicianStatus[] =$filterValue;
                }
                if($filterName=='svPatient_CurrentStatusID') {
                    $clientStatus[] =$filterValue;
                }
            }
        }
        $search = $request->input('search.value');
        if (!empty($search)) { // +1
            $bucket->where(function ($query) use ($search){
                $query->where('mssql_providers.Search_Name', 'LIKE', "%{$search}%");
                $query->orWhere('mssql_facilities.FacilityName', 'LIKE', "%{$search}%");
                $query->orWhereRaw("CONCAT(mssql_providers.FirstName, ' ',
                IF(mssql_providers.MiddleName != '', CONCAT(mssql_providers.MiddleName, ' '), ''),
                mssql_providers.LastName) LIKE ?", ["%{$search}%"]);
                $query->orWhereRaw("CONCAT(mssql_patients.Patient_FirstName,' ',
                IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),
                mssql_patients.Patient_LastName) LIKE ?", ["%{$search}%"]);
            });
        }
        $bucket->where('mssql_providers.is_deleted', '0');
        if($isMeMode) { // +1
            $bucket->where($ccmIdExpr,'=',Auth::user()->id);
        }
        if(!empty($state)) { // +1
            $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
            $bucket->where(function($q) use ($facilities){
                foreach($facilities as $facility) { // +3
                    $q->orWhere("mssql_patients.aoc_facility_id","=",$facility->id);
                }
            });
        }

        if(!empty($branch)) { // +1
            $bucket->where(function($q) use ($branch){
                foreach($branch as $b) { // +3
                    $q->orWhere("mssql_patients.aoc_facility_id","=",$b);
                }
            });
        }

        if(!empty($region)) { // +1
            $bucket->where(function($q) use ($region){
                foreach($region as $b) { // +3
                    $q->orWhere("mssql_facilities.region","=",$b);
                }
            });
        }
        if(!empty($users)) { // +1
            $bucket->whereIn($ccmIdExpr,$users);
        }
        if(!empty($lob)) { // +1
            $bucket->where(function($q) use ($lob){
                foreach($lob as $b) { // +3
                    $q->orWhere("mssql_patients.LOBID","=",$b);
                }
            });
        }
        if(!empty($discipline)) { // +1
            $bucket->where(function($q) use ($discipline){
                foreach($discipline as $b) { // +3
                    $q->orWhere("mssql_providers.DisciplineName","=",$b);
                }
            });
        }
        if($showCount) { // +1
            return '('.$bucket->count().')';
        }
        if(!empty($startDate)) {
            $convertedStartDate = date('Y-m-d',strtotime($startDate));
            $bucket->whereDate("supervisory_visits.visit_date_due", ">=",$convertedStartDate);
        }

        if(!empty($endDate)) {
            $convertedEndDate = date('Y-m-d',strtotime($endDate));
            $bucket->whereDate("supervisory_visits.visit_date_due", "<=",$convertedEndDate);
        }
        if(!empty($clinicianStatus)){
            $bucket->where(function($q) use ($clinicianStatus){
                foreach($clinicianStatus as $c) {
                    $q->orWhere("mssql_employmentstatus.id","=",$c);
                }
            });
        }
        if(!empty($clientStatus)){
            $bucket->where(function($q) use ($clientStatus){
                foreach($clientStatus as $c) {
                    $q->orWhere("mssql_patients.Patient_CurrentStatusID","=",$c);
                }
            });
        }
        $bucket->active('supervisory_visits');
        if($viewType == 'showall') { // +1
            $showAllData['totalFiltered'] = $bucket->count();
            if($order == 'visit_date_due' || $order == 'last_sup_visit') {
                $bucket->offset($start)->limit($limit)
                ->orderBy(DB::raw('DATE('.$order.')'), $dir);
            } else{
                $bucket->offset($start)->limit($limit)->orderBy($order, $dir);
            }
            H::ExcelLog('List: ' . $bucket->toSql(), 'getSupervisoryVisits.log');
            $showAllData['data'] = $bucket->get();
            return $showAllData;
        }elseif($viewType == 'dashboard') {
            H::ExcelLog('List: ' . $bucket->toSql(), 'getSupervisoryVisitsBucket.log');
            return $bucket->get();
        }else{ // +1
            return $bucket->get();
        }
    }

    /**
     * @param $arg
     * @param $mode
     * @return void
     */
	public static function updateSupVisitNextDateFromDaas($arg, $mode): void
    {
        Log::channel('daasSupVisit')->info('Mode: ' . $mode);
        $arg = array_change_key_case($arg);

        Log::channel('daasSupVisit')->info('---Update SupVisit NextDate From Daas START---');
        Log::channel('daasSupVisit')->info("BillingInternalPatientID=".$arg['client_id']);
        Log::channel('daasSupVisit')->info("BillingProviderID=".$arg['supervisee_cgid']);

        try {

            $bucket = DB::table("supervisory_visits")
                ->select('supervisory_visits.*')
                ->leftJoin('mssql_providers', function ($query) {
                    $query->on('mssql_providers.id', '=', 'supervisory_visits.aoc_provider_id');
                })
                ->leftJoin('mssql_patients', function ($query) {
                    $query->on('mssql_patients.id', '=', 'supervisory_visits.aoc_patient_id');
                })->where('mssql_patients.BillingInternalPatientID', $arg['client_id'])
                ->where('mssql_providers.BillingProviderID', $arg['supervisee_cgid'])
                ->active('supervisory_visits')
                ->whereNull('supervisory_visits.completed_at')
                ->orderByRaw('COALESCE(supervisory_visits.form_sup_visit_date, supervisory_visits.last_visit) DESC')
                ->orderBy('supervisory_visits.id', 'desc')
                ->first();

            if(!empty($bucket)) {
                Log::channel('daasSupVisit')->info("Form Date=".$arg['form_date']);
                Log::channel('daasSupVisit')->info("Days=".$bucket->cycle);

                $visit_date_due = date('Y-m-d', strtotime($arg['form_date']."+ ".$bucket->cycle." days"));

                Log::channel('daasSupVisit')->info( " Old visit date due=".$bucket->visit_date_due);
                Log::channel('daasSupVisit')->info( " New visit date due=".$visit_date_due);

                DB::table('supervisory_visits')
                    ->where('id', $bucket->id)
                    ->active()
                    ->update([
                        'last_visit' => $arg['form_date'],
                        'visit_date_due' => $visit_date_due
                    ]);
            } else {
                Log::channel('daasSupVisit')
                    ->warning("No Record for=".$arg['client_id'] . " AND BillingProviderID=".$arg['supervisee_cgid']);
            }

            //Check completed records for form date match with KT date
            $completedBucket = DB::table('supervisory_visits')
                ->select('supervisory_visits.*')
                ->leftJoin('mssql_providers', function ($query) {
                    $query->on('mssql_providers.id', '=', 'supervisory_visits.aoc_provider_id');
                })
                ->leftJoin('mssql_patients', function ($query) {
                    $query->on('mssql_patients.id', '=', 'supervisory_visits.aoc_patient_id');
                })->where('mssql_patients.BillingInternalPatientID', $arg['client_id'])
                ->where('mssql_providers.BillingProviderID', $arg['supervisee_cgid'])
                ->active()
                ->whereNotNull('supervisory_visits.completed_at')
                ->orderBy('supervisory_visits.id', 'desc')->first();

            if(!empty($completedBucket)) {
                Log::channel('daasSupVisit')
                    ->info("Completed KT Form Master Date: ".$arg['form_date']);
                Log::channel('daasSupVisit')
                    ->info("Completed form_sup_visit_date: ".$completedBucket->form_sup_visit_date);

                if(strtotime($arg['form_date']) == strtotime($completedBucket->form_sup_visit_date)) {
                    DB::table('supervisory_visits')
                        ->where('id', $bucket->id)
                        ->active()
                        ->update(['visit_not_matched' => 0]);
                } else {
                    Log::channel('daasSupVisit')
                        ->info("KanTime form_date not matched with sup visit form date for: ".$arg['client_id'] .
                    " AND BillingProviderID=".$arg['supervisee_cgid']);
                }
            }
        } catch (\Throwable $th) {
            Log::channel('daasSupVisit')->error( "----Throwable----".$th->getMessage());
        }

        Log::channel('daasSupVisit')->info( "---Update SupVisit NextDate From Daas END---");
    }

    public function getSupervisoryVisitsForBucket($searchfilter = [], $showCount = 0, $request = '')
    {
        $search = '';$limit = '';$start = '';$order ='';$dir = '';
        $columns = H::getHelper('SupervisoryVisits')->getDataTableHtml('dashboard');
        if ($columns instanceof \Illuminate\View\View) {
            $columns = $columns->getData()['dataTable'];
        }
        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')]['name'];
        $dir = $request->input('order.0.dir');
        $bucket = DB::table("supervisory_visits");
        $ccmIdExpr = $this->getEffectiveAssignedToExpr();
        $bucket->leftJoin("mssql_providers",function($query){
            $query->on("mssql_providers.id","=","supervisory_visits.aoc_provider_id");
        });
        $bucket->leftJoin("mssql_patients",function($query){
            $query->on("mssql_patients.id","=","supervisory_visits.aoc_patient_id");
        });
        $bucket->leftJoin('users',function($query) use ($ccmIdExpr){
            $query->on('users.id','=',$ccmIdExpr);
        });
        $bucket->leftJoin("mssql_facilities", function($query){
            $query->on('mssql_patients.aoc_facility_id', '=', 'mssql_facilities.id');
        });
        $bucket->leftJoin("lob",function($query){
            $query->on("lob.LOBID","=","mssql_patients.LOBID");
        });
        $bucket->select('supervisory_visits.id',
        'supervisory_visits.aoc_provider_id',
        'supervisory_visits.aoc_patient_id',
        DB::raw("users.name AS ccmName"),
        DB::raw("CONCAT(mssql_providers.FirstName,' ',
        IF(mssql_providers.MiddleName != '',CONCAT(mssql_providers.MiddleName, ' '),''),
        mssql_providers.LastName) as clinicianName"),
        'mssql_providers.DisciplineName',
        DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',
        IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),
        mssql_patients.Patient_LastName) as clientName"),
        'lob.LOBName','mssql_facilities.FacilityName',
        DB::raw("supervisory_visits.last_visit AS last_sup_visit"),
        DB::raw("DATEDIFF(now(),supervisory_visits.last_visit)
        AS days_since_last_sup_visit"),
        DB::raw("DATE_FORMAT(supervisory_visits.visit_date_due, '%m/%d/%Y') as visit_date_due"),
        'supervisory_visits.completed_at',
        'supervisory_visits.completed_by',
        DB::raw("DATEDIFF(now(),supervisory_visits.visit_date_due)
        AS days_visit_due"),
        DB::raw("IF(supervisory_visits.visit_not_matched = 1, 'Sup visit not matched', '') AS visit_not_matched"),);
        $todayDate = date('Y-m-d');
        if(LD::checkFeatureFlag('client-clinician-status-columns-and-filters-supervisory-visit-tracking-bucket')
        ){
            $bucket->addSelect('mssql_employmentstatus.EmploymentStatus');
            $bucket->addSelect('mssql_patientstatus.PatientStatus as PatientStatus');
            $bucket->leftJoin("mssql_employmentstatus",
            "mssql_employmentstatus.id","=","mssql_providers.EmploymentStatusID");
            $bucket->leftJoin("mssql_patientstatus", function ($query) {
                $query->on("mssql_patients.Patient_CurrentStatusID", '=', 'mssql_patientstatus.id');
            });
        }
        $bucket->where(function ($query) use($todayDate){
            $query->where(function ($query){
                $query->whereNull('supervisory_visits.completed_at')
                ->whereRaw('supervisory_visits.visit_date_due <= DATE_ADD(now(),INTERVAL 60 DAY)');
            });
                $query->orWhere(function ($query) use($todayDate){
                    $query->where('supervisory_visits.completed_at', '<', $todayDate);
                    $query->whereNotNull('supervisory_visits.form_sup_visit_date');

                    $query->whereRaw('supervisory_visits.visit_date_due >= DATE_SUB(now(), INTERVAL 90 DAY)');

                    $query->where(DB::raw("ifnull((SELECT Date_Format(sm.form_date,'%m/%d/%Y') as date
                    FROM supervisory_form_master sm
                    WHERE sm.client_id = mssql_patients.BillingInternalPatientID
                    and sm.supervisee_cgid = mssql_providers.BillingProviderID
                    and Date_Format(sm.form_date,'%m/%d/%Y') = Date_Format(supervisory_visits.form_sup_visit_date,'%m/%d/%Y') limit 1),
                    '2000-01-01')"),
                    '!=',DB::raw("Date_Format(supervisory_visits.form_sup_visit_date,'%m/%d/%Y')"));
                });
        });
        // Filter Section
        $isMeMode = 0;
        $state = $region = $branch = $users = $discipline = $lob=$clinicianStatus=$clientStatus=[];
        $startDate = $endDate ='';
        if(!empty($searchfilter)) { // +1
            foreach($searchfilter as $filter) { // +2
                $filterName = $filter['name'];
                $filterValue = $filter['value'];

                if($filterName=='svdefault') { // +3
                    $isMeMode =1;
                }
                if($filterName=='svstate') { // +3
                    $state[] =$filterValue;
                }
                if($filterName=='svregion') { // +3
                    $region[] =$filterValue;
                }
                if($filterName=='svbranch') { // +3
                    $branch[] =$filterValue;
                }
                if($filterName=='svclinicalcaremanager'){ // +3
                    $users[] = $filterValue;
                }
                if($filterName=='discipline') { // +3
                    $discipline[] =$filterValue;
                }
                if($filterName=='LOBID') { // +3
                    $lob[] =$filterValue;
                }
                if($filterName=='fromvisit_date_due' && !empty($filterValue)) {
                    $startDate = $filterValue;
                }
                if($filterName=='tovisit_date_due' && !empty($filterValue)) {
                    $endDate = $filterValue;
                }
                if($filterName=='svClinicianStatus') {
                    $clinicianStatus[] =$filterValue;
                }
                if($filterName=='svPatient_CurrentStatusID') {
                    $clientStatus[] =$filterValue;
                }
            }
        }
        $search = $request->input('search.value');
        if (!empty($search)) { // +1
            $bucket->where(function ($query) use ($search){
                $query->where('mssql_providers.Search_Name', 'LIKE', "%{$search}%");
                $query->orWhere('mssql_facilities.FacilityName', 'LIKE', "%{$search}%");
                $query->orWhereRaw("CONCAT(mssql_providers.FirstName, ' ',
                IF(mssql_providers.MiddleName != '', CONCAT(mssql_providers.MiddleName, ' '), ''),
                mssql_providers.LastName) LIKE ?", ["%{$search}%"]);
                $query->orWhereRaw("CONCAT(mssql_patients.Patient_FirstName,' ',
                IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),
                mssql_patients.Patient_LastName) LIKE ?", ["%{$search}%"]);
            });
        }
        $bucket->where('mssql_providers.is_deleted', '0');
        if($isMeMode) { // +1
            $bucket->where($ccmIdExpr,'=',Auth::user()->id);
        }
        if (!empty($state)) { // +1
            $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
            $facilityIds = $facilities->pluck('id')->toArray(); // Corrected method

            if (!empty($facilityIds)) {
                $bucket->whereIn("mssql_patients.aoc_facility_id", $facilityIds);
            }
        }

        if (!empty($branch)) { // +1
            $bucket->whereIn("mssql_patients.aoc_facility_id", $branch);
        }

        if (!empty($region)) { // +1
            $bucket->whereIn("mssql_facilities.region", $region);
        }
        if(!empty($users)) { // +1
            $bucket->whereIn($ccmIdExpr,$users);
        }
        if (!empty($lob)) { // +1
            $bucket->whereIn("mssql_patients.LOBID", $lob);
        }
        if (!empty($discipline)) { // +1
            $bucket->whereIn("mssql_providers.DisciplineName", $discipline);
        }
        if($showCount) { // +1
            return '('.$bucket->count().')';
        }
        if(!empty($startDate)) {
            $convertedStartDate = date('Y-m-d',strtotime($startDate));
            $bucket->whereDate("supervisory_visits.visit_date_due", ">=",$convertedStartDate);
        }

        if(!empty($endDate)) {
            $convertedEndDate = date('Y-m-d',strtotime($endDate));
            $bucket->whereDate("supervisory_visits.visit_date_due", "<=",$convertedEndDate);
        }
        if (!empty($clinicianStatus)) {
            $bucket->whereIn("mssql_employmentstatus.id", $clinicianStatus);
        }
        if (!empty($clientStatus)) {
            $bucket->whereIn("mssql_patients.Patient_CurrentStatusID", $clientStatus);
        }
        $bucket->active('supervisory_visits');

        $showAllData['totalData'] = $bucket->count();
        $showAllData['totalFiltered'] = $showAllData['totalData'];
        if($order == 'visit_date_due' || $order == 'last_sup_visit') {
            $bucket->offset($start)->limit($limit)
            ->orderBy(DB::raw('DATE('.$order.')'), $dir);
        } else{
            $bucket->offset($start)->limit($limit)->orderBy($order, $dir);
        }
        H::ExcelLog('List: ' . $bucket->toSql(), 'getSupervisoryVisits.log');
        $showAllData['data'] = $bucket->get();
        return $showAllData;
    }

}
