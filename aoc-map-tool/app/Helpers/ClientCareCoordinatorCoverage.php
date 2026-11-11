<?php
namespace App\Helpers;

use App\Enums\DisciplineEnum;
use App\Enums\MssqlRecreturTrackerStatus;
use App\Enums\MssqlRecruiterTrackerActiveCoverageOutcome;
use App\Enums\MssqlRecruiterTrackerLiveIpeaStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\LD;

class ClientCareCoordinatorCoverage
{
    public $buckets = [
        "text_call_client_schedule_regulars"=>["heading"=>"Text and Call Client Schedule Regulars"],
        "text_call_record_clinicians"=>["heading"=>"Text and Call Record Clinicians"],
        "text_call_prn_clinicians_from_local_prn_schedule"=>["heading"=>"Text and Call PRN Clinicians from Local PRN Schedule"],
        "text_blast_180_mile_radius_from_text_blast_map"=>["heading"=>"Text Blast for 180-Mile Radius from Text Blast Map"],
        "call_nurses_from_text_blast_map"=>["heading"=>"Call Nurses from the Text Blast Map"],
        "check_cover_nurses_availability"=>["heading"=>"Check Cover Nurses Availability"],
        "escalated"=>["heading"=>"Escalated"]
    ];


    public function getBuckets(){
        return $this->buckets;
    }

    public function getBucketArray() {
        return [
            "text_call_client_schedule_regulars" => "Text and Call Client Schedule Regulars",
            "text_call_record_clinicians" => "Text and Call Record Clinicians",
            "text_call_prn_clinicians_from_local_prn_schedule" => "Text and Call PRN Clinicians from Local PRN Schedule",
            "text_blast_180_mile_radius_from_text_blast_map" => "Text Blast for 180-Mile Radius from Text Blast Map",
            "call_nurses_from_text_blast_map" => "Call Nurses from the Text Blast Map",
            "check_cover_nurses_availability" => "Check Cover Nurses Availability",
            "escalated" => "Escalated"
        ];
    }
    public function getBucketTitle($type) {
        return $this->buckets[$type]['heading'];
    }

    public function getDataTableHtml($type)
    {
        $dataTable = [];
        switch($type) {
            case 'escalated':
                if(LD::checkFeatureFlag('add-to-tracker-and-update-selected-button-added-to-escalated-bucket')){
                    $dataTable = ['select'=>'select','patient_name'=>'Client Name','Client_Coordinator'=>"Client Care Coordinator",'IsPriority'=>'Priority','shift_date'=>'Date','Schedule_Time'=>'Schedule','Hours'=>"Hours",'Service'=>"Service",'Discipline'=>"Discipline",'Payer'=>"Payer",'payer_group'=>"Payer Group"];
                }else{
                    $dataTable = ['patient_name'=>'Client Name','Client_Coordinator'=>"Client Care Coordinator",'IsPriority'=>'Priority','shift_date'=>'Date','Schedule_Time'=>'Schedule','Hours'=>"Hours",'Service'=>"Service",'Discipline'=>"Discipline",'Payer'=>"Payer",'payer_group'=>"Payer Group"];
                }
                break;
            default:
                $dataTable = ['select'=>'select','patient_name'=>'Client Name','Client_Coordinator'=>"Client Care Coordinator",'IsPriority'=>'Priority','shift_date'=>'Date','Schedule_Time'=>'Schedule','Hours'=>"Hours",'Service'=>"Service",'Discipline'=>"Discipline",'Payer'=>"Payer",'payer_group'=>"Payer Group"];
                break;
        }
        if(LD::checkFeatureFlag("add-intake-coverage-tracker-widget-cc-dashboard")) {
            $pos = array_search("Service",array_keys($dataTable));
            $pos = $pos+1;
            $insert = ['intake'=>"Intake"];
            $dataTable = array_merge(
                array_slice($dataTable, 0, $pos),
                $insert,
                array_slice($dataTable, $pos)
            );
        }
        return view('ccc.coverage_tracker_datatable',["type"=>$type,'dataTable'=>$dataTable]);
    }

    public function getClientCareCoverageTracker($type, $request = '',$showCount=0) {
        $bucket = DB::table("maps_coverageneeds");
        $bucket->select('maps_coverageneeds.*','mssql_patients.PatientAccount', 'users.id as userid',
        DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"),
        DB::raw("(maps_coverageneeds.Hours) AS Total_Hours"),
        DB::raw("users.name AS Client_Coordinator"),
        DB::raw('DATE_FORMAT(maps_coverageneeds.shift_date,"%m/%d/%Y") as shift_date'),"mssql_patients.payer_group",DB::raw("'No' as IsPriority"));
        $bucket->leftJoin("mssql_patients",function($query){
            $query->on("mssql_patients.id","=","maps_coverageneeds.aoc_patient_id");
        });
        $bucket->leftJoin("mssql_facilities", function($query){
            $query->on("mssql_facilities.id","=","mssql_patients.aoc_facility_id");
        });
        $branch = [];
        $state = [];
        $users = [];
        $payer = [];
        $payerGroup=[];
        $region = [];
        $startDate = $endDate = $intake ='';
        if(isset($request['filters'])) {
            foreach($request['filters'] as $key => $value){
                if($value['name']=='coveragetrackerstate') {
                    $state[] = $value['value'];
                }
                if($value['name']=='coveragetrackerbranch') {
                    $branch[] = $value['value'];
                }
                if($value['name']=='coveragetrackerclientcare'){
                    $users[] = $value['value'];
                }
                if( $value['name']=='coveragetrackerpayer'){
                    $payer[] = $value['value'];
                }
                if($value['name']=='coveragetrackerregion'){
                    $region[] = $value['value'];
                }
                if( $value['name']=='coveragetrackerpayer_group'){
                    $payerGroup[] = $value['value'];
                }
                if($value['name']=='coveragetracker_from_date') {
                    if(!empty($value['value'])) {
                        $startDate = $value['value'];
                    }
                }
                if($value['name']=='coveragetracker_to_date') {
                    if(!empty($value['value'])) {
                        $endDate = $value['value'];
                    }
                }
                if($value['name']=='coveragetrackerintake'){
                    $intake = $value['value'];
                }
            }
        }

        if(count($state)>0) {
            $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
            $bucket->where(function($q) use ($facilities){
                foreach($facilities as $facility) {
                    $q->orWhere("mssql_patients.aoc_facility_id","=",$facility->id);
                }
            });
        }
        if(count($branch)>0) {
            $bucket->where(function($q) use ($branch){
                foreach($branch as $b) {
                    $q->orWhere("mssql_patients.aoc_facility_id","=",$b);
                }
            });
        }
        if(LD::checkFeatureFlag("region-filter-for-all-dashboard")) {
            if(!empty($region)){
                $bucket->where(function($query) use ($region) {
                    foreach($region as $val) {
                        $query->orWhere("mssql_facilities.region",'=',$val);
                    }
                });
            }
        }
        if(!empty($users)){
            $bucket->whereIn("mssql_patients.ccc_id",$users);
        } else {
            if (isset($request['filters']) && $request['filters'][0]['name'] == 'default') {
                $bucket->where("mssql_patients.ccc_id",'=', Auth::user()->id);
            }
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
        if(!empty($startDate)) {
            $convertedStartDate = date('Y-m-d',strtotime($startDate));
            H::ExcelLog('convertedStartDate:'.$convertedStartDate,'getClientCareCoverageTracker.log');
            $bucket->whereDate("maps_coverageneeds.shift_date", ">=",$convertedStartDate);
        }

        if(!empty($endDate)) {
            $convertedEndDate = date('Y-m-d',strtotime($endDate));
            H::ExcelLog('convertedEndDate:'.$convertedEndDate,'getClientCareCoverageTracker.log');
            $bucket->whereDate("maps_coverageneeds.shift_date", "<=",$convertedEndDate);
        }
        if(!empty($intake)){
            $bucket->where("maps_coverageneeds.intake",$intake);
        }
        $bucket->leftJoin("users",function($query){
            $query->on("users.id","=","mssql_patients.ccc_id");
        });
        $bucket->whereRaw("ifnull(maps_coverageneeds.last_activity,'text_call_client_schedule_regulars') = ?", [$type]);

        H::ExcelLog($bucket->toSql(),'getClientCareCoverageTracker.log');
        if($showCount) {
            $totalCountData = $bucket->pluck('Total_Hours')->all();
            $totalHr =  number_format(array_sum($totalCountData), (fmod(array_sum($totalCountData),1) !== 0.00) ? 1 : 0);
            return '('.$bucket->count().' | ' . $totalHr . ' <span style="text-transform: initial;">hrs</span>)';
        }
        return $bucket->get();
    }


    public function getZeroRateDataTableHtml($type)
    {
        $dataTable = [];
        if($type == 'zero_rates') {
            $dataTable = ['Client_Coordinator'=>"Client Care Coordinator",
            'Clinician'=>'Clinician',
            'patient_name'=>'Client',
            'FacilityName' => 'Location',
            'PlannedStart'=>'Visit Date',
            'DisciplineName'=>'Discipline',
            'PayRate'=>'Rate'];
            return view('ccc.zeroRates_datatable',["type"=>$type,'dataTable'=>$dataTable]);
        }
    }

    public function getZeroRatesData($request = '',$showCount=0) {
        $date = date('Y-m-d');
        $dayOfWeek = (int) date('w', strtotime($date));
        $currentWeekSunday = date('Y-m-d', strtotime("-$dayOfWeek days", strtotime($date)));
        $startDate = date('Y-m-d', strtotime('-7 days', strtotime($currentWeekSunday)));
        $endDate = date('Y-m-d', strtotime('+6 days', strtotime($currentWeekSunday)));
        $bucket = DB::table('mssql_schedulemaster')->select(
            'mssql_schedulemaster.id',
            'mssql_patients.id as aoc_patient_id',
            DB::raw("CONCAT('$',FORMAT(mssql_schedulemaster.PayRate, 2)) as PayRate"),
            DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',
            IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),
            mssql_patients.Patient_LastName) AS patient_name"),
            DB::raw("mssql_providers.fullName AS Clinician"),
            'mssql_facilities.FacilityName',
            'mssql_patients.Payer',
            DB::raw("
            DATE_FORMAT(
            CASE WHEN
            DATE(mssql_schedulemaster.ActualStart) != '0000-00-00' &&
            DATE(mssql_schedulemaster.PlannedStart) != '0000-00-00'
            THEN
            coalesce(mssql_schedulemaster.ActualStart,mssql_schedulemaster.PlannedStart)
            WHEN DATE(mssql_schedulemaster.ActualStart) != '0000-00-00'
            THEN
            mssql_schedulemaster.ActualStart
            ELSE
            mssql_schedulemaster.PlannedStart
            END,'".config('constant')['mysqlsearchdatetimeformat']."')  as PlannedStart"),
            'mssql_providers.DisciplineName',
            DB::raw("users.name AS Client_Coordinator"),
            DB::raw("(mssql_schedulemaster.zero_rate_fixed_at IS NOT NULL) as zero_rate_marked_corrected"),
        )
        ->leftJoin('mssql_patients', 'mssql_schedulemaster.PatientID', '=', 'mssql_patients.PatientID')
        ->leftJoin('mssql_providers', 'mssql_schedulemaster.ProviderID', '=', 'mssql_providers.ProviderID')
        ->leftJoin('mssql_facilities', 'mssql_schedulemaster.FacilityID', '=', 'mssql_facilities.FacilityID')
        ->leftJoin('users', 'users.id', '=', 'mssql_patients.ccc_id');
        $bucket->where("mssql_schedulemaster.IsPayable",1)
        ->where("mssql_schedulemaster.ScheduleDate",'>=', $startDate)
        ->where("mssql_schedulemaster.ScheduleDate",'<=', $endDate)
        ->whereRaw("ifnull(mssql_schedulemaster.ProviderID,0) <> 0")
        ->whereRaw("ifnull(mssql_schedulemaster.PatientID,0) <> 0")
        ->where('mssql_schedulemaster.ScheduleStatusID','<>',4)
        ->where('mssql_schedulemaster.PayRate',0)
        ->where('mssql_patients.Search_Name', 'not like', '%test%')
        ->where('mssql_providers.Search_Name', 'not like', '%test%')
        ->where('mssql_patients.is_deleted', 0)
        ->where('mssql_schedulemaster.IsPaid',0);
        if(LD::checkFeatureFlag('remove-IsOfficeStaff-clinicians')){
            $bucket->whereRaw("ifnull(mssql_providers.IsOfficeStaff,0) <> 1");
        }
        if(LD::checkFeatureFlag('exclude-schedules-from-zero-bucket-which-are-is-child')){
            $bucket->whereRaw("ifnull(mssql_schedulemaster.isChild,0) <> 1");
        }
        $bucket->whereRaw("ifnull(DATE(mssql_schedulemaster.zero_rate_fixed_at),'2000-01-01') < CURRENT_DATE");
        $branch = [];
        $state = [];
        $users = [];
        $payer = [];
        $payerGroup=[];
        $region = [];
        $disciplines = array_map(function($enum) {
            return $enum->value;
        },  DisciplineEnum::zeroRates());
        $isMeMode = 0;
        if(isset($request['filters'])) {
            foreach($request['filters'] as $value){
                if($value['name']=='zero_rates_state') {
                    $state[] = $value['value'];
                }
                if($value['name']=='zero_rates_branch') {
                    $branch[] = $value['value'];
                }
                if($value['name']=='zero_rates_clientcare'){
                    $users[] = $value['value'];
                }
                if( $value['name']=='zero_rates_payer'){
                    $payer[] = $value['value'];
                }
                if($value['name']=='zero_rates_region'){
                    $region[] = $value['value'];
                }
                if( $value['name']=='zero_rates_payer_group'){
                    $payerGroup[] = $value['value'];
                }
                if($value['name']=='zero_rates_memode') {
                    $isMeMode = 1;
                }
                if( $value['name']=='Discipline'){
                    $disciplines[] = $value['value'];
                }
            }
        }

        if(!empty($state)) {
            $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
            $bucket->where(function($q) use ($facilities){
                foreach($facilities as $facility) {
                    $q->orWhere("mssql_facilities.id","=",$facility->id);
                }
            });
        }
        if(!empty($branch)) {
            $bucket->where(function($q) use ($branch){
                foreach($branch as $b) {
                    $q->orWhere("mssql_facilities.id","=",$b);
                }
            });
        }
        if(!empty($region)){
            $bucket->where(function($query) use ($region) {
                foreach($region as $val) {
                    $query->orWhere("mssql_facilities.region",'=',$val);
                }
            });
        }
        if(!empty($users)){
            $bucket->whereIn("mssql_patients.ccc_id",$users);
        } elseif($isMeMode) {
            $bucket->where("mssql_patients.ccc_id","=",Auth::user()->id);
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
        if(!empty($disciplines)){
            $bucket->whereIn("mssql_providers.DisciplineName", $disciplines);
        }
        H::ExcelLog($bucket->toSql(),'getZeroRatesData.log');
        if($showCount) {
            return '('.$bucket->count().' )';
        }
        return $bucket->get();
    }

    public function getCaseLoadDataTableHtml($type)
    {
        $dataTable = [];
        if($type == 'case_load') {
            if(LD::checkFeatureFlag('add-check-box-in-case-load')){
                $dataTable = [ 'select'=>'Select','Client_Coordinator'=>"Client Care Coordinator",
                'patient_name'=>'Client',
                'PatientAccount'=>'Client ID',
                'LOBName'=>'LOB',
                'FacilityName' => 'Branch',
                'PatientStatus'=>'Status',
                'onnth'=>'On NTH',
                'totalnth'=>'Hours On NTH'
                ];
            }else{
                $dataTable = ['Client_Coordinator'=>"Client Care Coordinator",
                'patient_name'=>'Client',
                'PatientAccount'=>'Client ID',
                'LOBName'=>'LOB',
                'FacilityName' => 'Branch',
                'PatientStatus'=>'Status',
                'onnth'=>'On NTH',
                'totalnth'=>'Hours On NTH'
                ];
            }
            return view('ccc.caseload_datatable',["type"=>$type,'dataTable'=>$dataTable]);
        }
    }

    public function getCaseLoadData($request = '',$showCount=0) {
        $bucket = DB::table("mssql_patients")
        ->select(
            "mssql_patients.id",
            DB::raw("concat(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) as patient_name"),
            "mssql_patients.PatientAccount",
            "lob.LOBName",
            "mssql_facilities.FacilityName",
            "mssql_patientstatus.PatientStatus",
            DB::raw("users.name AS Client_Coordinator"),
            DB::raw('ifnull((select a.id from  NeedToHire_Accounts a where  a.aoc_patient_id = mssql_patients.id and a.IsActive = 1  order by a.id desc limit 1),0) <> 0 as onnth'),
            DB::raw('round((select sum(ShiftHours) from NeedToHire_Schedules s left join NeedToHire_Accounts a on s.aoc_nthaccounts_id = a.id where  a.aoc_patient_id = mssql_patients.id and s.IsActive=1 and a.IsActive = 1),2)  as totalnth')

            )
        ->leftJoin('mssql_facilities', 'mssql_patients.aoc_facility_id', '=', 'mssql_facilities.id')
        ->leftJoin('lob', 'mssql_patients.LOBID', '=', 'lob.LOBID')
        ->leftJoin('mssql_patientstatus', 'mssql_patients.Patient_CurrentStatusID', '=', 'mssql_patientstatus.PatientStatusID')
        ->leftJoin('users', 'users.id', '=', 'mssql_patients.ccc_id');
		if (LD::checkFeatureFlag('client-new-changes')) {
            $bucket->where('mssql_patients.IsLatestAdmit', 1);
        }

        $bucket = $bucket->where('mssql_patients.is_deleted', '=', 0);

        $branch = [];
        $state = [];
        $users = [];
        $payer = [];
        $payerGroup=[];
        $region = [];
        $status = [];

        $isMeMode = 0;
        if(isset($request['filters'])) {
            foreach($request['filters'] as $value){
                if($value['name']=='case_load_state') {
                    $state[] = $value['value'];
                }
                if($value['name']=='case_load_branch') {
                    $branch[] = $value['value'];
                }
                if($value['name']=='case_load_clientcare'){
                    $users[] = $value['value'];
                }
                if( $value['name']=='case_load_payer'){
                    $payer[] = $value['value'];
                }
                if($value['name']=='case_load_region'){
                    $region[] = $value['value'];
                }
                if( $value['name']=='case_load_payer_group'){
                    $payerGroup[] = $value['value'];
                }
                if( $value['name']=='case_load_CurrentStatusID'){
                    $status[] = $value['value'];
                }
                if($value['name']=='case_load_memode') {
                    $isMeMode = 1;
                }
            }
        }

        if(!empty($state)) {
            $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
            $bucket->where(function($q) use ($facilities){
                foreach($facilities as $facility) {
                    $q->orWhere("mssql_patients.aoc_facility_id","=",$facility->id);
                }
            });
        }
        if(!empty($branch)) {
            $bucket->where(function($q) use ($branch){
                foreach($branch as $b) {
                    $q->orWhere("mssql_patients.aoc_facility_id","=",$b);
                }
            });
        }
        if(!empty($region)){
            $bucket->where(function($query) use ($region) {
                foreach($region as $val) {
                    $query->orWhere("mssql_facilities.region",'=',$val);
                }
            });
        }
        if(!empty($users)){
            $bucket->whereIn("mssql_patients.ccc_id",$users);
        } elseif($isMeMode) {
            $bucket->where("mssql_patients.ccc_id","=",Auth::user()->id);
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
        if(!empty($status)){
            $bucket->where(function($q) use ($status){
                foreach($status as $t) {
                    $q->orWhere("mssql_patients.Patient_CurrentStatusID","=",$t);
                }
            });
        }
        if(LD::checkFeatureFlag('caseload-bucket-additional-filters')) {
            if(isset($request['order'][0]['column'])) {
                if($request['order'][0]['column']==0) {
                    $bucket->orderByRaw("
                        CASE mssql_patients.Patient_CurrentStatusID
                        WHEN '7' THEN 0
                        WHEN '1' THEN 1
                        ELSE 3
                        END
                        ASC");
                }

            }
        }


        H::ExcelLog($bucket->toSql(),'getCaseLoad.log');
        if($showCount) {
            return '('.$bucket->count().')';
        }
        return $bucket->get();
    }



    public function getActiveCoverageDataTableHtml($type)
    {
        $dataTable = [];
        if($type == 'ccc_active_coverage') {

            $dataTable = [
                'Client_Coordinator'=>"Coverage coordinator",
                'FacilityName' => 'Branch',
                'clinician_name'=>'Clinician',
                'activecoverage_outcome'=>'Status',
                'shift_type'=>'Shift Type',
                'days_available'=>'Days Available',
            ];

            return view('ccc.activecoverage_datatable',["type"=>$type,'dataTable'=>$dataTable]);
        }
    }

    public function getActiveCoverageData($request = '',$showCount=0) {
        $bucket = DB::table("mssql_recruitertracker")
        ->select("mssql_recruitertracker.*",
        DB::raw("CONCAT(candidates.firstName,' ',candidates.lastName) AS candidate_name"),
        DB::raw("CONCAT(mssql_providers.FirstName,' ',mssql_providers.LastName) AS clinician_name"),
        "users.name as Client_Coordinator","mssql_facilities.FacilityName",DB::raw("format_dates(days_available)
        AS days_available"))

        ->leftJoin('candidates', 'mssql_recruitertracker.aoc_candidate_id', '=', 'candidates.id')
        ->leftJoin('mssql_providers', 'mssql_recruitertracker.aoc_provider_id', '=', 'mssql_providers.id')
        ->leftJoin('mssql_employmentstatus', 'mssql_employmentstatus.EmploymentStatusID', '=', 'mssql_providers.EmploymentStatusID')

        ->leftJoin('users', 'users.id', '=', 'mssql_providers.cc_id')
            ->leftJoin('mssql_facilities', 'mssql_recruitertracker.aoc_facility_id', '=', 'mssql_facilities.id');

        $subquery = DB::table('mssql_recruitertracker as r')
            ->leftJoin('mssql_providers as p2', 'r.aoc_provider_id', '=', 'p2.id')
            ->leftJoin('mssql_employmentstatus as es2', 'p2.EmploymentStatusID', '=', 'es2.EmploymentStatusID')
            ->leftJoin('mssql_patients as pat2', 'pat2.id', '=', 'r.aoc_patient_id')
            ->select('r.aoc_provider_id', DB::raw('MAX(r.IPEA_Date) as latest_ipea_date'))
            ->where(function ($query) {
                $query->where('es2.EmploymentStatus', '=', 'Active Coverage')
                    ->orWhere(function ($q) {

                        $q->where("r.nurse_status", "2");

                        $q->where("pat2.isPRN", 1);
                        $q->where('r.live_ipea_status', MssqlRecruiterTrackerLiveIpeaStatus::READY_TO_WORK->value);
                        $q->where('r.is_active', '1')->where('r.Candidate_Current_Status', "!=", MssqlRecreturTrackerStatus::INACTIVE->value);

                        $q->where(function ($qqq) {
                            $qqq->whereNotIn("r.activecoverage_outcome", [
                                MssqlRecruiterTrackerActiveCoverageOutcome::ESCALATED_TO_CCM->value,
                                MssqlRecruiterTrackerActiveCoverageOutcome::STATUS_CHANGE_REQUEST->value,
                                MssqlRecruiterTrackerActiveCoverageOutcome::NO_LONGER_INTERESTED_MOVE_TO_NEEDS_TO_REVIEW->value,
                            ])
                                ->orWhereNull("r.activecoverage_outcome");
                        });

                        $q->whereNotExists(function ($notExists) {
                            $notExists->select(DB::raw(1))
                                ->from('mssql_recruitertracker as sub')
                                ->join('mssql_providers as sp', 'sub.aoc_provider_id', '=', 'sp.id')
                                ->join('mssql_employmentstatus as ses', 'sp.EmploymentStatusID', '=', 'ses.EmploymentStatusID')
                                ->where('ses.EmploymentStatus', '=', 'Active Coverage')
                                ->whereColumn('sub.aoc_provider_id', 'r.aoc_provider_id');
                        });
                    });
            })
            ->groupBy('r.aoc_provider_id');

        $bucket->joinSub($subquery, 'latest', function ($join) {
            $join->on('mssql_recruitertracker.aoc_provider_id', '=', 'latest.aoc_provider_id')
                ->where(function ($q) {
                    $q->whereColumn('mssql_recruitertracker.IPEA_Date', '=', 'latest.latest_ipea_date')
                        ->orWhere(function ($qq) {
                            $qq->whereNull('mssql_recruitertracker.IPEA_Date')
                                ->whereNull('latest.latest_ipea_date');
                        });
                });
        });

        $branch = [];
        $state = [];
        $users = [];
        $region = [];
        $status = [];

        $isMeMode = 0;
        if(isset($request['filters'])) {
            foreach($request['filters'] as $value){
                if($value['name']=='active_coverage_state') {
                    $state[] = $value['value'];
                }
                if($value['name']=='active_coverage_branch') {
                    $branch[] = $value['value'];
                }
                if($value['name']=='active_coverage_clientcare'){
                    $users[] = $value['value'];
                }
                if($value['name']=='active_coverage_region'){
                    $region[] = $value['value'];
                }

                if($value['name']=='active_coverage_memode') {
                    $isMeMode = 1;
                }
            }
        }

        if(!empty($state)) {
            $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
            $bucket->where(function($q) use ($facilities){
                foreach($facilities as $facility) {
                    $q->orWhere("mssql_recruitertracker.aoc_facility_id","=",$facility->id);
                }
            });
        }
        if(!empty($branch)) {
            $bucket->where(function($q) use ($branch){
                foreach($branch as $b) {
                    $q->orWhere("mssql_recruitertracker.aoc_facility_id","=",$b);
                }
            });
        }
        if(!empty($region)){
            $bucket->where(function($query) use ($region) {
                foreach($region as $val) {
                    $query->orWhere("mssql_facilities.region",'=',$val);
                }
            });
        }
        if(!empty($users)){
            $bucket->whereIn("mssql_providers.cc_id",$users);
        } elseif($isMeMode) {
            $bucket->where("mssql_providers.cc_id","=",Auth::user()->id);
        }

        H::ExcelLog($bucket->toSql(),'getActiveCoverage.log');
        if($showCount) {
            return '('.$bucket->count().')';
        }

        return $bucket->groupBy('aoc_provider_id')->get();
    }
}
