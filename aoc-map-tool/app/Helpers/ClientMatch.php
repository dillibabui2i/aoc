<?php
namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\MssqlRecruitertracker;
use App\Helpers\LD;
class ClientMatch
{
    public $buckets = [
        "ccc_client_match"=>["heading"=>"Client Match"]
    ];


    public function getBuckets(){
        return $this->buckets;
    }


    public function getBucketTitle($type) {
        return $this->buckets[$type]['heading'];
    }

    public function getDataTableHtml($type)
    {
        $dataTable = [];
        switch($type) {

            case 'ccc_client_match':
                $dataTable = [
                    'select'=>'Select',
                    'patient_name'=>'Client',
                    'Client_Coordinator'=>"Client Care Coordinator",
                    'FacilityName'=>'Branch',
                    'Clinician'=>'Clinician',
                    'Skillset'=>'Clinician Discipline',
                    'Candidate_Start_Date'=>'Candidate Start Date',
                    'Total_Hours'=>'Total Hours'
                ];
                break;
            default:
                break;
        }
        return view('ccc.clientMatch.datatable',["type"=>$type,'dataTable'=>$dataTable]);
    }

    public function getClientMatchData($type, $request = '',$showCount=0) {
        $request = $request->all();
        if($type=='ccc_client_match') {

            $today = date('Y-m-d');

            $bucket = MssqlRecruitertracker::select("mssql_recruitertracker.*",'mssql_facilities.FacilityName',
            DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"),
            DB::raw("CONCAT(mssql_providers.FirstName,' ',mssql_providers.LastName) AS Clinician"),
            DB::raw("users.name AS Client_Coordinator"),
            DB::raw("CONCAT(candidates.firstName,' ',candidates.lastName) AS Clinician_Candidate"));

            $bucket->leftJoin("mssql_patients",function($query){
                $query->on("mssql_patients.id","=","mssql_recruitertracker.aoc_patient_id");
            });

            $bucket->leftJoin("mssql_providers",function($query){
                $query->on("mssql_providers.id","=","mssql_recruitertracker.aoc_provider_id");
            });

            $bucket->leftJoin("candidates",function($query){
                $query->on("candidates.id","=","mssql_recruitertracker.aoc_candidate_id");
            });
            $bucket->leftJoin("users",function($query){
                $query->on("users.id","=","mssql_patients.ccc_id");
            });

            $bucket->leftJoin("mssql_facilities",function($query){
                $query->on("mssql_facilities.id","=","mssql_recruitertracker.aoc_facility_id");
            });

            $bucket->where(function($query){
                $query->where("Client_Match_Date","!=","0000-00-00 00:00:00")->whereNotNull("Client_Match_Date");
            });
            $bucket->where("Client_Match_Date",">","2023-02-26");
            /**
             * Permanently exclude Client Matches whose Candidate Start Date is on or before
             * 2026-06-30 (pre-training cleanup). Empty / '0000-00-00' / null start dates also
             * fail this comparison and are therefore excluded. Bucket-scoped only; the record
             * stays intact everywhere else in the recruiting system. Applies to future synced
             * records as well, so stale matches never reappear.
             */
            $bucket->whereDate("mssql_recruitertracker.Candidate_Start_Date", ">", "2026-06-30");
            $bucket->where("mssql_patients.Patient_CurrentStatusID","!=","4");
            $bucket->where("mssql_patients.is_deleted","=",0);
            $bucket->where("mssql_recruitertracker.schedules_built",0);

            $bucket->where(function($q){
                $q->whereDate('mssql_recruitertracker.checked_off_at','<',date("Y-m-d"))->orWhereNull("checked_off_at");
            });

            if(LD::checkFeatureFlag('client-match-added-condition-for-nurse-not-working-aoc')) {
                $bucket->where(function($q){
                    $q->where("mssql_recruitertracker.inactive_reason","!=","Nurse decided not to work for AOC")
                    ->orWhereNull("mssql_recruitertracker.inactive_reason");
                });
            }

            if(LD::checkFeatureFlag('client-match-terminated-clinician-exclude')) {
                $bucket->where("mssql_providers.EmploymentStatusID","!=","12");
            }
            /**
             * Exclude clients whose name contains "PRN".
             */
            $bucket->whereRaw("CONCAT(mssql_patients.Patient_FirstName, ' ', mssql_patients.Patient_LastName)
            NOT LIKE '%PRN%'");

			$bucket->where('Candidate_Current_Status', '<>', 'Inactive');

            if(LD::checkFeatureFlag('add-outcomes-to-track-client-match')) {
                $bucket->where(function ($query) {
                    $query->where("mssql_recruitertracker.clientmatch_outcome", "!=", "Client match failed")
                          ->orWhereNull("mssql_recruitertracker.clientmatch_outcome");
                });
            }

            if(LD::checkFeatureFlag('client-not-kantime-exclude-from-client-match-bucket')) {
                $bucket->where("mssql_patients.not_in_kantime", 0);
            }

            $bucket->where('mssql_providers.is_deleted',0);
            $branch = [];
            $state = [];
            $users = [];
            $region = [];
            $Payer = [];
            $payergroup = [];
            $startDate = '';
            $endDate = '';
            $isMeMode = 0;

            if(isset($request['filters'])) {

                foreach($request['filters'] as $key => $value){

                    if($value['name']=='clientmatchstate') {
                        $state[] = $value['value'];
                    }
                    if($value['name']=='clientmatchbranch') {
                        $branch[] = $value['value'];
                    }
                    if($value['name']=='clientmatchclientcare'){
                        $users[] = $value['value'];
                    }

                    if($value['name']=='fromClientStartDate') {
                        if(!empty($value['value'])) {
                            $startDate = $value['value'];
                        }
                    }
                    if($value['name']=='toClientStartDate') {
                        if(!empty($value['value'])) {
                            $endDate = $value['value'];
                        }
                    }
                    if($value['name']=='clientmatchmemode') {
                        $isMeMode = 1;
                    }
                    if($value['name']=='region') {
                        if(!empty($value['value'])) {
                            $region[] = $value['value'];
                        }
                    }
                    if(LD::checkFeatureFlag('payer-filters-on-client-match')){
                        if($value['name']=='Payer') {
                            if(!empty($value['value'])) {
                                $Payer[] = $value['value'];
                            }
                        }
                        if($value['name']=='payer_group') {
                            if(!empty($value['value'])) {
                                $payergroup[] = $value['value'];
                            }
                        }
                    }

                }
            }

            if(count($state)>0) {
                $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
                $bucket->where(function($q) use ($facilities){
                    foreach($facilities as $facility) {
                        $q->orWhere("mssql_recruitertracker.aoc_facility_id","=",$facility->id);
                    }
                });
            }
            if(count($branch)>0) {
                $bucket->where(function($q) use ($branch){
                    foreach($branch as $b) {
                        $q->orWhere("mssql_recruitertracker.aoc_facility_id","=",$b);
                    }
                });
            }
            if(count($region)>0) {
                $bucket->where(function($q) use ($region){
                    foreach($region as $r) {
                        $q->orWhere("mssql_facilities.region","=",$r);
                    }
                });
            }
            if(count($Payer)>0) {
                $bucket->where(function($q) use ($Payer){
                    foreach($Payer as $p) {
                        $q->orWhere("mssql_patients.payer","=",$p);
                    }
                });
            }
            if(count($payergroup)>0) {
                $bucket->where(function($q) use ($payergroup){
                    foreach($payergroup as $r) {
                        $q->orWhere("mssql_patients.payer_group","=",$r);
                    }
                });
            }

            if(!empty($users) && !$isMeMode){
                $bucket->whereIn("mssql_patients.ccc_id",$users);
            } else if($isMeMode) {
                $bucket->where("mssql_patients.ccc_id","=",Auth::user()->id);
            }

            if(!empty($startDate)) {
                $convertedStartDate = date('Y-m-d',strtotime($startDate));
                $bucket->whereDate("mssql_recruitertracker.Candidate_Start_Date", ">=",$convertedStartDate);
            }

            if(!empty($endDate)) {
                $convertedEndDate = date('Y-m-d',strtotime($endDate));
                $bucket->whereDate("mssql_recruitertracker.Candidate_Start_Date", "<=",$convertedEndDate);
            }

            if($showCount) {
                return '('.$bucket->count().')';
            }
            H::ExcelLog($bucket->toSql(),'testclientmatch.log');
            return $bucket->get();


        }

    }
}
