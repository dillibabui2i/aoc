<?php
namespace App\Helpers;

use App\Enums\MssqlScheduleMasterStatus;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\H;
use App\Helpers\LD;

class ClientCareCoordinatorException
{
    const PATIENTNAME = "CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name";
    const CLIENTID = 'Client ID';
    const CCC = "users.name AS Client_Coordinator";
    const COORDINATOR = "users.name AS Client_Coordinator";
    public function getFormType($followupType,$monthlyCallId,$weeklyCallId){
        $formType = '';
        if($followupType=='weekly' && $monthlyCallId!=0) {
            $formType = 'Monthly & NTH';
        } else if($followupType=='weekly' && $monthlyCallId==0) {
            $formType = 'NTH Weekly';
        } else if($followupType=='monthly' && $weeklyCallId!=0) {
            $formType = 'Monthly & NTH';
        } else if($followupType=='monthly' && $weeklyCallId==0) {
            $formType = 'Monthly';
        }

        return $formType;

    }
    public function isMonthlyCallOpen($patientId){

       $monthly= DB::table("ccc_followup_calls")->select("*")
        ->where("aoc_patient_id",$patientId)
        ->where("type","client")
        ->where(function($q){
            $q->whereNotIn("outcome",["Not Complete","Completed"])
            ->orWhereNull("outcome");
        })
        ->whereDate("due_date",">=",date("Y-m-d"))
        ->orderBy("due_date","asc")
        ->first();


        if($monthly) {
            return $monthly->id;
        } else {
            return 0;
        }

    }

    public function isWeeklyCallOpen($patientId){

        $weekly= DB::table("ccc_followup_calls")->select("*")
        ->where("aoc_patient_id",$patientId)
        ->where("type","nth")
        ->where(function($q){
            $q->whereNotIn("outcome",["Not Complete","Completed"])
            ->orWhereNull("outcome");
        })
        ->whereDate("due_date",">=",date("Y-m-d"))
        ->orderBy("due_date","asc")
        ->first();

        if($weekly) {
            return $weekly->id;
        } else {
            return 0;
        }

    }
    public $buckets = [
        "not_meeting_nurses"=>["heading"=>"Not Meeting Nurses"],
        "the_sale_255_characters"=>["heading"=>"The Sale < 255 Characters"],
        "gender_preference_mismatch_in_kantime"=>["heading"=>"Gender Preference Mismatch in Kantime"],
        "no_match_in_kantime"=>["heading"=>"No Match in Kantime"],
        "no_hours"=>["heading"=>"No Hours"],
        "no_payor"=>["heading"=>"No Payor"],
        "no_ccc"=>["heading"=>"No CCC"],
        "ccc_respite_no_respite_payor"=>["heading"=>"Respite w/No Respite Payor"],
    ];


    public function getBuckets(){
        if (LD::checkFeatureFlag('staffed-coverage-shifts-not-updated-in-kantime')) {
            $this->buckets['staffed_coverage_shifts_not_updated_in_kantime'] = ["heading" => "Staffed Coverage Shifts Not Updated in KanTime"];
        }
        if (LD::checkFeatureFlag('add-new-widget-to-client-care-exception')) {
            $this->buckets['nth_hours_exceed_authorized_hours'] = ["heading" => "NTH Hours Exceed Authorized Hours"];
        }
        if (LD::checkFeatureFlag('ccc-no-services-needed-widget-exception-bucket')) {
            $this->buckets['no_services_needed'] = ["heading" => "No Services Needed"];
        }
        if (LD::checkFeatureFlag('ccc-coverage-shift-without-kantime-clinician-exception-widget')) {
            $this->buckets['coverage_shift_without_kantime_clinician'] = ["heading" => "Coverage Shift without KanTime Clinician"];
        }
        if (LD::checkFeatureFlag('add-acceptscoverage-to-nth-schedule-page')) {
            $this->buckets['ccc_intake_coverage_shift_covered_by_perm_nurse'] =
            ["heading" => "Intake Coverage Shift Covered by PERM Nurse"];
            $this->buckets['ccc_intake_coverage_shift_before_goal_soc_date'] =
            ["heading" => "Intake Coverage Shift Before Goal SOC Date"];
        }
        if (LD::checkFeatureFlag('mismatched-clients-bucket')) {
            $this->buckets['clients_without_kantime_match'] = ["heading" => "Clients without KanTime Match"];
        }
        if (LD::checkFeatureFlag('active-coverage-exception')) {
            $this->buckets['active_coverage_exception'] = ["heading" => "Active Coverage Clinicians"];
        }
        return $this->buckets;
    }


    public function getBucketTitle($type) {
        return $this->buckets[$type]['heading'];
    }

    public function getDataTableHtml($type)
    {
        $dataTable = [];
        $lastUpdateText = 'Last Update';
        $constClientCareCoordinator = 'Client Care Coordinator';
        $contCoverageBookedBy = 'Coverage Booked by';
        switch($type) {
            case 'weekly_nth_calls':
                if(LD::checkFeatureFlag('weekly-nth-bucket-ccr-show-all-excelssonly')) {
                    $dataTable = ['patient_name'=>'Client',
                    'PatientAccount'=>'ClientID',
                    'Client_Coordinator'=>$constClientCareCoordinator,
                    'IsPriority'=>'Priority',
                    'FacilityName'=>'Branch',
                    'Payer'=>'Payer','CF_ServicesNeeded'=>'Service Needed',
                    'created_at'=>'Date Added','totalhours'=>'Hours on NTH',
                    'attempt_count'=>"Status",
                    'outcome'=>'Outcome',
                    'last_weekly_followup'=>$lastUpdateText,
                    'is_completed'=>'IsHidden'];

                } else {
                    $dataTable = ['patient_name'=>'Client',
                    'PatientAccount'=>'ClientID',
                    'Client_Coordinator'=>$constClientCareCoordinator,
                    'IsPriority'=>'Priority','FacilityName'=>'Branch',
                    'Payer'=>'Payer',
                    'CF_ServicesNeeded'=>'Service Needed',
                    'created_at'=>'Date Added',
                    'totalhours'=>'Hours on NTH',
                    'attempt_count'=>"Status",'outcome'=>'Outcome',
                    'last_weekly_followup'=>$lastUpdateText];

                }
                break;
            case 'monthly_calls':
                $dataTable = ['patient_name'=>'Client',
                'PatientAccount'=>'ClientID','Client_Coordinator'=>$constClientCareCoordinator,
                'FacilityName'=>'Branch','Payer'=>'Payer',
                'payer_group'=>'Payer Group',
                'attempt_count'=>"Status",
                'outcome'=>'Outcome',
                'last_monthly_followup'=>$lastUpdateText];
                break;
            case 'ccc_missed_visits':
            case 'planned_visits_with_no_clinician':
                if(LD::checkFeatureFlag('add-patient-status-filter-in-missed-visits-bucket')) {
                    $dataTable = ['select'=>'select',
                        'FacilityName'=>'Branch',
                        'Clinician'=>'Clinician',
                        'patient_name'=>'Client',
                        'Client_Coordinator'=>$constClientCareCoordinator,
                        'ScheduleDate'=>'Schedule Date',
                        'PlannedTime'=>'Planned Time',
                        'PlannedHours'=>'Planned Hours',
                        'ServiceCode'=>'Services',
                        'PatientStatus'=>'Client Status'
                    ];

                } else {

                    if(LD::checkFeatureFlag('add-multi-select-to-ccc-missed-visits')){
                        $dataTable = ['select'=>'select','FacilityName'=>'Branch','Clinician'=>'Clinician','patient_name'=>'Client',
                        'Client_Coordinator'=>$constClientCareCoordinator,
                        'ScheduleDate'=>'Schedule Date',
                        'PlannedTime'=>'Planned Time',
                        'PlannedHours'=>'Planned Hours',
                        'ServiceCode'=>'Services',
                        'Payer'=>'Payer',
                        'payer_group'=>'Payer Group'
                    ];
                    }else{
                        $dataTable = ['FacilityName'=>'Branch','Clinician'=>'Clinician','patient_name'=>'Client',
                        'Client_Coordinator'=>$constClientCareCoordinator,
                        'ScheduleDate'=>'Schedule Date',
                        'PlannedTime'=>'Planned Time',
                        'PlannedHours'=>'Planned Hours',
                        'ServiceCode'=>'Services',
                        'Payer'=>'Payer',
                        'payer_group'=>'Payer Group'
                    ];
                    }

                }



                break;
            case 'ccc_new_clients':
            case 'ccc_pending_intake_clients':
                    if(LD::checkFeatureFlag('new-intake-clients-modifications')) {
                        $dataTable = ['patient_name'=>'Client','FacilityName'=>'Branch',
                            'SOCDate'=>'SOC Date',
                            'OnNTH'=>'On NTH',
                            'outcome'=>'Outcome',
                            'Client_Coordinator'=>'CCC Name'
                        ];
                    } else {
                        $dataTable = ['patient_name'=>'Client','FacilityName'=>'Branch',
                        'SOCDate'=>'SOC Date',
                        'OnNTH'=>'On NTH',
                        'attempt_count'=>'Attempt#',
                        'outcome'=>'Outcome',
                        'Client_Coordinator'=>'CCC Name'
                    ];
                    }

                break;
            case 'staffed_coverage_shifts_not_updated_in_kantime':
                if(LD::checkFeatureFlag('change-exception-bucket-column-name')){
                    $dataTable = [
                        'Client_Coordinator' => 'Coordinator',
                        'patient_name' => 'Client',
                        'PatientStatus'=>'Status',
                        'shift_date' => 'Visit Date',
                        'Schedule_Time' => 'Visit Time',
                        'Payer' => 'Payer',
                        'Clinician' => 'Clinician',
                        'escalated_pay_rate' => 'Pay Rate',
                    ];
                }else{
                    $dataTable = [
                        'Client_Coordinator' => 'CCC',
                        'patient_name' => 'Client',
                        'PatientStatus'=>'Status',
                        'shift_date' => 'Visit Date',
                        'Schedule_Time' => 'Visit Time',
                        'Payer' => 'Payer',
                        'Clinician' => 'Clinician',
                        'escalated_pay_rate' => 'Pay Rate',
                    ];
                }
                break;
            case 'nth_hours_exceed_authorized_hours':
                    $dataTable = [
                        'patient_name' => 'Client',
                        'PatientAccount'=>'ClientID',
                        'PatientStatus'=>'Status',
                        'FacilityName' => 'Location',
                        'Client_Coordinator' => $constClientCareCoordinator,
                        'CreatedOn' => "Date Added to NTH",
                        'days_since_created' => "Days on NTH",
                        'totalhours' => "NTH Hours",
                        'totalauthhours' => "Authorized Hours",
                    ];
                break;
            case 'the_sale_255_characters':
                    $dataTable = [
                        'patient_name' => 'Client',
                        'PatientAccount' => self::CLIENTID,
                        'PatientStatus' => 'Status',
                        'FacilityName' => 'Location',
                        'Client_Coordinator' => $constClientCareCoordinator,
                        'CreatedOn' => "Date Added to NTH",
                        'days_since_created' => "Days on NTH",
                        'character_count' => "Character Count",
                    ];
                break;
            case 'ccc_intake_coverage_shifts_booked':
                $dataTable = ['patient_name'=>'Client Name',
                'Client_Coordinator'=>$constClientCareCoordinator,
                'IsPriority'=>'Priority',
                'shift_date'=>'Date','Schedule_Time'=>'Schedule',
                'Hours'=>"Hours",'Service'=>"Service",
                'Discipline'=>"Discipline",
                'Payer'=>"Payer",
                'payer_group'=>"Payer Group"];
                break;
            case 'coverage_shift_without_kantime_clinician':
                $dataTable = ['Client_Coordinator'=>"Coverage Coordinator",
                'FacilityName'=>'Branch',
                'patient_name'=>'Client',
                'Clinician'=>'Clinician Name',
                'shift_date' => 'Visit Date',
                'Payer'=>"Payer",
                'payer_group'=>"Special Payer"];
                break;
            case 'ccc_intake_coverage_shift_covered_by_perm_nurse':
                $dataTable = [
                    'Client_Coordinator' => $contCoverageBookedBy,
                    'patient_name' => 'Client',
                    'CF_GoalSOCDate' => 'Goal SOC Date',
                    'shift_date' => 'Visit Date',
                    'Schedule_Time' => 'Visit Time',
                    'Payer' => "Payer",
                    'Clinician' => "Coverage Clinician",
                ];
            break;
            case 'ccc_intake_coverage_shift_before_goal_soc_date':
                $dataTable = [
                    'Client_Coordinator' => $contCoverageBookedBy,
                    'patient_name' => 'Client',
                    'CF_GoalSOCDate' => 'Goal SOC Date',
                    'shift_date' => 'Visit Date',
                    'Schedule_Time' => 'Visit Time',
                    'Payer' => "Payer",
                    'Clinician' => "Clinician",
                    'escalated_pay_rate' => 'Pay Rate',
                ];
            break;
            case 'clients_without_kantime_match':
                $dataTable = [
                    'patient_name' => 'Client',
                    'PatientAccount' => self::CLIENTID,
                    'FacilityName' => 'Branch',
                    'PatientStatus' => 'Status'
                ];
            break;
            case 'active_coverage_exception':
                $dataTable = [
                    'Coverage_Coordinator' => "Coverage coordinator",
                    'FacilityName' => 'Branch',
                    'clinician_name' => 'Clinician',
                    'activecoverage_outcome' => 'Status',
                    'shift_type' => 'Shift Type',
                    'days_available' => 'Days Available',
                ];
            break;
            default:
                $dataTable = ['patient_name'=>'Client',
                'PatientAccount'=> self::CLIENTID,
                'PatientStatus'=>'Status',
                'FacilityName'=>'Location',
                'Client_Coordinator'=>$constClientCareCoordinator,
                'CreatedOn'=>"Date Added to NTH",
                'days_since_created'=>"Days on NTH"];
                break;
        }

        if ($type == 'not_meeting_nurses') {
            $dataTable["date_not_meeting_nurses"] = "# Days Not Meeting Nurses";
        }
        return view('ccc.datatable',["type"=>$type,'dataTable'=>$dataTable]);
    }

    public function getClientCareException($type, $request = '',$showCount=0) {
        $request = $request->all();
        $clinicianName = "CONCAT(mssql_providers.FirstName,' ',mssql_providers.LastName) AS Clinician";
        if($type=='ccc_missed_visits' || $type=='planned_visits_with_no_clinician') {

            $exceptLineOfBusiness = ['1003', '1004', '1006'];
            $bucket = DB::table("mssql_schedulemaster");
            $bucket
            ->select('mssql_schedulemaster.id','mssql_patients.LOBID',
            DB::raw('service_codes_daas.name as ServiceCode'),
            'mssql_schedulemaster.PlannedStart',
            'mssql_schedulemaster.PlannedEnd',
            DB::raw(self::PATIENTNAME),
            DB::raw($clinicianName),
            'mssql_facilities.FacilityName',
            DB::raw(self::CCC),
            "mssql_patients.Payer",
            "mssql_patients.payer_group",
            "mssql_schedulemaster.is_missed_visit",
            "mssql_schedulemaster.ScheduleStatusID",
            "mssql_schedulemaster.ScheduleDate",
            "mssql_schedulemaster.IsBillable",
            'mssql_schedulemaster.PlannedHours',
            DB::raw("mssql_patients.id AS aoc_patient_id"),
            DB::raw("case when (select s.id
                    from NeedToHire_Schedules s
                left join NeedToHire_Accounts a on s.aoc_nthaccounts_id = a.id
                where a.aoc_patient_id = mssql_patients.id
                        and s.StartDayOfWeekInt = DAYOFWEEK(PlannedStart)
                        and s.StartHour = hour(PlannedStart)
                        and s.StartMinute = minute(PlannedStart)
                    and s.EndHour = hour(PlannedEnd)
                        and s.EndMinute = minute(PlannedEnd)
                        and s.IsActive = 1 and a.IsActive = 1 limit 1) is not null
            then 'Yes' else 'No' end as OnNTH")
            );



            $bucket->leftJoin("mssql_patients",function($query){
                $query->on("mssql_patients.PatientID","=","mssql_schedulemaster.PatientID");
            });

            $bucket->leftJoin("mssql_providers",function($query){
                $query->on("mssql_providers.ProviderID","=","mssql_schedulemaster.ProviderID");
            });
            $bucket->leftJoin("mssql_facilities",function($query){
                $query->on("mssql_facilities.FacilityID","=","mssql_schedulemaster.FacilityID");
            });
            $bucket->leftJoin("service_codes_daas",function($query){
                $query->on("service_codes_daas.service_id","=","mssql_schedulemaster.ServiceCodeID");
            });
            $bucket->leftJoin("users",function($query){
                $query->on("users.id","=","mssql_patients.ccc_id");
            });

            if (LD::checkFeatureFlag('client-new-changes')) {
                $bucket->where('mssql_patients.IsLatestAdmit', 1);
            }

            if(LD::checkFeatureFlag('add-patient-status-filter-in-missed-visits-bucket')) {
                $bucket->addSelect("mssql_patientstatus.PatientStatus");
                $bucket->leftJoin("mssql_patientstatus",function($query){
                    $query->on("mssql_patients.Patient_CurrentStatusID","=","mssql_patientstatus.PatientStatusID");
                });
            }


            if(LD::checkFeatureFlag('discharged-clients-missed-visit-bucket')) {
                $bucket->whereRaw("if(mssql_patientstatus.PatientStatus='Discharged' and mssql_patients.DischargeDate != '0000-00-00 00:00:00',mssql_schedulemaster.ScheduleDate < mssql_patients.DischargeDate, true)");
            }

            if(isset($request['filters'])) {

                $branch = [];
                $state = [];
                $users = [];
                $payer = [];
                $payerGroup = [];
                $startDate = '';
                $endDate = '';
                $isMeMode = 0;
                $onnth = '';
                $lob= [];
                $patientStatus = [];

                foreach($request['filters'] as $key => $value){

                    if($value['name']=='missedstate') {
                        $state[] = $value['value'];
                    }
                    if($value['name']=='missedbranch') {
                        $branch[] = $value['value'];
                    }
                    if($value['name']=='missedclientcare'){
                        $users[] = $value['value'];
                    }
                    if($value['name']=='schedule_from_date') {
                        if(!empty($value['value'])) {
                            $startDate = $value['value'];
                        }
                    }
                    if($value['name']=='schedule_to_date') {
                        if(!empty($value['value'])) {
                            $endDate = $value['value'];
                        }
                    }
                    if($value['name']=='missedmemode') {
                        $isMeMode = 1;
                    }
                    if(LD::checkFeatureFlag('payer-filters-on-missed-visits')){
                        if($value['name']=='Payer' && $value['value'] != ''){
                            $payer[] = $value['value'];
                        }
                        if( $value['name']=='payer_group'  && $value['value'] != ''){
                            $payerGroup[] = $value['value'];
                        }
                        if($value['name']=='on_nth' && $value['value'] != ''){
                            $onnth = $value['value'];
                        }
                    }
                    if(LD::checkFeatureFlag('add-lob-filter-in-missed-visits-bucket')){
                        if($value['name']=='LOB' && $value['value'] != ''){
                            $lob[] = $value['value'];
                        }
                    }

                    if(LD::checkFeatureFlag('add-patient-status-filter-in-missed-visits-bucket')){
                        if($value['name']=='patient_status' && $value['value'] != ''){
                            $patientStatus[] = $value['value'];
                        }
                    }
                }

                if(LD::checkFeatureFlag('add-patient-status-filter-in-missed-visits-bucket')){
                    if(count($patientStatus)) {
                        $bucket->whereIn("mssql_patients.Patient_CurrentStatusID",$patientStatus);
                    }
                }

                if(count($state)>0) {
                    H::ExcelLog('state: ' . print_r($state,true),'dbStatefilteronmissedvisiste.log');
                    $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);

                    if(LD::checkFeatureFlag('orientation-coordinator-filter-fix')) {
                            $facililtyIdArray = [];
                            foreach($facilities as $facility) {
                                $facililtyIdArray[] = $facility->id;
                            }
                            if(!empty($facililtyIdArray)) {
                                $bucket->whereIn("mssql_patients.aoc_facility_id",$facililtyIdArray);
                            }
                    } else {
                        $bucket->where(function($q) use ($facilities){
                            foreach($facilities as $facility) {
                                $q->orWhere("mssql_patients.aoc_facility_id","=",$facility->id);
                            }
                        });
                    }

                }
                if(count($branch)>0) {

                    if(LD::checkFeatureFlag('orientation-coordinator-filter-fix')) {
                            $bucket->whereIn("mssql_patients.aoc_facility_id",$branch);
                    } else {
                        $bucket->where(function($q) use ($branch){
                            foreach($branch as $b) {
                                $q->orWhere("mssql_patients.aoc_facility_id","=",$b);
                            }
                        });
                    }

                }


                if(!empty($users) && !$isMeMode){
                    $bucket->whereIn("mssql_patients.ccc_id",$users);
                } else if($isMeMode) {
                    $bucket->where("mssql_patients.ccc_id","=",Auth::user()->id);
                }
                if(LD::checkFeatureFlag('payer-filters-on-missed-visits')){
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

                    if(!empty($onnth)){
                        if($onnth == 'Yes') {
                            $bucket->having("OnNTH","Yes");
                        }elseif($onnth == 'No'){
                            $bucket->having("OnNTH","No");
                        }
                    }
                }

                if(!empty($startDate)) {
                    $convertedStartDate = date('Y-m-d',strtotime($startDate));
                    $bucket->where("mssql_schedulemaster.ScheduleDate", ">=",$convertedStartDate);
                }

                if(!empty($endDate)) {
                    $convertedEndDate = date('Y-m-d',strtotime($endDate));
                    $bucket->where("mssql_schedulemaster.ScheduleDate", "<=",$convertedEndDate);
                }


                if(LD::checkFeatureFlag('add-lob-filter-in-missed-visits-bucket')){
                    if(count($lob) > 0){
                        $bucket->whereIn("mssql_patients.LOBID",$lob);
                    }
                }


            }
            if(LD::checkFeatureFlag('ccc-missed-visit-overdue-logic')) {
                $bucket->addSelect("mssql_schedulemaster.checked_off_at","mssql_schedulemaster.checked_off_by");
            }

            $today = date('Y-m-d');
            $bucket->whereRaw("COALESCE(NULLIF(mssql_schedulemaster.checked_off_at, 0), '1900-01-01') < ?", [$today]);
            $bucket->where('mssql_schedulemaster.ScheduleDate', '<', $today);

            $bucket->where('mssql_schedulemaster.ScheduleStatusID', MssqlScheduleMasterStatus::PLANNED->value);

            if($type=='ccc_missed_visits') {
                $bucket->where('mssql_schedulemaster.is_no_provider',1);
            }

            if($type=='planned_visits_with_no_clinician') {
                $bucket->where('mssql_schedulemaster.is_no_provider',2);
            }

            H::ExcelLog($bucket->toSql(), 'MissedVisits.log');

            if($showCount) {
                $tmp = $bucket->count();
                if (LD::checkFeatureFlag('add-planned-hours-to-ccc-missed-visits')) {
                    $totalHr =  number_format(array_sum($bucket->pluck('PlannedHours')->all()), 0);
                    return '('.$tmp.' | ' . $totalHr . ' <span style="text-transform: initial;">hrs</span>)';
                } else {
                    return '('.$tmp.')';
                }
            }

            return $bucket->get();


        }elseif($type=='ccc_new_clients' || $type=='ccc_pending_intake_clients') {
            $bucket = DB::table("mssql_patients")
                ->select(
                    'mssql_patients.id',
                    'mssql_patients.attempt_count',
                    'mssql_patients.outcome',
                    'mssql_patients.SOCDate',
                    'mssql_patients.CreatedOn',
                    'mssql_patients.PatientAccount',
                    DB::raw(self::PATIENTNAME),
                    'mssql_facilities.FacilityName',
                    DB::raw(self::CCC),
                    DB::raw("NeedToHire_Accounts.aoc_patient_id AS nth_aoc_patient_id")
                );
            $bucket->leftJoin("mssql_facilities",function($query){
                $query->on("mssql_facilities.id","=","mssql_patients.aoc_facility_id");
            });
            $bucket->leftJoin("NeedToHire_Accounts",function($query){
                $query->on("NeedToHire_Accounts.aoc_patient_id","=","mssql_patients.id");
                $query->where("NeedToHire_Accounts.IsActive",1);
            });
            $bucket->leftJoin("users",function($query){
                $query->on("users.id","=","mssql_patients.ccc_id");
            });
            if(isset($request['filters'])) {
                $branch = [];
                $state = [];
                $users = [];
                $startDate = '';
                $endDate = '';
                $isMeMode = 0;
                $region = [];

                foreach($request['filters'] as $key => $value){
                    if($value['name']=='intakestate') {
                        $state[] = $value['value'];
                    }
                    if($value['name']=='intakebranch') {
                        $branch[] = $value['value'];
                    }
                    if($value['name']=='intakeregion') {
                        $region[] = $value['value'];
                    }
                    if($value['name']=='intakeclientcare'){
                        $users[] = $value['value'];
                    }
                    if($value['name']=='schedule_to_date') {
                        if(!empty($value['value'])) {
                            $endDate = $value['value'];
                        }
                    }
                    if($value['name']=='intakememode') {
                        $isMeMode = 1;
                    }
                }

                if(count($state)>0) {
                    H::ExcelLog(print_r($state, true), 'IntakeClient.log');
                    $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
                    H::ExcelLog(print_r($facilities, true), 'IntakeClient.log');
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

                if(count($region)>0) {
                    $bucket->where(function($q) use ($region){
                        foreach($region as $b) {
                            $q->orWhere("mssql_facilities.region","=",$b);
                        }
                    });
                }

                if(!empty($users)){
                    $bucket->whereIn("mssql_patients.ccc_id",$users);
                }
                if($isMeMode){
                    $bucket->where("mssql_patients.ccc_id","=",Auth::user()->id);
                }
            }
            $today = date('Y-m-d');
            if($type=='ccc_new_clients') {
                $bucket->where('mssql_patients.ccc_intro_call','=',0);
                $bucket->where(function($q) use($today){
                    $q->whereDate("ccc_intro_call_made_at","<",$today.' 00:00:00')->orWhereNull("ccc_intro_call_made_at");
                });

                if(LD::checkFeatureFlag('new-intake-clients-modifications')) {
                    $bucket->leftJoin("intake",function($query){
                        $query->on("intake.aoc_patient_id","=","mssql_patients.id");
                    });
                    $bucket->where(DB::raw("IFNULL(intake.intake_readytostart,0)"),"1");
                    $bucket->whereNotIn("intake.LOBID",[1003,1004,1006]);
                    $bucket->whereRaw("ifnull(intake.CF_GoalSOCDate,'0000-00-00 00:00:00') <= NOW()");
                }
            }
            if($type=='ccc_pending_intake_clients') {
                $bucket->where('mssql_patients.Patient_CurrentStatusID','=',4);
            }
            H::ExcelLog($bucket->toSql(), 'IntakeClient.log');
            if($showCount) {
                return '('.$bucket->count().')';
            }
            return $bucket->get();
        } elseif ($type == 'staffed_coverage_shifts_not_updated_in_kantime') {
            if(LD::checkFeatureFlag('allow-clinician-name-coverage-tracker')){
                if(LD::checkFeatureFlag('allow-clinician-name-coverage-tracker')){
                    $bucket = DB::table("coverage_tracker")
                    ->select(
                        "mssql_patients.id as PID",
                        'mssql_patientstatus.PatientStatus',
                        DB::raw(self::COORDINATOR),
                        DB::raw(self::PATIENTNAME),
                        'coverage_tracker.shift_date',
                        'coverage_tracker.Schedule_Time',
                        'mssql_patients.Payer',
                        DB::raw('CASE WHEN coverage_tracker.aoc_provider_id = 0 THEN coverage_tracker.new_clinician_name
                        ELSE CONCAT(mssql_providers.FirstName," ",mssql_providers.LastName) END as Clinician'),
                        'coverage_tracker.escalated_pay_rate',
                        DB::raw('(SELECT m.id FROM `maps_coverageneeds` m WHERE
                        m.Patient_ID = coverage_tracker.Patient_ID
                        and m.Schedule_Time = coverage_tracker.Schedule_Time and m.shift_date =
                        coverage_tracker.shift_date limit 1) as MID'));


                }else{
                    $bucket = DB::table("coverage_tracker")
                    ->select(
                        "mssql_patients.id as PID",
                        'mssql_patientstatus.PatientStatus',
                        DB::raw(self::CCC),
                        DB::raw(self::PATIENTNAME),
                        'coverage_tracker.shift_date',
                        'coverage_tracker.Schedule_Time',
                        'mssql_patients.Payer',
                        DB::raw('CASE WHEN coverage_tracker.aoc_provider_id = 0 THEN coverage_tracker.new_clinician_name
                        ELSE CONCAT(mssql_providers.FirstName," ",mssql_providers.LastName) END as Clinician'),
                        'coverage_tracker.escalated_pay_rate',
                        DB::raw('(SELECT m.id FROM `maps_coverageneeds` m WHERE
                        m.Patient_ID = coverage_tracker.Patient_ID
                        and m.Schedule_Time = coverage_tracker.Schedule_Time and m.shift_date =
                        coverage_tracker.shift_date limit 1) as MID'));
                }
            }else{
                $bucket = DB::table("coverage_tracker")
                    ->select(
                        "mssql_patients.id as PID",
                        'mssql_patientstatus.PatientStatus',
                        DB::raw(self::CCC),
                        DB::raw(self::PATIENTNAME),
                        'coverage_tracker.shift_date',
                        'coverage_tracker.Schedule_Time',
                        'mssql_patients.Payer',
                        DB::raw($clinicianName),
                        'coverage_tracker.escalated_pay_rate',
                        DB::raw('(SELECT m.id FROM `maps_coverageneeds` m WHERE m.Patient_ID =
                        coverage_tracker.Patient_ID and m.Schedule_Time = coverage_tracker.Schedule_Time
                        and m.shift_date = coverage_tracker.shift_date limit 1) as MID'));
            }
            $bucket->leftJoin("mssql_patients", function($query) {
                $query->on("coverage_tracker.aoc_patient_id", "=", "mssql_patients.id");
            });
            $bucket->leftJoin("mssql_facilities", function($query) {
                $query->on("mssql_facilities.id", "=", "mssql_patients.aoc_facility_id");
            });
            if(LD::checkFeatureFlag('allow-clinician-name-coverage-tracker')){
                $bucket->leftJoin("users",function($query){
                    $query->on("users.id", "=", "coverage_tracker.created_by");
                });
            }else{
                $bucket->leftJoin("users",function($query){
                    $query->on("users.id", "=", "mssql_patients.ccc_id");
                });
            }
            $bucket->leftJoin("mssql_providers",function($query){
                $query->on("mssql_providers.id","=","coverage_tracker.aoc_provider_id");
            });
            $bucket->leftJoin("mssql_patientstatus", function($query){
                $query->on("mssql_patients.Patient_CurrentStatusID","=","mssql_patientstatus.PatientStatusID");
            });
            if(LD::checkFeatureFlag('client-not-kantime-staffed-coverage-not-update-kantime')) {
                $bucket->where("mssql_patients.not_in_kantime",0);
            }

            if(isset($request['filters'])) {
                $branch = [];
                $state = [];
                $region = [];
                $users = [];
                $payer = [];
                $payerGroup=[];
                $isMeMode = 0;


                if(isset($request['filters'])) {

                    foreach($request['filters'] as $key => $value){
                        if($value['name']=='ccstate' || $value['name']=='weeklyccstate' || $value['name']=='monthlyccstate') {
                            $state[] = $value['value'];
                        }
                        if($value['name']=='ccbranch' || $value['name']=='weeklyccbranch'  || $value['name']=='monthlyccbranch') {
                            $branch[] = $value['value'];
                        }
                        if($value['name']=='ccregion') {
                            $region[] = $value['value'];
                        }
                        if($value['name']=='clientcare' || $value['name']=='weeklyclientcare'  || $value['name']=='monthlyclientcare'){
                            $users[] = $value['value'];
                        }
                        if( $value['name']=='weeklypayer' ||  $value['name']=='monthlypayer'){
                            $payer[] = $value['value'];
                        }
                        if( $value['name']=='weeklypayergroup'  || $value['name']=='monthlypayergroup'){
                            $payerGroup[] = $value['value'];
                        }
                        if(LD::checkFeatureFlag('payer-filters-on-exception-bucket')){
                            if($value['name']=='exPayer' && $value['value'] != ''){
                                $payer[] = $value['value'];
                            }
                            if( $value['name']=='ex_payer_group'  && $value['value'] != ''){
                                $payerGroup[] = $value['value'];
                            }
                        }
                        if($value['name']=='ccdefault') {
                            $isMeMode = 1;
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
                if(count($region)>0) {
                    $bucket->where(function($q) use ($region){
                        foreach($region as $b) {
                            $q->orWhere("mssql_facilities.region","=",$b);
                        }
                    });
                }

                if(!empty($users) && !$isMeMode){
                    $bucket->whereIn("mssql_patients.ccc_id",$users);
                } else if($isMeMode) {
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
            }
            $bucket->whereRaw('isnull((SELECT m.id FROM `maps_coverageneeds` m WHERE m.Patient_ID = coverage_tracker.Patient_ID and m.Schedule_Time = coverage_tracker.Schedule_Time and m.shift_date = coverage_tracker.shift_date limit 1)) = 0');
            H::ExcelLog($bucket->toSql(), 'IntakeClient.log');
            if($showCount) {
                return '('.$bucket->count().')';
            }
            return $bucket->get();
        } elseif($type=='ccc_intake_coverage_shifts_booked') {
            $today = date('Y-m-d');
            $bucket = DB::table('coverage_tracker')
            ->leftJoin('mssql_providers', 'mssql_providers.id', '=', 'coverage_tracker.aoc_provider_id')
            ->leftJoin('mssql_patients', 'mssql_patients.id', '=', 'coverage_tracker.aoc_patient_id')
            ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_patients.FacilityID')
            ->leftJoin('users', 'users.id', '=', 'coverage_tracker.filled_by')
            ->select(
                'coverage_tracker.id',
                'coverage_tracker.Schedule_Time',
                'coverage_tracker.Hours',
                'coverage_tracker.Service',
                'coverage_tracker.Discipline',
                'coverage_tracker.Payer',
                "mssql_patients.payer_group",
                'coverage_tracker.aoc_patient_id',
                DB::raw(self::PATIENTNAME),
                DB::raw(self::CCC),
                DB::raw("DATE_FORMAT(coverage_tracker.shift_date,'%m/%d/%Y') AS shift_date"),DB::raw("'No' as IsPriority")
            );
            $bucket->where('coverage_tracker.intake','Yes');
            $bucket->whereDate("coverage_tracker.shift_date", ">=",$today);
            $bucket->whereNull('completed_by');
            if(isset($request['filters'])) {
                $branch = [];
                $state = [];
                $users = [];
                $payer = [];
                $payerGroup = [];
                $isMeMode = 0;
                $region = [];

                foreach($request['filters'] as $key => $value){
                    if($value['name']=='intakestate') {
                        $state[] = $value['value'];
                    }
                    if($value['name']=='intakebranch') {
                        $branch[] = $value['value'];
                    }
                    if($value['name']=='intakeregion') {
                        $region[] = $value['value'];
                    }
                    if($value['name']=='intakeclientcare'){
                        $users[] = $value['value'];
                    }
                    if($value['name']=='intakememode') {
                        $isMeMode = 1;
                    }
                    if( $value['name']=='intakepayer'){
                        $payer[] = $value['value'];
                    }
                    if( $value['name']=='intakepayer_group'){
                        $payerGroup[] = $value['value'];
                    }
                }
                if(count($state)>0) {
                    H::ExcelLog(print_r($state, true), 'IntakeClientCoverageShiftsBooked.log');
                    $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
                    H::ExcelLog(print_r($facilities, true), 'IntakeClientCoverageShiftsBooked.log');
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

                if(count($region)>0) {
                    $bucket->where(function($q) use ($region){
                        foreach($region as $b) {
                            $q->orWhere("mssql_facilities.region","=",$b);
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
                if(!empty($users)){
                    $bucket->whereIn("mssql_patients.ccc_id",$users);
                }
                if($isMeMode){
                    $bucket->where("mssql_patients.ccc_id","=",Auth::user()->id);
                }
            }
            H::ExcelLog($bucket->toSql(), 'IntakeClientCoverageShiftsBooked.log');
            if($showCount) {
                return '('.$bucket->count().')';
            }
            return $bucket->get();
        } elseif ($type == 'coverage_shift_without_kantime_clinician') {

            $bucket = DB::table('coverage_tracker')
            ->leftJoin('mssql_providers', 'mssql_providers.id', '=', 'coverage_tracker.aoc_provider_id')
            ->leftJoin('mssql_patients', 'mssql_patients.id', '=', 'coverage_tracker.aoc_patient_id')
            ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_patients.FacilityID')
            ->leftJoin('users', 'users.id', '=', 'coverage_tracker.filled_by')
            ->select(
                'coverage_tracker.id',
                DB::raw(self::CCC),
                'mssql_facilities.FacilityName',
                DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),mssql_patients.Patient_LastName) AS patient_name"),
                DB::raw('coverage_tracker.new_clinician_name as Clinician'),
                DB::raw("DATE_FORMAT(coverage_tracker.shift_date,'%m/%d/%Y') AS shift_date"),
                'mssql_patients.Payer',
                "mssql_patients.payer_group"
            );
            if(LD::checkFeatureFlag('client-not-kantime-coverageshift-without-kantime-clinician')) {
                $bucket->where("mssql_patients.not_in_kantime",0);
            }

            if(isset($request['filters'])) {
                $branch = [];
                $state = [];
                $region = [];
                $users = [];
                $payer = [];
                $payerGroup=[];
                $isMeMode = 0;
                if(isset($request['filters'])) {
                    foreach($request['filters'] as $key => $value){
                        if($value['name']=='ccstate') {
                            $state[] = $value['value'];
                        }
                        if($value['name']=='ccbranch') {
                            $branch[] = $value['value'];
                        }
                        if($value['name']=='ccregion') {
                            $region[] = $value['value'];
                        }
                        if($value['name']=='clientcare'){
                            $users[] = $value['value'];
                        }
                        if($value['name']=='exPayer' && $value['value'] != ''){
                            $payer[] = $value['value'];
                        }
                        if( $value['name']=='ex_payer_group'  && $value['value'] != ''){
                            $payerGroup[] = $value['value'];
                        }
                        if($value['name']=='ccdefault') {
                            $isMeMode = 1;
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
                if(count($region)>0) {
                    $bucket->where(function($q) use ($region){
                        foreach($region as $b) {
                            $q->orWhere("mssql_facilities.region","=",$b);
                        }
                    });
                }
                if(!empty($users) && !$isMeMode){
                    $bucket->whereIn("coverage_tracker.filled_by",$users);
                } else if($isMeMode) {
                    $bucket->where("coverage_tracker.filled_by","=",Auth::user()->id);
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
            }
            $bucket->where(function($q){
                $q->where("coverage_tracker.aoc_provider_id",'')->orWhereNull("coverage_tracker.aoc_provider_id");
            });
            $bucket->where('coverage_tracker.shift_date','>','2023-12-01');
            H::ExcelLog($bucket->toSql(), 'CoverageShiftwithoutKanTimeClinician.log');
            if($showCount) {
                return '('.$bucket->count().')';
            }
            return $bucket->get();
        } elseif ($type == 'clients_without_kantime_match') {
            $bucket = DB::table("mssql_patients")
            ->select(
                'mssql_patients.id',
                DB::raw(self::PATIENTNAME),
                'mssql_patients.PatientAccount',
                'mssql_facilities.FacilityName',
                'mssql_patientstatus.PatientStatus'
            );
            $bucket->leftJoin("mssql_facilities",function($query){
                $query->on("mssql_facilities.id","=","mssql_patients.aoc_facility_id");
            });
            $bucket->leftJoin("mssql_patientstatus", function($query){
                $query->on("mssql_patients.Patient_CurrentStatusID","=","mssql_patientstatus.PatientStatusID");
            });
            $bucket->whereNull("mssql_patients.PatientID");
            $bucket->whereNull("mssql_patients.IntakeID");
            if(isset($request['filters'])) {
                $branch = [];
                $state = [];
                $region = [];
                $users = [];
                $payer = [];
                $payerGroup=[];
                $isMeMode = 0;
                if (isset($request['filters'])) {
                    foreach($request['filters'] as $key => $value){
                        if($value['name']=='ccstate') {
                            $state[] = $value['value'];
                        }
                        if($value['name']=='ccbranch') {
                            $branch[] = $value['value'];
                        }
                        if($value['name']=='ccregion') {
                            $region[] = $value['value'];
                        }
                        if($value['name']=='clientcare'){
                            $users[] = $value['value'];
                        }
                        if($value['name']=='exPayer' && $value['value'] != ''){
                            $payer[] = $value['value'];
                        }
                        if( $value['name']=='ex_payer_group'  && $value['value'] != ''){
                            $payerGroup[] = $value['value'];
                        }
                        if($value['name']=='ccdefault') {
                            $isMeMode = 1;
                        }
                    }
                }
                if (!empty($state)) {
                    $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
                    $bucket->where(function($q) use ($facilities){
                        foreach($facilities as $facility) {
                            $q->orWhere("mssql_patients.aoc_facility_id","=",$facility->id);
                        }
                    });
                }
                if (!empty($branch)) {
                    $bucket->where(function($q) use ($branch){
                        foreach($branch as $b) {
                            $q->orWhere("mssql_patients.aoc_facility_id","=",$b);
                        }
                    });
                }
                if (!empty($region)) {
                    $bucket->where(function($q) use ($region){
                        foreach($region as $b) {
                            $q->orWhere("mssql_facilities.region","=",$b);
                        }
                    });
                }

                if (!empty($users)) {
                    $bucket->whereIn("mssql_patients.ccc_id",$users);
                }

                if ($isMeMode) {
                    $bucket->where("mssql_patients.ccc_id","=",Auth::user()->id);
                }

                if (!empty($payer)) {
                    $bucket->where(function($q) use ($payer){
                        foreach($payer as $t) {
                            $q->orWhere("mssql_patients.Payer","=",$t);
                        }
                    });
                }

                if (!empty($payerGroup)) {
                    $bucket->where(function($q) use ($payerGroup){
                        foreach($payerGroup as $t) {
                            $q->orWhere("mssql_patients.payer_group","=",$t);
                        }
                    });
                }
            }
            if($showCount) {
                return '('.$bucket->count().')';
            }
            return $bucket->get();
        } elseif ($type == 'active_coverage_exception') {
            return $this->ActiveCoverageException($request, $showCount);
        } else {

                $bucket = DB::table("NeedToHire_Accounts");
                if (LD::checkFeatureFlag('add-color-highlights-ccc-exception-bucket')) {
                    $bucket->select(
                        'NeedToHire_Accounts.*',
                        'mssql_patients.PatientAccount',
                        'mssql_patients.exception_addressed_date',
                        'mssql_facilities.FacilityName',
                        'mssql_patientstatus.PatientStatus',
                        DB::raw(self::CCC),
                    DB::raw("DATEDIFF(now(),NeedToHire_Accounts.CreatedOn) AS days_since_created"),
                    DB::raw("LENGTH(CF_TheSale) AS character_count"),
                    DB::raw(self::PATIENTNAME),
                    DB::raw("ROUND((select sum(ShiftHours) from NeedToHire_Schedules where NeedToHire_Accounts.id = NeedToHire_Schedules.aoc_nthaccounts_id and NeedToHire_Schedules.IsActive=1),2) as totalhours"),
                    DB::raw("(IFNULL(mssql_patients.AuthorizedWeeklyHours,0)+IFNULL(mssql_patients.CF_RespWeeklyHrs,0)+IFNULL(mssql_patients.CNAAuthorizedWeeklyHours,0)) as totalauthhours"));
                } else {
                    $bucket->select(
                        'NeedToHire_Accounts.*',
                        'mssql_patients.PatientAccount',
                        'mssql_facilities.FacilityName',
                        'mssql_patientstatus.PatientStatus',
                        DB::raw(self::CCC),
                    DB::raw("DATEDIFF(now(),NeedToHire_Accounts.CreatedOn) AS days_since_created"),
                    DB::raw("LENGTH(CF_TheSale) AS character_count"),
                    DB::raw(self::PATIENTNAME),
                    DB::raw("ROUND((select sum(ShiftHours) from NeedToHire_Schedules where NeedToHire_Accounts.id = NeedToHire_Schedules.aoc_nthaccounts_id and NeedToHire_Schedules.IsActive=1),2) as totalhours"),
                    DB::raw("(IFNULL(mssql_patients.AuthorizedWeeklyHours,0)+IFNULL(mssql_patients.CF_RespWeeklyHrs,0)+IFNULL(mssql_patients.CNAAuthorizedWeeklyHours,0)) as totalauthhours"));
                }

                $bucket->leftJoin("mssql_patients",function($query){
                    $query->on("mssql_patients.id","=","NeedToHire_Accounts.aoc_patient_id");
                });

                $bucket->leftJoin("mssql_facilities",function($query){
                    $query->on("mssql_patients.aoc_facility_id","=","mssql_facilities.id");
                });

                $bucket->leftJoin("mssql_patientstatus", function($query){
                    $query->on("mssql_patients.Patient_CurrentStatusID","=","mssql_patientstatus.PatientStatusID");
                });

                $branch = [];
                $state = [];
                $region = [];
                $users = [];
                $payer = [];
                $payerGroup=[];
                $isMeMode = 0;


                if(isset($request['filters'])) {

                    foreach($request['filters'] as $key => $value){
                        if($value['name']=='ccstate' || $value['name']=='weeklyccstate' || $value['name']=='monthlyccstate') {
                            $state[] = $value['value'];
                        }
                        if($value['name']=='ccbranch' || $value['name']=='weeklyccbranch'  || $value['name']=='monthlyccbranch') {
                            $branch[] = $value['value'];
                        }
                        if($value['name']=='ccregion' || $value['name']=='weeklyregion' || $value['name']=='monthlyccregion'  ) {

                            $region[] = $value['value'];
                        }
                        if($value['name']=='clientcare' || $value['name']=='weeklyclientcare'  || $value['name']=='monthlyclientcare'){
                            $users[] = $value['value'];
                        }
                        if( $value['name']=='weeklypayer' ||  $value['name']=='monthlypayer'){
                            $payer[] = $value['value'];
                        }
                        if( $value['name']=='weeklypayergroup'  || $value['name']=='monthlypayergroup'){
                            $payerGroup[] = $value['value'];
                        }
                        if(LD::checkFeatureFlag('payer-filters-on-exception-bucket')){
                            if($value['name']=='exPayer' && $value['value'] != ''){
                                $payer[] = $value['value'];
                            }
                            if( $value['name']=='ex_payer_group'  && $value['value'] != ''){
                                $payerGroup[] = $value['value'];
                            }
                        }
                        if($value['name']=='ccdefault') {
                            $isMeMode = 1;
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

                if(count($region)>0) {
                    $bucket->where(function($q) use ($region){
                        foreach($region as $b) {
                            $q->orWhere("mssql_facilities.region","=",$b);
                        }
                    });

                }

                if(!empty($users) && !$isMeMode){
                    $bucket->whereIn("mssql_patients.ccc_id",$users);
                } else if($isMeMode) {
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

                $bucket->leftJoin("users",function($query){
                    $query->on("users.id","=","mssql_patients.ccc_id");
                });

                $bucket->where("NeedToHire_Accounts.isActive",'1');
                if($type != 'weekly_nth_calls') {
                    $bucket->whereIn("mssql_patients.Patient_CurrentStatusID",[1,6,8]);
                }

                $bucket->addSelect("mssql_patients.Payer","mssql_patients.payer_group","mssql_patients.CF_ServicesNeeded","mssql_patients.last_monthly_followup");
                if($type=='weekly_nth_calls') {
                    $bucket->addSelect('NeedToHire_Accounts.outcome');
                    if(isset($request['filters'][0]['name']) && $request['filters'][0]['name']=='weeklymemode') {
                        $bucket->where("mssql_patients.ccc_id",'=', Auth::user()->id);
                    }
                    $fday = date('Y-m-d', strtotime("this week"));
                    $bucket->where("NeedToHire_Accounts.created_at","<",$fday.' 00:00:00');
                    if(!LD::checkFeatureFlag('weekly-nth-bucket-ccr-show-all-excelssonly')) {
                        $bucket->where(function($query) use($fday){
                            $query->where('last_weekly_followup', '<', $fday.' 00:00:00')->orWhere('last_weekly_followup', '=', '0000-00-00 00:00:00')->orWhereNull("last_weekly_followup");
                        });
                    }

                    $bucket->whereIn("mssql_patients.Patient_CurrentStatusID",[1,6,7,8]);
                    if(!LD::checkFeatureFlag('weekly-nth-bucket-ccr-show-all-excelssonly')) {
                        $bucket->where(function($q){
                            $q->where("mssql_patients.outcome",'!=','Completed')->orWhereNull("mssql_patients.outcome");
                        });
                        $bucket->where('NeedToHire_Accounts.attempt_count', '<', 2);
                    }

                    $bucket->whereRaw("(mssql_facilities.State <> 'CO' OR mssql_patients.LOBID = 1023)");
                } else if($type=='monthly_calls'){
                        $exceptLineOfBusiness = ['PT','OT','ST'];
                        if(LD::checkFeatureFlag('ccc_monthly_call_remove_att')) {
                            array_push($exceptLineOfBusiness,'Attendant Care');
                        }
                        $bucket = DB::table("mssql_patients");
                        $bucket->select('mssql_patients.*','mssql_facilities.FacilityName',DB::raw(self::CCC),
                        DB::raw(self::PATIENTNAME));
                        $bucket->leftJoin("mssql_facilities",function($query){
                            $query->on("mssql_patients.aoc_facility_id","=","mssql_facilities.id");
                        });

                        $bucket->leftJoin("users",function($query){
                            $query->on("users.id","=","mssql_patients.ccc_id");
                        });
                        $bucket->leftJoin("lob",function($query){
                            $query->on("lob.LOBID","=","mssql_patients.LOBID");
                        });
                        $bucket->whereNotIn("lob.LOBName",$exceptLineOfBusiness);
                        if(LD::checkFeatureFlag('cc-dashboard-remove-tx-skilled-visits-branch-monthly-calls')) {
                            $bucket->whereNotIn("mssql_patients.aoc_facility_id",['14']);
                        }
                        if(LD::checkFeatureFlag('remove-2-FL-branch-client-from-monthly-call')){
                            $bucket->whereNotIn("mssql_patients.aoc_facility_id",['43','44']);
                        }
                        $bucket->where("isPRN",0);
                        $bucket->whereRaw("mssql_patients.Search_Name not like '%Test%'");
                        $bucket->whereRaw("mssql_patients.Search_Name not like '%KanTime%'");
                        $bucket->whereRaw("mssql_patients.Search_Name not like '%PRN%'");
                        $bucket->whereRaw("(mssql_facilities.State <> 'CO' OR mssql_patients.LOBID = 1023)");
                        $bucket->where(function($q){
                            $q->where('mssql_patients.attempt_count', '!=', '3')->orWhereNull('mssql_patients.attempt_count');
                        });

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

                         if(count($region)>0) {
                            $bucket->where(function($q) use ($region){
                                foreach($region as $b) {
                                    $q->orWhere("mssql_facilities.region","=",$b);
                                }
                            });

                        }

                        if(!empty($users)){
                            $bucket->whereIn("mssql_patients.ccc_id",$users);
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

                        if(isset($request['filters'][0]['name']) && $request['filters'][0]['name']=='monthlymemode') {
                            $bucket->where("mssql_patients.ccc_id",'=', Auth::user()->id);
                        }
                        $fday = date("Y-m-01 00:00:00");
                        $bucket->where("mssql_patients.created_at","<",$fday);

                        $bucket->where(function($q) use($fday) {
                            $q->where('mssql_patients.last_monthly_followup', '<', $fday)->orWhere('mssql_patients.last_monthly_followup', '=', '0000-00-00 00:00:00')->orWhereNull("last_monthly_followup");

                        });
                    $bucket->whereIn("mssql_patients.Patient_CurrentStatusID",[1,6,7,8]);
                    $bucket->where("mssql_patients.IsNonAdmit",'<>',1);
                    $bucket->where(function($q){
                        $q->where("mssql_patients.outcome",'!=','Completed')->orWhereNull("mssql_patients.outcome");
                    });
                }
                else if($type=='not_meeting_nurses') {
                    $bucket->where("mssql_patients.CF_MeetingNurses",'like', 'No%');
                    $bucket->addSelect(DB::raw('IF(ISNULL(date_not_meeting_nurses), 0, DATEDIFF(now(), date_not_meeting_nurses)) as date_not_meeting_nurses'));
                } else if($type=='gender_preference_mismatch_in_kantime') {
                    $bucket->where("mssql_patients.PreferenceList",'not like', '%Male Nurse Accepted%')->where("mssql_patients.PreferenceList",'like', '%Does NOT Accept Female Nurses%');
                } else if($type=='the_sale_255_characters') {
                    $bucket->whereRaw("LENGTH(CF_TheSale) < 255");
                } else if($type=='nth_hours_exceed_authorized_hours') {
                    if(LD::checkFeatureFlag('client-not-kantime-nthhours-exceed-auth-hours')) {
                        $bucket->where("mssql_patients.not_in_kantime",0);
                    }
                    $bucket->whereRaw("ROUND(IFNULL((select sum(ShiftHours) from NeedToHire_Schedules where NeedToHire_Accounts.id = NeedToHire_Schedules.aoc_nthaccounts_id and NeedToHire_Schedules.IsActive=1), 0),2) > ((IFNULL(mssql_patients.AuthorizedWeeklyHours,0)+IFNULL(mssql_patients.CF_RespWeeklyHrs,0)+IFNULL(mssql_patients.CNAAuthorizedWeeklyHours,0)))");
                }else if($type=='no_match_in_kantime') {
                    $bucket->where(function($q){
                        $q->whereNull("PatientID")->orWhere("PatientID",'=','');
                    });
                    $bucket->where(function($q){
                        $q->whereNull("IntakeID")->orWhere("IntakeID",'=','');
                    });
                } else if($type=='no_hours') {
                    $bucket->havingRaw("totalhours < 0.01 or isnull(totalhours)=1");
                } else if($type=='no_payor') {
                    $bucket->where("NeedToHire_Accounts.ServicesNeeded","!=","Respite");
                    $bucket->where(function($q){
                        $q->whereNull("Payer")->orWhere("Payer",'=','');
                    });
                } else if($type=='intake_no_intake_coordinator') {
                    $bucket->where(function($q){
                        $q->whereNull("ic_id")->orWhere("ic_id",'=','');
                    });
                } else if($type=='no_ccc') {
                    $bucket->where(function($q){
                        $q->whereNull("ccc_id")->orWhere("ccc_id",'=','');
                    });

                } else if($type=='ccc_respite_no_respite_payor') {
                    $bucket->where("NeedToHire_Accounts.ServicesNeeded",'=',"Respite");
                    $bucket->whereRaw("ifnull(mssql_patients.RespitePayer,'') = ''");
                }else if($type=='no_services_needed') {
                    $bucket->where("mssql_patients.CF_ServicesNeeded",'=','');
                }
                if($showCount) {
                    return '('.$bucket->count().')';
                }
                H::ExcelLog('ClientCareCoordinatorException: ' . $bucket->toSql(),'ClientCareCoordinatorException.log');
                return $bucket->get();

        }

    }

    public function getIntakeCoverageShiftCoveredPermNurse($dashboard_type, $request = '',$showCount=0) {
        $userCordinatorJoin = 'coverage_tracker.created_by';
        if($dashboard_type == 'intake') {
            $userCordinatorJoin = 'mssql_patients.ic_id';
        }
        $today = date('Y-m-d');
        $clinicianName = "CONCAT(mssql_providers.FirstName,' ',mssql_providers.LastName) AS Clinician";
        $bucket = DB::table("coverage_tracker")
        ->select("coverage_tracker.id",
        "coverage_tracker.aoc_patient_id",
        DB::raw('IF(
            (
                mssql_patients.CF_GoalSOCDate IS NULL or
                mssql_patients.CF_GoalSOCDate = \'\' or
                mssql_patients.CF_GoalSOCDate = \'0000-00-00 00:00:00\'), \'\',
                DATE_FORMAT(mssql_patients.CF_GoalSOCDate,"'.config('constant')['mysqlsearchdateformat'].'")
            )
        as CF_GoalSOCDate'),
        DB::raw(self::CCC),
        DB::raw(self::PATIENTNAME),
        DB::raw("DATE_FORMAT(coverage_tracker.shift_date,'%m/%d/%Y') AS shift_date"),
        'coverage_tracker.Schedule_Time',
        'mssql_patients.Payer',
        DB::raw($clinicianName),
        'coverage_tracker.escalated_pay_rate');
        $bucket->leftJoin("mssql_patients", function($query) {
            $query->on("coverage_tracker.aoc_patient_id", "=", "mssql_patients.id");
        });
        $bucket->leftJoin("mssql_facilities", function($query) {
            $query->on("mssql_facilities.id", "=", "mssql_patients.aoc_facility_id");
        });
        $bucket->leftJoin("users",function($query) use($userCordinatorJoin){
            $query->on("users.id", "=", $userCordinatorJoin);
        });
        $bucket->leftJoin("mssql_providers",function($query){
            $query->on("mssql_providers.id","=","coverage_tracker.aoc_provider_id");
        });
        $bucket->where('coverage_tracker.perm_filled',1);
        $bucket->where(function($q) use($today) {
            $q->where("coverage_tracker.intake_addressed_at",'<',$today)
            ->orWhereNull('coverage_tracker.intake_addressed_at');
        });
        if(isset($request['filters'])) {
            $branch = [];
            $state = [];
            $region = [];
            $users = [];
            $payer = [];
            $payerGroup=[];
            $isMeMode = 0;
            if(isset($request['filters'])) {
                foreach($request['filters'] as $key => $value){
                    if($value['name']=='ccstate' || $value['name']=='intakeexceptionstate') {
                        $state[] = $value['value'];
                    }
                    if($value['name']=='ccbranch' || $value['name']=='intakeexceptionbranch') {
                        $branch[] = $value['value'];
                    }
                    if($value['name']=='ccregion'  || $value['name']=='intakeexceptionregion') {
                        $region[] = $value['value'];
                    }
                    if($value['name']=='clientcare' || $value['name']=='intakeexceptioncare'){
                        $users[] = $value['value'];
                    }
                    if($value['name']=='exPayer'  || $value['name']=='intakeexceptionPayer'){
                        $payer[] = $value['value'];
                    }
                    if( $value['name']=='ex_payer_group'  || $value['name']=='intakeexceptionspecialpayergroup'){
                        $payerGroup[] = $value['value'];
                    }
                    if($value['name']=='ccdefault'  || $value['name']=='intakeexceptiondefault') {
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
            if(!empty($region)) {
                $bucket->where(function($q) use ($region){
                    foreach($region as $b) {
                        $q->orWhere("mssql_facilities.region","=",$b);
                    }
                });
            }

            if(!empty($users) && !$isMeMode){
                $bucket->whereIn($userCordinatorJoin,$users);
            } elseif($isMeMode) {
                $bucket->where($userCordinatorJoin,"=",Auth::user()->id);
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
        }
        H::ExcelLog($dashboard_type.'=='.$bucket->toSql(), 'getIntakeCoverageShiftCoveredPermNurse.log');
        if($showCount) {
            return '('.$bucket->count().')';
        }
        H::ExcelLog($dashboard_type.'=Bucket=='.$bucket->toSql(), 'getIntakeCoverageShiftCoveredPermNurse.log');
        return $bucket->get();
    }

    public function getIntakeCoverageShiftBeforeGoalSocDate($dashboard_type, $request = '',$showCount=0) {
        $userCordinatorJoin = 'coverage_tracker.created_by';
        if($dashboard_type == 'intake') {
            $userCordinatorJoin = 'mssql_patients.ic_id';
        }
        $today = date('Y-m-d');
        $clinicianName = "CONCAT(mssql_providers.FirstName,' ',mssql_providers.LastName) AS Clinician";
        $bucket = DB::table("coverage_tracker")
        ->select("coverage_tracker.id",
        "coverage_tracker.aoc_patient_id",
        DB::raw('IF(
            (
                mssql_patients.CF_GoalSOCDate IS NULL or
                mssql_patients.CF_GoalSOCDate = \'\' or
                mssql_patients.CF_GoalSOCDate = \'0000-00-00 00:00:00\'), \'\',
                DATE_FORMAT(mssql_patients.CF_GoalSOCDate,"'.config('constant')['mysqlsearchdateformat'].'")
            )
        as CF_GoalSOCDate'),
        DB::raw(self::CCC),
        DB::raw(self::PATIENTNAME),
        DB::raw("DATE_FORMAT(coverage_tracker.shift_date,'%m/%d/%Y') AS shift_date"),
        'coverage_tracker.Schedule_Time',
        'mssql_patients.Payer',
        DB::raw($clinicianName),
        'coverage_tracker.escalated_pay_rate');
        $bucket->leftJoin("mssql_patients", function($query) {
            $query->on("coverage_tracker.aoc_patient_id", "=", "mssql_patients.id");
        });
        $bucket->leftJoin("mssql_facilities", function($query) {
            $query->on("mssql_facilities.id", "=", "mssql_patients.aoc_facility_id");
        });
        $bucket->leftJoin("users",function($query) use($userCordinatorJoin) {
            $query->on("users.id", "=", $userCordinatorJoin);
        });
        $bucket->leftJoin("mssql_providers",function($query){
            $query->on("mssql_providers.id","=","coverage_tracker.aoc_provider_id");
        });
        $bucket->whereRaw('coverage_tracker.shift_date < cast(mssql_patients.CF_GoalSOCDate as date)');
        $bucket->where("coverage_tracker.shift_date",">=",$today);
        $bucket->where(function($q) use($today) {
            $q->where("coverage_tracker.intake_addressed_at",'<',$today)
            ->orWhereNull('coverage_tracker.intake_addressed_at');
        });
        if(isset($request['filters'])) {
            $branch = [];
            $state = [];
            $region = [];
            $users = [];
            $payer = [];
            $payerGroup=[];
            $isMeMode = 0;
            if(isset($request['filters'])) {
                foreach($request['filters'] as $key => $value){
                    if($value['name']=='ccstate' || $value['name']=='intakeexceptionstate') {
                        $state[] = $value['value'];
                    }
                    if($value['name']=='ccbranch' || $value['name']=='intakeexceptionbranch') {
                        $branch[] = $value['value'];
                    }
                    if($value['name']=='ccregion'  || $value['name']=='intakeexceptionregion') {
                        $region[] = $value['value'];
                    }
                    if($value['name']=='clientcare' || $value['name']=='intakeexceptioncare'){
                        $users[] = $value['value'];
                    }
                    if($value['name']=='exPayer'  || $value['name']=='intakeexceptionPayer'){
                        $payer[] = $value['value'];
                    }
                    if( $value['name']=='ex_payer_group'  || $value['name']=='intakeexceptionspecialpayergroup'){
                        $payerGroup[] = $value['value'];
                    }
                    if($value['name']=='ccdefault'  || $value['name']=='intakeexceptiondefault') {
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
            if(!empty($region)) {
                $bucket->where(function($q) use ($region){
                    foreach($region as $b) {
                        $q->orWhere("mssql_facilities.region","=",$b);
                    }
                });
            }

            if(!empty($users) && !$isMeMode){
                $bucket->whereIn($userCordinatorJoin,$users);
            } elseif($isMeMode) {
                $bucket->where($userCordinatorJoin,"=",Auth::user()->id);
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
        }
        H::ExcelLog($dashboard_type.'=='.$bucket->toSql(), 'getIntakeCoverageShiftBeforeGoalSocDate.log');
        if($showCount) {
            return '('.$bucket->count().')';
        }
        H::ExcelLog($dashboard_type.'=Bucket=='.$bucket->toSql(), 'getIntakeCoverageShiftBeforeGoalSocDate.log');
        return $bucket->get();
    }

    public function getClientCareWeeklyCall($request = '',$showCount=0) {
        $fday = H::getMondayFromDate(date('Y-m-d'),0);
        $bucket = DB::table("ccc_followup_calls");
        $bucket->select('ccc_followup_calls.*',
        DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName)
        AS patient_name"),
        'mssql_patients.PatientAccount',
        DB::raw(self::CCC),
        'mssql_facilities.FacilityName',
        "mssql_patients.Payer",
        'mssql_patients.CF_ServicesNeeded',
        'mssql_patientstatus.PatientStatus',
        DB::raw("ROUND((select sum(ShiftHours)
        from NeedToHire_Schedules
        where ccc_followup_calls.aoc_nthaccount_id = NeedToHire_Schedules.aoc_nthaccounts_id
        and NeedToHire_Schedules.IsActive=1),2) as totalhours"));

        $bucket->leftJoin("mssql_patients",function($query){
            $query->on("mssql_patients.id","=","ccc_followup_calls.aoc_patient_id");
        });
		$bucket->leftJoin("mssql_facilities",function($query){
            $query->on("mssql_patients.aoc_facility_id","=","mssql_facilities.id");
        });
        $bucket->leftJoin("users",function($query){
            $query->on("users.id","=","mssql_patients.ccc_id");
        });
        $bucket->leftJoin("mssql_patientstatus", function($query){
            $query->on("mssql_patients.Patient_CurrentStatusID","=","mssql_patientstatus.PatientStatusID");
        });
        if(LD::checkFeatureFlag('add-lob-filter-in-ccc-follow-up-calls')){
            $bucket->addSelect('mssql_patients.LOBID');
            $bucket->leftJoin("lob", function($query){
                $query->on("mssql_patients.LOBID","=","lob.LOBID");
            });
        }
		$bucket->where("mssql_patients.Patient_CurrentStatusID","<>",7);
        $bucket->where("ccc_followup_calls.type","=",'nth');
        $bucket->where("ccc_followup_calls.week_month_start",$fday);
        $bucket->where(function($query) {
            $query->whereNotIn("ccc_followup_calls.outcome",['Completed','Not Complete'])
            ->orWhereNull("ccc_followup_calls.outcome");
        });
        $bucket->where(function($query) {
            $query->where('ccc_followup_calls.attempt_count', '<', 2)
            ->orWhereNull("ccc_followup_calls.attempt_count");
        });
        //Setting the filters
        $this->setFilters($bucket,$request['filters']);
        if($showCount) {
            H::ExcelLog('getClientCareWeeklyCall Count: ' . $bucket->toSql(),'getClientCareWeeklyCall.log');
            return '('.$bucket->count().')';
        }
        H::ExcelLog('getClientCareWeeklyCall: ' . $bucket->toSql(),'getClientCareWeeklyCall.log');
        return $bucket->get();
    }

    public function getClientCareMonthlyCall($request = '',$showCount=0) {
        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $weekStart  = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();

        $bucket = DB::table("ccc_followup_calls");
        $bucket->select('ccc_followup_calls.*',
        DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName)
        AS patient_name"),
        'mssql_patients.PatientAccount',
        DB::raw(self::CCC),
        DB::raw("'No' as IsPriority"),
        'mssql_facilities.FacilityName',
        "mssql_patients.Payer",
        "mssql_patients.payer_group",
        'mssql_patientstatus.PatientStatus',
        DB::raw("ROUND(IFNULL((select sum(ShiftHours)
        from NeedToHire_Schedules
        where ccc_followup_calls.aoc_nthaccount_id = NeedToHire_Schedules.aoc_nthaccounts_id
        and NeedToHire_Schedules.IsActive=1),0),2) as totalhours"));

        $bucket->leftJoin("mssql_patients",function($query){
            $query->on("mssql_patients.id","=","ccc_followup_calls.aoc_patient_id");
        });
		$bucket->leftJoin("mssql_facilities",function($query){
            $query->on("mssql_patients.aoc_facility_id","=","mssql_facilities.id");
        });

        $bucket->leftJoin("users",function($query){
            $query->on("users.id","=","mssql_patients.ccc_id");
        });
        $bucket->leftJoin("mssql_patientstatus", function($query){
            $query->on("mssql_patients.Patient_CurrentStatusID","=","mssql_patientstatus.PatientStatusID");
        });
        if(LD::checkFeatureFlag('add-lob-filter-in-ccc-follow-up-calls')){
            $bucket->addSelect('mssql_patients.LOBID');
            $bucket->leftJoin("lob", function($query){
                $query->on("mssql_patients.LOBID","=","lob.LOBID");
            });
        }
        $bucket->where("ccc_followup_calls.type","=",'client');
        $bucket->where(function($q) use ($monthStart, $weekStart) {
            $q->where("ccc_followup_calls.week_month_start", $monthStart)
                ->orWhere("ccc_followup_calls.week_month_start", $weekStart);
        });
        $bucket->where(function($query) {
            $query->whereNotIn("ccc_followup_calls.outcome",['Completed','Not Complete'])
            ->orWhereNull("ccc_followup_calls.outcome");
        });
        $bucket->where(function($query) {
            $query->where('ccc_followup_calls.attempt_count', '!=', '3')
            ->orWhereNull("ccc_followup_calls.attempt_count");
        });
        if(LD::checkFeatureFlag('ccc-monthly-calls-exclude-non-admit-discharged-inactive')){
            $bucket->whereNotIn('mssql_patients.Patient_CurrentStatusID', [2, 3, 5]);
        }
        //Setting the filters
        $this->setFilters($bucket,$request['filters']);
        if($showCount) {
            return '('.$bucket->count().')';
        }
        H::ExcelLog('getClientCareMonthlyCall: ' . $bucket->toSql(),'getClientCareMonthlyCall.log');
        return $bucket->get();
    }

    private function setFilters(&$bucket, $searchfilter)
    {
        $filters = $this->extractFilters($searchfilter);
        foreach ($filters as $key => $values) {
            switch ($key) {
                case 'weeklymemode':
                case 'monthlymemode':
                    $bucket->where("mssql_patients.ccc_id","=",Auth::user()->id);
                    break;
                case 'weeklyccstate':
                case 'monthlyccstate':
                    $this->applyFacilityFilter($bucket, 'mssql_patients.aoc_facility_id', $values);
                    break;
                case 'weeklyccbranch':
                case 'monthlyccbranch':
                    $this->applyFilter($bucket, 'mssql_patients.aoc_facility_id', $values);
                    break;
                case 'weeklyregion':
                case 'monthlyccregion':
                    $this->applyFilter($bucket, 'mssql_facilities.region', $values);
                    break;
                case 'weeklyclientcare':
                case 'monthlyclientcare':
                    $this->applyFilter($bucket, 'mssql_patients.ccc_id', $values);
                    break;
                case 'weeklypayer':
                case 'monthlypayer':
                    $this->applyFilter($bucket, 'mssql_patients.Payer', $values);
                    break;
                case 'weeklypayergroup':
                case 'monthlypayergroup':
                    $this->applyFilter($bucket, 'mssql_patients.payer_group', $values);
                    break;
                case 'weeklyLOB':
                case 'monthlyLOB':
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

    public function getCCCWeeklyProgressBar($request){
        $progress = 0;
        $total = 0;
        $fday = H::getMondayFromDate(date('Y-m-d'),0);
        $bucket = DB::table("ccc_followup_calls");
        $bucket->leftJoin("mssql_patients",function($query){
            $query->on("mssql_patients.id","=","ccc_followup_calls.aoc_patient_id");
        });

        $bucket->leftJoin("mssql_facilities",function($query){
            $query->on("mssql_patients.aoc_facility_id","=","mssql_facilities.id");
        });

        $bucket->leftJoin("users",function($query){
            $query->on("users.id","=","mssql_patients.ccc_id");
        });
        $bucket->leftJoin("mssql_patientstatus", function($query){
            $query->on("mssql_patients.Patient_CurrentStatusID","=","mssql_patientstatus.PatientStatusID");
        });
        if(LD::checkFeatureFlag('add-lob-filter-in-ccc-follow-up-calls')){
            $bucket->addSelect('mssql_patients.LOBID');
            $bucket->leftJoin("lob", function($query){
                $query->on("mssql_patients.LOBID","=","lob.LOBID");
            });
        }
		$bucket->where("mssql_patients.Patient_CurrentStatusID","<>",7);
        $bucket->where("ccc_followup_calls.type","=",'nth');
        $bucket->where("ccc_followup_calls.week_month_start",$fday);
        //Setting the filters
        if(!empty($request['filters'])) {
            $this->setFilters($bucket,$request['filters']);
        }
        $progressQuery = $totalQuery = $bucket;

        $total = $totalQuery->count();
        H::ExcelLog('getCCCWeeklyProgressBar Total  Query: ' . $totalQuery->toSql(),'getCCCWeeklyProgressBar.log');
        $progressQuery->where(function($query) {
            $query->whereIn("ccc_followup_calls.outcome",['Completed','Not Complete']);
            $query->orWhere('ccc_followup_calls.attempt_count', '>=', 2);
        });
        $progress = $progressQuery->count();
        H::ExcelLog('getCCCWeeklyProgressBar Progress Query: '.
        $progressQuery->toSql(),'getCCCWeeklyProgressBar.log');
        if($total>0) {
            H::ExcelLog('Weekly calls ProgressBar progress count : ' . $progress,'getCCCWeeklyProgressBar.log');
            H::ExcelLog('Weekly calls ProgressBar total count : ' . $total,'getCCCWeeklyProgressBar.log');
            $percentage = round(($progress/$total)*100,0);
            H::ExcelLog('Weekly calls ProgressBar percentage : ' . $percentage,'getCCCWeeklyProgressBar.log');
            return [
                "html" => '<div class="progress"><div class="progress-bar" role="progressbar"
                style="width: '.$percentage.'%;" aria-valuenow="'.$percentage.'"
                aria-valuemin="0" aria-valuemax="100">'.$percentage.'%</div></div>',
                "percentage" => $percentage
            ];
        } else {
            $percentage = 0;
            return ["html" => '', "percentage" => $percentage];
        }
    }

    public function getCCCMonthlyProgressBar($request){
        $progress = 0;
        $total = 0;

        $today = Carbon::now();
        $monthStart = $today->startOfMonth()->toDateString();
        $weekStart  = $today->startOfWeek(Carbon::MONDAY)->toDateString();

        $bucket = DB::table("ccc_followup_calls");
        $bucket->leftJoin("mssql_patients",function($query){
            $query->on("mssql_patients.id","=","ccc_followup_calls.aoc_patient_id");
        });

        $bucket->leftJoin("mssql_facilities",function($query){
            $query->on("mssql_patients.aoc_facility_id","=","mssql_facilities.id");
        });

        $bucket->leftJoin("users",function($query){
            $query->on("users.id","=","mssql_patients.ccc_id");
        });
        $bucket->leftJoin("mssql_patientstatus", function($query){
            $query->on("mssql_patients.Patient_CurrentStatusID","=","mssql_patientstatus.PatientStatusID");
        });
        if(LD::checkFeatureFlag('add-lob-filter-in-ccc-follow-up-calls')){
            $bucket->addSelect('mssql_patients.LOBID');
            $bucket->leftJoin("lob", function($query){
                $query->on("mssql_patients.LOBID","=","lob.LOBID");
            });
        }
        $bucket->where("ccc_followup_calls.type","=",'client');
        $bucket->where(function($q) use ($monthStart, $weekStart) {
            $q->where("ccc_followup_calls.week_month_start", $monthStart)
                ->orWhere("ccc_followup_calls.week_month_start", $weekStart);
        });
        //Setting the filters
        if(!empty($request['filters'])) {
            $this->setFilters($bucket,$request['filters']);
        }
        if(LD::checkFeatureFlag('ccc-monthly-calls-exclude-non-admit-discharged-inactive')){
            $bucket->whereNotIn('mssql_patients.Patient_CurrentStatusID', [2, 3, 5]);
        }
        $progressQuery = $totalQuery = $bucket;

        $total = $totalQuery->count();
        H::ExcelLog('getCCCMonthlyProgressBar Total  Query: ' . $totalQuery->toSql(),'getCCCMonthlyProgressBar.log');

        $progressQuery->where(function($query) {
            $query->whereIn("ccc_followup_calls.outcome",['Completed','Not Complete']);
            $query->orWhere('ccc_followup_calls.attempt_count', '=', '3');
        });
        $progress = $progressQuery->count();
        H::ExcelLog('getCCCMonthlyProgressBar Progress Query: '.
        $progressQuery->toSql(),'getCCCMonthlyProgressBar.log');
        if($total>0) {
            H::ExcelLog('monthly calls ProgressBar progress count : ' . $progress,'getCCCMonthlyProgressBar.log');
            H::ExcelLog('monthly calls ProgressBar total count : ' . $total,'getCCCMonthlyProgressBar.log');
            $percentage = round(($progress/$total)*100,0);
            H::ExcelLog('monthly calls ProgressBar percentage : ' . $percentage,'getCCCMonthlyProgressBar.log');
            return [
                "html" => '<div class="progress"><div class="progress-bar" role="progressbar"
                style="width: '.$percentage.'%;" aria-valuenow="'.$percentage.'"
                aria-valuemin="0" aria-valuemax="100">'.$percentage.'%</div></div>',
                "percentage" => $percentage
            ];
        } else {
            $percentage = 0;
            return ["html" => '', "percentage" => $percentage];
        }
    }

    private function ActiveCoverageException ($request, $showCount)
    {
        $bucket = DB::table("mssql_recruitertracker")
        ->select("mssql_recruitertracker.*",
        DB::raw("IF(mssql_recruitertracker.aoc_provider_id IS NULL,
                    CONCAT(candidates.firstName,' ',candidates.lastName),
                    CONCAT(mssql_providers.FirstName,' ',mssql_providers.LastName)
                ) AS clinician_name"),
        DB::raw("IF(IFNULL((select count(*) from mssql_schedulemaster where
                                    mssql_schedulemaster.PatientID = mssql_patients.PatientID AND
                                    mssql_schedulemaster.ProviderID = mssql_providers.ProviderID AND
                                    mssql_schedulemaster.ScheduleStatusID = 5 AND
                                    mssql_schedulemaster.ScheduleDate > CURDATE() AND
                                    mssql_schedulemaster.ScheduleDate <= DATE_ADD(CURDATE(), INTERVAL + 90 DAY)
                                    limit 1
                                ), 0) = 0, 'No Scheduled Shift', activecoverage_outcome)
             AS activecoverage_outcome"),
        DB::raw("format_dates(days_available)
             AS days_available"),
        "users.name as Coverage_Coordinator",
        "mssql_facilities.FacilityName")

        ->leftJoin('candidates', 'candidates.id', '=', 'mssql_recruitertracker.aoc_candidate_id')
        ->leftJoin('mssql_providers', 'mssql_providers.id', '=', 'mssql_recruitertracker.aoc_provider_id')
        ->leftJoin('users', 'users.id', '=', 'mssql_providers.cc_id')
        ->leftJoin('mssql_patients', 'mssql_patients.id', '=', 'mssql_recruitertracker.aoc_patient_id')
        ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_recruitertracker.aoc_facility_id');
        if(LD::checkFeatureFlag('ccc-all-bucket-change')) {

            $bucket->where(function($q) {
                $q->where(function($query){
                    $varNoDateTimeEntry = '0000-00-00 00:00:00';
                    $query->where("mssql_recruitertracker.nurse_status","2");
                    $query->where("mssql_patients.isPRN",1);
                    $query
                    ->whereDate('IPEA_Date', '<=', date('Y-m-d'))
                    ->where('IPEA_Date', '!=', $varNoDateTimeEntry);
                    $query->where('live_ipea_status', 'Ready to work');
                    $query->where("mssql_recruitertracker.activecoverage_outcome", 'Escalated to CCM');

                })->orWhere(function($query) {

                    $query->where("mssql_providers.EmploymentStatusID", '24');
                    $query->where("mssql_recruitertracker.activecoverage_outcome", 'Escalated to CCM');
                });
            });

        }  else {
            $bucket->where(function($query) {
                $query->where("mssql_recruitertracker.nurse_status", '2');
                $query->orWhere("mssql_providers.EmploymentStatusID", '24');
            })
            ->where(function($query) {
                $query->where("mssql_recruitertracker.activecoverage_outcome", 'Escalated to CCM');
                $query->orWhereRaw('IFNULL((select count(*) from mssql_schedulemaster where
                                        mssql_schedulemaster.PatientID = mssql_patients.PatientID AND
                                        mssql_schedulemaster.ProviderID = mssql_providers.ProviderID AND
                                        mssql_schedulemaster.ScheduleStatusID = 5 AND
                                        mssql_schedulemaster.ScheduleDate > CURDATE() AND
                                        mssql_schedulemaster.ScheduleDate <= DATE_ADD(CURDATE(), INTERVAL + 90 DAY)
                                        limit 1
                                    ), 0) = 0
                ');
            });
            if(LD::checkFeatureFlag('ccc-exception-activecoverage-bucket-prn-change')) {
                $bucket->where("mssql_patients.isPRN",1);
            }
        }



        if(isset($request['filters'])) {
            $branch = [];
            $state = [];
            $region = [];
            $users = [];
            $payer = [];
            $payerGroup=[];
            $isMeMode = 0;
            if (isset($request['filters'])) {
                foreach ($request['filters'] as $key => $value) {
                    if ($value['name'] == 'ccstate') {
                        $state[] = $value['value'];
                    }
                    if ($value['name'] == 'ccbranch') {
                        $branch[] = $value['value'];
                    }
                    if ($value['name'] == 'ccregion') {
                        $region[] = $value['value'];
                    }
                    if ($value['name'] == 'clientcare') {
                        $users[] = $value['value'];
                    }
                    if ($value['name'] == 'exPayer' && $value['value'] != '') {
                        $payer[] = $value['value'];
                    }
                    if ($value['name'] == 'ex_payer_group' && $value['value'] != '') {
                        $payerGroup[] = $value['value'];
                    }
                    if ($value['name'] == 'ccdefault') {
                        $isMeMode = 1;
                    }
                }
            }
            if (!empty($state)) {
                $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
                $bucket->where(function ($q) use ($facilities) {
                    foreach ($facilities as $facility) {
                        $q->orWhere("mssql_facilities.id", "=", $facility->id);
                    }
                });
            }
            if (!empty($branch)) {
                $bucket->where(function ($q) use ($branch) {
                    foreach ($branch as $b) {
                        $q->orWhere("mssql_facilities.id", "=", $b);
                    }
                });
            }
            if (!empty($region)) {
                $bucket->where(function ($q) use ($region) {
                    foreach ($region as $b) {
                        $q->orWhere("mssql_facilities.region", "=", $b);
                    }
                });
            }

            if (!empty($users)) {
                $bucket->whereIn("mssql_patients.ccc_id", $users);
            }

            if ($isMeMode) {
                $bucket->where("mssql_patients.ccc_id", "=", Auth::user()->id);
            }

            if (!empty($payer)) {
                $bucket->where(function ($q) use ($payer) {
                    foreach ($payer as $t) {
                        $q->orWhere("mssql_patients.Payer", "=", $t);
                    }
                });
            }

            if (!empty($payerGroup)) {
                $bucket->where(function ($q) use ($payerGroup) {
                    foreach ($payerGroup as $t) {
                        $q->orWhere("mssql_patients.payer_group", "=", $t);
                    }
                });
            }
        }
        if($showCount) {
            return '('.$bucket->count().')';
        }
        return $bucket->get();
    }
}
