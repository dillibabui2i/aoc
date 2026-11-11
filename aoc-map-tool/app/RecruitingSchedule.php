<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use App\Helpers\LD;
use App\Helpers\H;
use DB;

class RecruitingSchedule extends Model
{
    //
    protected $primaryKey = 'id';
    protected $table = 'recruiting_schedule';

    protected $fillable = [
        'tracker_id',
        'type_id',
        'appointment_date',
        'appointment_time',
        'patient_id',
        'ClinicianID',
        'candidate_id',
        'pay_rate',
        'gp',
        'gp_approved_by',
        'outcome',
        'description',
        'scheduled_date',
        'aoc_patient_id',
        'aoc_candidate_id',
        'aoc_provider_id',
        'aoc_facility_id',
        'ipea_location_id',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'ccrNotes',
        'coverage_id',
        'candidate_type',
        'is_virtual'
    ];

	public static function booted() {

        parent::booted();

        static::addGlobalScope('exclude_deleted', function (Builder $builder) {
			$builder->where('recruiting_schedule.is_deleted', '0');
		});
    }

    public static function getCandidateStartQuery($request){
        if(LD::checkFeatureFlag("candidate_start_reassign_calls")) {
            $bucket = RecruitingSchedule::select("recruiting_schedule.*", DB::raw("ifnull(canu.name, users.name) as case_manager"),DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"),
            DB::raw("CONCAT(mssql_providers.FirstName,' ',mssql_providers.LastName) AS Clinician"),DB::raw("CONCAT(candidates.firstName,' ',candidates.lastName) AS Clinician_Candidate"),DB::raw("candidates.homePhone AS candidates_homePhone"),DB::raw("candidates.mobile AS candidates_mobile"),DB::raw("mssql_providers.Phone AS provider_homephone"),DB::raw("mssql_providers.Mobile AS provider_mobile"));
        } else {
            $bucket = RecruitingSchedule::select("recruiting_schedule.*", DB::raw("users.name AS case_manager"),DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"),
            DB::raw("CONCAT(mssql_providers.FirstName,' ',mssql_providers.LastName) AS Clinician"),DB::raw("CONCAT(candidates.firstName,' ',candidates.lastName) AS Clinician_Candidate"),DB::raw("candidates.homePhone AS candidates_homePhone"),DB::raw("candidates.mobile AS candidates_mobile"),DB::raw("mssql_providers.Phone AS provider_homephone"),DB::raw("mssql_providers.Mobile AS provider_mobile"));

        }
        if(LD::checkFeatureFlag('add-branch-candidate-start-followups')){
            $bucket->addSelect('mssql_facilities.FacilityName');
        }
        $bucket->leftJoin("mssql_patients",function($query){
            $query->on("mssql_patients.id","=","recruiting_schedule.aoc_patient_id");
        });

        $bucket->leftJoin("mssql_recruitertracker",function($query){
            $query->on("mssql_recruitertracker.id","=","recruiting_schedule.tracker_id");
        });

        $bucket->leftJoin("mssql_facilities",function($query){
            $query->on("mssql_facilities.id","=","mssql_patients.aoc_facility_id");
        });

        $bucket->leftJoin("mssql_providers",function($query){
            $query->on("mssql_providers.id","=","recruiting_schedule.aoc_provider_id");
        });

        $bucket->leftJoin("candidates",function($query){
            $query->on("candidates.id","=","recruiting_schedule.aoc_candidate_id");
        });

        $bucket->leftJoin("users",function($query){
            $query->on("users.id","=","mssql_patients.ccm_id");
        });

        if(LD::checkFeatureFlag("candidate_start_reassign_calls")) {
            $bucket->leftJoin("users as canu",function($query){
                $query->on("canu.id","=","mssql_recruitertracker.assigned_candidatestart");
            });
        }

        $bucket->where("recruiting_schedule.appointment_date","<",date("Y-m-d"));
        $bucket->where("recruiting_schedule.type_id","=",6);


        $branch = [];
        $state = [];
        $users = [];
        $payer = [];
        $payer_group = [];
        $startDate = '';
        $endDate = '';
        $isMeMode = 0;
        $region = [];

        if(isset($request['filters'])) {

            foreach($request['filters'] as $key => $value){

                if($value['name']=='ccmcandidatestate') {
                    $state[] = $value['value'];
                }
                if($value['name']=='ccmcandidatebranch') {
                    $branch[] = $value['value'];
                }
                if($value['name']=='ccmcandidateregion') {
                    $region[] = $value['value'];
                }
                if($value['name']=='candidateccm'){
                    $users[] = $value['value'];
                }
                if($value['name']=='ccmcandidatedefault'){
                    $isMeMode =1;
                }

                if($value['name']=='fromCandidateStartDate') {
                    if(!empty($value['value'])) {
                        $startDate = $value['value'];
                    }
                }
                if($value['name']=='toCandidateStartDate') {
                    if(!empty($value['value'])) {
                        $endDate = $value['value'];
                    }
                }
                if(LD::checkFeatureFlag('payer-filters-on-ccm-candidate-start')){
                    if($value['name']=='Payer') {
                        if(!empty($value['value'])) {
                            $payer[] = $value['value'];
                        }
                    }
                    if($value['name']=='payer_group') {
                        if(!empty($value['value'])) {
                            $payer_group[] = $value['value'];
                        }
                    }
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

        if(count($payer)){
                $bucket->where(function($q) use($payer){
                    foreach($payer as $p) {
                        $q->orWhere("mssql_patients.payer", '=', $p);
                    }
                });
        }
        if(count($payer_group)){
            $bucket->where(function($q) use($payer_group){
                foreach($payer_group as $p) {
                    $q->orWhere("mssql_patients.payer_group", '=', $p);
                }
            });
        }

        if(!empty($startDate)) {
            $convertedStartDate = date('Y-m-d',strtotime($startDate));
            $bucket->whereDate("recruiting_schedule.appointment_date", ">=",$convertedStartDate);
        }

        if(!empty($endDate)) {
            $convertedEndDate = date('Y-m-d',strtotime($endDate));
            $bucket->whereDate("recruiting_schedule.appointment_date", "<=",$convertedEndDate);
        }

        if(LD::checkFeatureFlag("candidate_start_reassign_calls")) {
            if(!empty($users) && !$isMeMode){
                $bucket->whereIn(DB::raw("ifnull(mssql_recruitertracker.assigned_candidatestart, mssql_patients.ccm_id)"),$users);
            } else if($isMeMode) {
                $bucket->where(DB::raw("ifnull(mssql_recruitertracker.assigned_candidatestart, mssql_patients.ccm_id)"),"=",Auth::user()->id);
            }
        } else {
            if(!empty($users) && !$isMeMode){
                $bucket->whereIn("mssql_patients.ccm_id",$users);
            } else if($isMeMode) {
                $bucket->where("mssql_patients.ccm_id","=",Auth::user()->id);
            }
        }
       $bucket->where("mssql_recruitertracker.Candidate_Current_Status","=","First Shift Confirmed");
       return $bucket;
    }

    public function candidate(): HasOne
    {
        return $this->hasOne(Candidates::class, 'id', 'aoc_candidate_id');
    }

    public function branchLocation(): HasOne
    {
        return $this->hasOne(BranchLocations::class, 'id', 'ipea_location_id');
    }

    public function recruiterTracker(): HasOne
    {
        return $this->hasOne(MssqlRecruitertracker::class, 'id', 'tracker_id');
    }
}
