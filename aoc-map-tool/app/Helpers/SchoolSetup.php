<?php
namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\LD;

class SchoolSetup
{
    public function getShowAllGridColumns()
    {
        if(LD::checkFeatureFlag('add-requested-date-column-to-school-set-ups-buckets')){
            $columns = [
                [
                    'name' => 'Client_Coordinator',
                    'label' => 'Client Care Coordinator',
                    'width' => '12%'
                ],
                [
                    'name' => 'FacilityName',
                    'label' => 'Branch Location',
                    'width' => '12%'
                ],
                [
                    'name' => 'clinicianName',
                    'label' => 'Clinician',
                    'width' => '12%'
                ],
                [
                    'name' => 'clientName',
                    'label' => 'Client',
                    'width' => '10%'
                ],
                [
                    'name' => 'SchoolName',
                    'label' => 'School Name',
                    'width' => '12%'
                ],
                [
                    'name' => 'SchoolDistrict',
                    'label' => 'School District',
                    'width' => '12%'
                ],
                [
                    'name' => 'requested_date',
                    'label' => 'School Set Up Requested Date',
                    'width' => '10%',
                ],
                [
                    'name' => 'start_date',
                    'label' => 'School Set Up Due Date',
                    'width' => '10%',
                ],
                [
                    'name' => 'status',
                    'label' => 'School Set Up Status',
                    'width' => '13%'
                ]
            ];
        }else{
            $columns = [
                [
                    'name' => 'Client_Coordinator',
                    'label' => 'Client Care Coordinator',
                    'width' => '12%'
                ],
                [
                    'name' => 'FacilityName',
                    'label' => 'Branch Location',
                    'width' => '12%'
                ],
                [
                    'name' => 'clinicianName',
                    'label' => 'Clinician',
                    'width' => '12%'
                ],
                [
                    'name' => 'clientName',
                    'label' => 'Client',
                    'width' => '10%'
                ],
                [
                    'name' => 'SchoolName',
                    'label' => 'School Name',
                    'width' => '12%'
                ],
                [
                    'name' => 'SchoolDistrict',
                    'label' => 'School District',
                    'width' => '12%'
                ],
                [
                    'name' => 'start_date',
                    'label' => 'Start Date',
                    'width' => '10%',
                ],
                [
                    'name' => 'status',
                    'label' => 'School Set Up Status',
                    'width' => '13%'
                ]
            ];
        }


        return $columns;
    }
    public function getOpenSchoolRequestBucketColumn()
    {
        if(LD::checkFeatureFlag('add-requested-date-column-to-school-set-ups-buckets')){
            return ['select'=>'select','clientName'=>'Client',
            'FacilityName'=>'Branch',
            'Client_Coordinator'=>'Client Care Coordinator',
            'clinicianName'=>'Clinician',
            'SchoolName'=>'School Name',
            'SchoolDistrict'=>'School District',
            'requested_date'=>'School Set Up Requested Date',
            'start_date'=>'School Set Up Due Date',
            'live_status'=>'Status'];
        }else{
            return ['select'=>'select','clientName'=>'Client',
            'FacilityName'=>'Branch',
            'Client_Coordinator'=>'Client Care Coordinator',
            'clinicianName'=>'Clinician',
            'SchoolName'=>'School Name',
            'SchoolDistrict'=>'School District',
            'start_date'=>'Start Date',
            'live_status'=>'Status'];
        }
    }
    public function getDataTableHtml($type,$form='')
    {
        $clientCareCoordinator = 'Client Care Coordinator';
        $dataTable = [];
        $viewName = 'schoolSetUp.schoolSetup_datatable';
        switch($type) {
            case 'ccc_client_need_school_setup':
                $dataTable = ['Client_Coordinator'=>$clientCareCoordinator,
                'clientName'=>'Client',
                'clinicianName'=>'Clinician',
                'FacilityName'=>'Branch'];
                break;
            case 'ccc_pending_school_setups_request':

                    if(LD::checkFeatureFlag('add-requested-date-column-to-school-set-ups-buckets')){
                        $dataTable = [
                            'Client_Coordinator'=>$clientCareCoordinator,
                            'FacilityName'=>'Branch Location',
                            'clinicianName'=>'Clinician',
                            'clientName'=>'Client',
                            'requested_date'=>'School Set Up Requested Date',
                            'start_date'=>'School Set Up Due Date',
                            'live_status'=>'School Set Up Status'
                        ];
                    }else{
                        $dataTable = [
                            'Client_Coordinator'=>$clientCareCoordinator,
                            'FacilityName'=>'Branch Location',
                            'clinicianName'=>'Clinician',
                            'clientName'=>'Client',
                            'start_date'=>'School Set Up Due Date',
                            'live_status'=>'School Set Up Status'
                        ];
                    }
                    break;
            case 'open_school_setup_requests':
                $dataTable = H::getHelper('SchoolSetup')->getOpenSchoolRequestBucketColumn();
                $viewName = 'schoolSetUp.schoolSetupRequestDashboard_datatable';
                break;
            default:
            $dataTable = ['patient_name'=>'Client',
            'FacilityName'=>'Branch',
            'Client_Coordinator'=>$clientCareCoordinator];
                break;
        }
        return view($viewName,["type"=>$type,'dataTable'=>$dataTable,"form"=>$form]);
    }

    public function getClientSchoolSetup($request, $showCount=0) {
        $search = $request->input('search.value');
        $clientName = "CONCAT(mssql_patients.Patient_FirstName,' ',
        IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),
        mssql_patients.Patient_LastName)";
        $bucket = DB::table('mssql_patients')->select(
            'mssql_patients.id',
            DB::raw($clientName." AS clientName"),
            DB::raw("users.name AS
            Client_Coordinator"),
            'mssql_facilities.FacilityName',
            DB::raw("(select CONCAT(
                GROUP_CONCAT(
                    distinct CONCAT(mssql_providers.FirstName,' ',
                    IF(mssql_providers.MiddleName != '',
                    CONCAT(mssql_providers.MiddleName, ' '),''),
                    mssql_providers.LastName)
                )
            )
            from mssql_schedulemaster sm
            left join mssql_providers on sm.ProviderID = mssql_providers.ProviderID
            where sm.PatientID = mssql_patients.PatientID
            and sm.ScheduleDate >= '".date('Y-m-d')."') as clinicianName"),
        )
        ->leftJoin('mssql_facilities', 'mssql_patients.aoc_facility_id', '=', 'mssql_facilities.id')
        ->leftJoin('users', 'mssql_patients.ccc_id' , 'users.id');

        if (!empty($search)) {
            $bucket->where(function ($query) use ($search,$clientName){
                $query->whereRaw($clientName." LIKE ?", ["%{$search}%"]);
                $query->orWhere('users.name', 'LIKE', "%{$search}%");
            });
        }

        $bucket->where('mssql_patients.needs_school_set_up', '1');
		if (LD::checkFeatureFlag('client-new-changes')) {
            $bucket->where('mssql_patients.IsLatestAdmit', 1);
        }

        $state = $region = $branch = $ccc = $payer=[];
        $isMeMode = 0;

        if(isset($request['filters'])) {

            foreach($request['filters'] as $value){

                if($value['name'] == 'ccc_school_setup_state') {
                    $state[] = $value['value'];
                }
                if($value['name'] == 'ccc_school_setup_region') {
                    $region[] = $value['value'];
                }
                if($value['name'] == 'ccc_school_setup_aoc_facility_id') {
                    $branch[] = $value['value'];
                }
                if($value['name'] == 'ccc_school_setup_client_care_coordinator'){
                    $ccc[] = $value['value'];
                }
                if($value['name'] == 'ccc_school_setup_default'){
                    $isMeMode = 1;
                }
                if($value['name']=='ccc_school_setup_payer' && $value['value'] != ''){
                    $payer[] = $value['value'];
                }
                if( $value['name']=='ccc_school_setup_payer_group'  && $value['value'] != ''){
                    $payerGroup[] = $value['value'];
                }
            }
        }

        if(!empty($state)) { // +1
            $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
            $bucket->where(function($bucket) use ($facilities){
                foreach($facilities as $facility) { // +3
                    $bucket->orWhere("mssql_patients.aoc_facility_id","=",$facility->id);
                }
            });
        }

        if(!empty($branch)) { // +1
            $bucket->where(function($bucket) use ($branch){
                foreach($branch as $b) { // +3
                    $bucket->orWhere("mssql_patients.aoc_facility_id","=",$b);
                }
            });
        }

        if(!empty($region)) { // +1
            $bucket->where(function($bucket) use ($region){
                foreach($region as $b) { // +3
                    $bucket->orWhere("mssql_facilities.region","=",$b);
                }
            });
        }
        if(!empty($payer)){
            $bucket->where(function($q) use ($payer){
                foreach($payer as $t) {
                    $q->orWhere("mssql_patients.Payer","=",$t);
                }
            });
        }

        if(!empty($payerGroup)){
            $bucket->where(function($q) use ($payerGroup){
                foreach($payerGroup as $t) {
                    $q->orWhere("mssql_patients.payer_group","=",$t);
                }
            });
        }
        if(!empty($ccc) && !$isMeMode){
            $bucket->whereIn("mssql_patients.ccc_id", $ccc);
        } elseif($isMeMode) {
            $bucket->where("mssql_patients.ccc_id", Auth::user()->id);
        }
        H::ExcelLog('List: ' . $bucket->toSql(), 'getClientSchoolSetup.log');
        if($showCount==1) {
            return '('.$bucket->count().')';
        } else {
            return $bucket->get();
        }
    }

    public function getPendingSchoolSetupRequestForCcDashboard($request, $showCount=0) {
        $search = $request->input('search.value');
        $clinicianName = "(CASE WHEN school_setup_requests.tracker_id > 0
        THEN (SELECT Candidate
        FROM mssql_recruitertracker
        WHERE mssql_recruitertracker.id=school_setup_requests.tracker_id)
        WHEN school_setup_requests.aoc_provider_id > 0
        THEN (SELECT mssql_providers.fullName
        FROM mssql_providers
        WHERE mssql_providers.id=school_setup_requests.aoc_provider_id)
        ELSE '' END)";

        $bucket = DB::table('school_setup_requests')->select(
            'school_setup_requests.id',
            'school_setup_requests.aoc_patient_id',
            'school_setup_requests.live_status',
            'school_setup_requests.aoc_provider_id',
            DB::raw('IF(
                (
                    school_setup_requests.start_date IS NULL or
                    school_setup_requests.start_date = \'\' or
                    school_setup_requests.start_date = \'0000-00-00\'), \'\',
                    DATE_FORMAT(school_setup_requests.start_date,"'.config('constant')['mysqlsearchdateformat'].'")
                )
            as start_date'),
            'school_setup_requests.status',
            DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',
            IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),
            mssql_patients.Patient_LastName) AS clientName"),
            DB::raw($clinicianName." as clinicianName"),
            DB::raw("users.name AS Client_Coordinator"),
            'mssql_facilities.FacilityName',
        )
        ->leftJoin('mssql_patients', 'school_setup_requests.aoc_patient_id', '=', 'mssql_patients.id')
        ->leftJoin('mssql_facilities', 'mssql_patients.aoc_facility_id', '=', 'mssql_facilities.id')
        ->leftJoin('users', 'school_setup_requests.assigned_ccc' , 'users.id');
        if(LD::checkFeatureFlag('school-set-up-status-column-default-value')){
            $bucket->addselect(DB::raw("IFNULL(live_status, 'Not Started') as live_status"));
        }
        if(LD::checkFeatureFlag('add-requested-date-column-to-school-set-ups-buckets')){
            $bucket->addSelect(DB::raw('IF(
                    (
                        school_setup_requests.created_at IS NULL or
                        school_setup_requests.created_at = \'\' or
                        school_setup_requests.created_at = \'0000-00-00\'), \'\',
                        DATE_FORMAT(school_setup_requests.created_at,"'.config('constant')['mysqlsearchdateformat'].'")
                    )
                as requested_date'));
        }
        if (!empty($search)) {
            $bucket->where(function ($query) use ($search,$clinicianName){
                $query->whereRaw("CONCAT(mssql_patients.Patient_FirstName,' ',
                IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),
                mssql_patients.Patient_LastName) LIKE ?", ["%{$search}%"]);
                $query->orWhere('users.name', 'LIKE', "%{$search}%");
                $query->orWhere(DB::raw($clinicianName), 'LIKE', "%{$search}%");
            });
        }
        $bucket->where('school_setup_requests.ccc_acknowledged',0);
        $bucket->where('school_setup_requests.is_deleted', '0');
        if(LD::checkFeatureFlag('new-ssu-form-change')){
            $bucket->where(function ($query) {
                $query->whereNull('school_setup_requests.ssu_marked')
                      ->orWhere('school_setup_requests.ssu_marked', '!=', 1);
            });
        }
        if(LD::checkFeatureFlag('add-save-button-in-ssu-form-from-bucket')){
            $bucket->where('school_setup_requests.save_flag', '<>', '1');
        }
        $state = $region = $branch = $ccc = $payer=[];
        $isMeMode = 0;

        if(isset($request['filters'])) {
            foreach($request['filters'] as $value){
                if($value['name'] == 'ccc_pending_school_setup_request_state') {
                    $state[] = $value['value'];
                }
                if($value['name'] == 'ccc_pending_school_setup_request_region') {
                    $region[] = $value['value'];
                }
                if($value['name'] == 'ccc_pending_school_setup_request_aoc_facility_id') {
                    $branch[] = $value['value'];
                }
                if($value['name'] == 'ccc_pending_school_setup_request_client_care_coordinator'){
                    $ccc[] = $value['value'];
                }
                if($value['name'] == 'ccc_pending_school_setup_request_default'){
                    $isMeMode = 1;
                }
                if($value['name']=='ccc_pending_school_setup_request_payer' && $value['value'] != ''){
                    $payer[] = $value['value'];
                }
                if( $value['name']=='ccc_pending_school_setup_request_payer_group'  && $value['value'] != ''){
                    $payerGroup[] = $value['value'];
                }
            }
        }

        if(!empty($state)) { // +1
            $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
            $bucket->where(function($bucket) use ($facilities){
                foreach($facilities as $facility) { // +3
                    $bucket->orWhere("mssql_patients.aoc_facility_id","=",$facility->id);
                }
            });
        }

        if(!empty($branch)) { // +1
            $bucket->where(function($bucket) use ($branch){
                foreach($branch as $b) { // +3
                    $bucket->orWhere("mssql_patients.aoc_facility_id","=",$b);
                }
            });
        }

        if(!empty($region)) { // +1
            $bucket->where(function($bucket) use ($region){
                foreach($region as $b) { // +3
                    $bucket->orWhere("mssql_facilities.region","=",$b);
                }
            });
        }
        if(!empty($payer)){
            $bucket->where(function($q) use ($payer){
                foreach($payer as $t) {
                    $q->orWhere("mssql_patients.Payer","=",$t);
                }
            });
        }

        if(!empty($payerGroup)){
            $bucket->where(function($q) use ($payerGroup){
                foreach($payerGroup as $t) {
                    $q->orWhere("mssql_patients.payer_group","=",$t);
                }
            });
        }
        if(!empty($ccc) && !$isMeMode){
            $bucket->whereIn("school_setup_requests.assigned_ccc", $ccc);
        } elseif($isMeMode) {
            $bucket->where("school_setup_requests.assigned_ccc", Auth::user()->id);
        }
        H::ExcelLog('List: ' . $bucket->toSql(), 'getPendingSchoolSetupRequestForCcDashboard.log');
        if($showCount==1) {
            return '('.$bucket->count().')';
        } else {
            return $bucket->get();
        }
    }

    public function getSchoolSetUpRequest($viewType="dashboard",$searchfilter=[],$showCount=0,$request='') {
        $search = $request->input('search.value');
        $clinicianName = "(CASE WHEN school_setup_requests.tracker_id > 0
        THEN (SELECT Candidate
        FROM mssql_recruitertracker
        WHERE mssql_recruitertracker.id=school_setup_requests.tracker_id)
        WHEN school_setup_requests.aoc_provider_id > 0
        THEN (SELECT mssql_providers.fullName
        FROM mssql_providers
        WHERE mssql_providers.id=school_setup_requests.aoc_provider_id)
        ELSE '' END)";

        $bucket = DB::table('school_setup_requests')->select(
            'school_setup_requests.id as school_setup_id',
            'school_setup_requests.aoc_patient_id',
            'school_setup_requests.aoc_provider_id',
            'school_setup_requests.school_name as SchoolName',
            'school_setup_requests.school_district as SchoolDistrict',
            'school_setup_requests.live_status',
            DB::raw('IF(
                (
                    school_setup_requests.start_date IS NULL or
                    school_setup_requests.start_date = \'\' or
                    school_setup_requests.start_date = \'0000-00-00\'), \'\',
                    DATE_FORMAT(school_setup_requests.start_date,"'.config('constant')['mysqlsearchdateformat'].'")
                )
            as start_date'),
            'school_setup_requests.status',
            'mssql_patients.id',
            DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',
            IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),
            mssql_patients.Patient_LastName) AS clientName"),
            DB::raw($clinicianName." as clinicianName"),
            DB::raw("users.name AS Client_Coordinator"),
            'mssql_facilities.FacilityName',
        )
        ->leftJoin('mssql_patients', 'school_setup_requests.aoc_patient_id', '=', 'mssql_patients.id')
        ->leftJoin('mssql_facilities', 'mssql_patients.aoc_facility_id', '=', 'mssql_facilities.id')
        ->leftJoin('users', 'school_setup_requests.assigned_ccc' , 'users.id');
        if(LD::checkFeatureFlag('school-set-up-status-column-default-value')){
            $bucket->addselect(DB::raw("IFNULL(live_status, 'Not Started') as live_status"));
        }
        if(LD::checkFeatureFlag('add-requested-date-column-to-school-set-ups-buckets')){
            $bucket->addSelect(DB::raw('IF(
                    (
                        school_setup_requests.created_at IS NULL or
                        school_setup_requests.created_at = \'\' or
                        school_setup_requests.created_at = \'0000-00-00\'), \'\',
                        DATE_FORMAT(school_setup_requests.created_at,"'.config('constant')['mysqlsearchdateformat'].'")
                    )
                as requested_date'));
        }
        if (!empty($search)) {
            $bucket->where(function ($query) use ($search,$clinicianName){
                $query->whereRaw("CONCAT(mssql_patients.Patient_FirstName,' ',
                IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),
                mssql_patients.Patient_LastName) LIKE ?", ["%{$search}%"]);
                $query->orWhere('users.name', 'LIKE', "%{$search}%");
                $query->orWhere(DB::raw($clinicianName), 'LIKE', "%{$search}%");
                $query->orWhere('school_setup_requests.school_name', 'LIKE', "%{$search}%");
                $query->orWhere('school_setup_requests.school_district', 'LIKE', "%{$search}%");
            });
        }
        if(LD::checkFeatureFlag('add-save-button-in-ssu-form-from-bucket')){
            $bucket->where('school_setup_requests.save_flag', '<>', '1');
        }
        $state = $region = $branch = $ccc = $payer=$schoolSatus=[];
        $showOnlyOpenRequest = 1;
        $isMeMode = 0;
        if(!empty($searchfilter)) { // +1
            foreach($searchfilter as $filter) {
                $filterName = $filter['name'];
                $filterValue = $filter['value'];
                if($filterName == 'open_school_setup_requests_memode') {
                    $isMeMode = 1;
                }
                if($filterName == 'open_school_setup_requests_state') {
                    $state[] = $filterValue;
                }
                if($filterName == 'open_school_setup_requests_region') {
                    $region[] = $filterValue;
                }
                if($filterName == 'open_school_setup_requests_aoc_facility_id') {
                    $branch[] = $filterValue;
                }
                if($filterName == 'open_school_setup_requests_client_care_coordinator'){
                    $ccc[] = $filterValue;
                }
                if ($filterName == 'open_school_setup_request_include_completed') {
                    $showOnlyOpenRequest = 0;
                }
                if($filterName=='open_school_setup_requests_payer' && $filterValue != ''){
                    $payer[] = $filterValue;
                }
                if( $filterName=='open_school_setup_requests_payer_group'  && $filterValue != ''){
                    $payerGroup[] = $filterValue;
                }
                if(LD::checkFeatureFlag('add-status-filter-on-school-set-up-show-all-page')){
                    if( $filterName=='school_Set_Up_Status'  && $filterValue != ''){
                        $schoolSatus[] = $filterValue;
                    }
                }
            }
        }

        if ($showOnlyOpenRequest || $viewType == 'dashboard') {
            $bucket->whereIn('school_setup_requests.status', ['Open','Started']);
        }

        if (!empty($state)) {
            $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
            $facilities = array_map(function ($ar) {return $ar['id'];}, $facilities->toArray());
            $bucket->whereIn("mssql_patients.aoc_facility_id", $facilities);
        }

        if (!empty($branch)) {
            $bucket->whereIn("mssql_patients.aoc_facility_id", $branch);
        }

        if (!empty($region)) {
            $bucket->whereIn("mssql_facilities.region", $region);
        }

        if (!empty($payer)) {
            $bucket->whereIn("mssql_patients.Payer", $payer);
        }

        if (!empty($payerGroup)) {
            $bucket->whereIn("mssql_patients.payer_group", $payerGroup);
        }

        if(!empty($schoolSatus)){
            $bucket->whereIn("school_setup_requests.status", $schoolSatus);
        }

        if(!empty($ccc) && !$isMeMode){
            $bucket->whereIn("school_setup_requests.assigned_ccc", $ccc);
        } elseif($isMeMode) {
            $bucket->where("school_setup_requests.assigned_ccc", Auth::user()->id);
        }

        $bucket->where('school_setup_requests.is_deleted', '0');

        H::ExcelLog('List: ' . $bucket->toSql(), 'getSchoolSetUpRequest.log');
        if($showCount==1) {
            return '('.$bucket->count().')';
        } else {
            $showAllData = [];
            $showAllData['totalData'] = $bucket->count();
            $showAllData['totalFiltered'] = $showAllData['totalData'];
            if ($viewType == 'export') {
                $showAllData['data'] = $bucket->get();
            } else {
                if ($viewType == 'dashboard') {
                    $columnsArr = H::getHelper('SchoolSetup')->getOpenSchoolRequestBucketColumn();
                    $columns = array_keys($columnsArr);
                    $order = $columns[$request->input('order.0.column')];
                } else {
                    $columns = $this->getShowAllGridColumns();
                    $order = $columns[$request->input('order.0.column')]['name'];
                }
                $limit = $request->input('length');
                $start = $request->input('start');
                $dir = $request->input('order.0.dir');
                H::ExcelLog('Order: ' . $order, 'getSchoolSetUpRequest.log');
                if($order == 'start_date') {
                    $order = DB::raw('DATE('.$order.')');
                } elseif($order == 'Client_Coordinator') {
                    $order = 'users.name';
                }
                $showAllData['data'] = $bucket->offset($start)->limit($limit)->orderBy($order, $dir)->get();
                H::ExcelLog('Final Query: ' . $bucket->toSql(), 'getSchoolSetUpRequest.log');
            }
            return $showAllData;
        }
    }
}
