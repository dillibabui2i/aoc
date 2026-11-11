<?php

namespace App\Helpers;

use App\RecruitingSchedule as RecruitingSchedule;
use App\User;
use App\MssqlProviders;
use App\Renewables;
use Carbon\Carbon;
use DB;

class Orientation
{
    public function getOrientationData($status = '', $request = [] ,$showCount=0) {
        $mytime = Carbon::now();
        $today = $mytime->toDateString();
        $tomorrow = Carbon::tomorrow();
        $tomorrowDate = $tomorrow->toDateString();
        $weekday =  Carbon::createFromFormat('Y-m-d', $mytime->toDateString())->format('l');
        $nextMonday = Carbon::parse($today)->next('Monday')->toDateString();

        if ($status == 'todays_ipeas') { // +1
            if(LD::checkFeatureFlag("show-in-home-on-ipea-calendar")){ // +2
                $recruiting = RecruitingSchedule::select(
                    'recruiting_schedule.*',
                    'linked_IPEA',
                    DB::Raw('IF(mssql_recruitertracker.intake_in_home = 1, concat(mssql_recruitertracker.Candidate, \' \', \'*\'),
                    mssql_recruitertracker.Candidate) as Candidate'),
                    'mssql_recruitertracker.id as rtid',
                    'mssql_recruitertracker.Branch_Location',
                    'branch_locations.branch',
                    'mssql_recruitertracker.Candidate_Current_Status',
                    'mssql_recruitertracker.Candidate_Notes',
                    DB::raw("(SELECT CONCAT(type,': ',DATE_FORMAT(appointment_date, '" . config('constant')['mysqlsearchdateformat'] . "')
                    ,' ',TIME_FORMAT(appointment_time, '" . config('constant')['mysqlsearchtimeformat'] . "')) FROM recruiting_schedule as
                    rsinner left join schedule_types on rsinner.type_id = schedule_types.id WHERE rsinner.type_id <> 1 AND
                    rsinner.appointment_date >= CURDATE()  AND rsinner.tracker_id = recruiting_schedule.tracker_id order by
                    appointment_date asc, appointment_time desc limit 1) as NS"),
                    DB::raw("(SELECT users.name from users left join users_has_roles on users.id = users_has_roles.user_id left join roles
                    on users_has_roles.role_id = roles.id where roles.name='Orientation Coordinator' and users.status='1' and
                    find_in_set(recruiting_schedule.ipea_location_id, users.branchid ) limit 1) as oc")
                );
            }else{ // +1
                $recruiting = RecruitingSchedule::select(
                    'recruiting_schedule.*',
                    'linked_IPEA',
                    'mssql_recruitertracker.Candidate',
                    'mssql_recruitertracker.id as rtid',
                    'mssql_recruitertracker.Branch_Location',
                    'branch_locations.branch',
                    'mssql_recruitertracker.Candidate_Current_Status',
                    'mssql_recruitertracker.Candidate_Notes',
                    DB::raw("(SELECT CONCAT(type,': ',DATE_FORMAT(appointment_date, '" . config('constant')['mysqlsearchdateformat'] . "')
                    ,' ',TIME_FORMAT(appointment_time, '" . config('constant')['mysqlsearchtimeformat'] . "')) FROM recruiting_schedule as
                    rsinner left join schedule_types on rsinner.type_id = schedule_types.id WHERE rsinner.type_id <> 1 AND
                    rsinner.appointment_date >= CURDATE()  AND rsinner.tracker_id = recruiting_schedule.tracker_id order by
                    appointment_date asc, appointment_time desc limit 1) as NS"),
                    DB::raw("(SELECT users.name from users left join users_has_roles on users.id = users_has_roles.user_id left join roles
                    on users_has_roles.role_id = roles.id where roles.name='Orientation Coordinator' and users.status='1' and
                    find_in_set(recruiting_schedule.ipea_location_id, users.branchid ) limit 1) as oc")
                );
            }
        } elseif ($status == 'pending_orientation') { // +1
            if(LD::checkFeatureFlag('multiple-roles-to-user')){ // +2
                $recruiting = RecruitingSchedule::select('recruiting_schedule.*','linked_IPEA','mssql_recruitertracker.Candidate','mssql_recruitertracker.id as rtid','mssql_recruitertracker.Branch_Location','branch_locations.branch','mssql_recruitertracker.Candidate_Notes', DB::raw("(SELECT users.name from users left join users_has_roles on users.id = users_has_roles.user_id left join roles on users_has_roles.role_id = roles.id where roles.name='Orientation Coordinator' and users.status='1' and find_in_set(recruiting_schedule.ipea_location_id, users.branchid ) limit 1) as oc"));
                if(LD::checkFeatureFlag('add-branch-in-pending-orientation')){
                    $recruiting->addselect('mssql_facilities.BranchName');
                }
            }else{ // +1
                $recruiting = RecruitingSchedule::select('recruiting_schedule.*','linked_IPEA','mssql_recruitertracker.Candidate','mssql_recruitertracker.id as rtid','mssql_recruitertracker.Branch_Location','branch_locations.branch','mssql_recruitertracker.Candidate_Notes', DB::raw("(SELECT users.name from users left join roles on users.role=roles.id where roles.name='Orientation Coordinator' and users.status='1' and find_in_set(recruiting_schedule.ipea_location_id, users.branchid ) limit 1) as oc"));
            }
        } else { // +1
            if(LD::checkFeatureFlag('multiple-roles-to-user')){ // +2
                $recruiting = RecruitingSchedule::select('recruiting_schedule.*','linked_IPEA','mssql_recruitertracker.Candidate','mssql_recruitertracker.id as rtid','mssql_recruitertracker.Branch_Location','branch_locations.branch','mssql_recruitertracker.Candidate_Current_Status','mssql_recruitertracker.Candidate_Notes', DB::raw("(SELECT users.name from users left join users_has_roles on users.id = users_has_roles.user_id left join roles on users_has_roles.role_id = roles.id where roles.name='Orientation Coordinator' and users.status='1' and find_in_set(recruiting_schedule.ipea_location_id, users.branchid ) limit 1) as oc"));
            }else{ // +1
                $recruiting = RecruitingSchedule::select('recruiting_schedule.*','linked_IPEA','mssql_recruitertracker.Candidate','mssql_recruitertracker.id as rtid','mssql_recruitertracker.Branch_Location','branch_locations.branch','mssql_recruitertracker.Candidate_Current_Status','mssql_recruitertracker.Candidate_Notes', DB::raw("(SELECT users.name from users left join roles on users.role=roles.id where roles.name='Orientation Coordinator' and users.status='1' and find_in_set(recruiting_schedule.ipea_location_id, users.branchid ) limit 1) as oc"));
            }
        }
        $recruiting->leftJoin('mssql_recruitertracker', function($join) {
            $join->on('recruiting_schedule.tracker_id', '=', 'mssql_recruitertracker.id');
        });
        $recruiting->leftJoin('branch_locations', function($join) {
            $join->on('recruiting_schedule.ipea_location_id', '=', 'branch_locations.id');
        });
        if(LD::checkFeatureFlag('add-branch-in-pending-orientation')){
            $recruiting->leftJoin('mssql_facilities', function($join) {
                $join->on('mssql_facilities.id', '=', 'mssql_recruitertracker.aoc_facility_id');
            });
        }
        $recruiting->where("recruiting_schedule.type_id",1);

        if($status=='ipeas_to_confirm') { // +1
            if($weekday=='Friday'){ // +2
                $recruiting->where('appointment_date',">",$today)->where("appointment_date","<=",$nextMonday);
            } else { // +1
              $recruiting->where('appointment_date',$tomorrowDate);
            }
            $recruiting->where(function($query) {
                $query->where("IPEA_Confirmed",'<>','Yes')->orWhereNull('IPEA_Confirmed');
            });
            if(LD::checkFeatureFlag('orientation-coordinator-ipeas-to-confirm-removecandidate')) {
                $recruiting->where(function($query) {
                    $query->where("IPEA_onboarding_status","!=","Inactive")->orWhereNull('IPEA_onboarding_status');
                });
            }
            if(LD::checkFeatureFlag("show-in-home-on-ipea-calendar")) {
                $recruiting->addSelect(DB::Raw('IF(mssql_recruitertracker.intake_in_home = 1, \'Yes\', \'No\') as intake_in_home'));
            }
        } else if($status=='todays_ipeas') { // +1
            $recruiting->where('appointment_date','<=', $today);
            $recruiting->where(function($query) {
                $query->where("IPEA_onboarding_status",'=','None')->orWhereNull('IPEA_onboarding_status')->orWhere("IPEA_onboarding_status",'=','');
            });
            $recruiting->where(function($query) {
                $query->where("IPEA_clinician_arrived",'=','None')->orWhereNull('IPEA_clinician_arrived')->orWhere("IPEA_clinician_arrived",'=','Yes');
            });
        } else if($status=='tomorrow_ipeas') { // +1
            if($weekday=='Friday'){ // +4
                $recruiting->where('appointment_date',">",$today)->where("appointment_date","<=",$nextMonday);
            } else { // +1
              $recruiting->where('appointment_date',$tomorrowDate);
            }
            $recruiting->where("IPEA_Confirmed",'=','Yes');
        }  else if($status=='pending_orientation') { // +1
            $recruiting->where(function($query) {
                $query->where("IPEA_onboarding_status",'=','Incomplete');
            })
            ->where("IPEA_onboarding_status","!=","Inactive")
            ->where("outcome","!=","Cancelled");
        }

        $this->applyFilter($recruiting, $request);
        
        if ($status == 'badge_request') { // +1
            $userBranch = H::getHelper("ConstantHelper")->checkUserHasAccessOfBadgeRequestBucket();
            $recruiting = MssqlProviders::select(
                'mssql_providers.id as providerId',
                'mssql_providers.fullName',
                'mssql_providers.ClinicianID',
                'mssql_facilities.FacilityName',
                DB::raw("DATE_FORMAT(mssql_providers.HiredDate, '" . config('constant')['mysqlsearchdatetimeformat'] . "') as HiredDate"))
                ->leftJoin('mssql_facilities','mssql_facilities.FacilityID','=','mssql_providers.FacilityID')
                ->where('mssql_providers.is_deleted',0)
                ->where('has_badge',0)
                ->whereIn('mssql_facilities.id',$userBranch);
        }

        if(LD::checkFeatureFlag('oc-dashboard-renewables')){ // +1
            if($status == 'expiring' || $status == 'missing'){ // +2
                $recruiting = Renewables::select(
                    'renewables.*',
                    'mssql_providers.fullName',
                    'mssql_providers.DisciplineName',
                    'renewables_types.name')
                ->leftJoin('mssql_providers','renewables.aoc_provider_id', '=', 'mssql_providers.id')
                ->leftJoin('renewables_types','renewables.renewables_type', '=', 'renewables_types.id')
                ->where('expiration_date','<',date('Y-m-d',strtotime("+90 days")))
                ->where('mssql_providers.is_deleted',0);
            }
        }
        
        if($showCount) { // +1
            return '('.$recruiting->count().')';
        }
        return $recruiting;
    }

    private function applyFilter(&$recruiting, $request) {
        $loggedInUser = H::getLoggedInUser();

        $branch = [];
        $state = [];
        $users = [];
        $pending = [];
        $region = [];
        $defaultMode = 0;
        if(isset($request['filters'])) { // +1
            foreach($request['filters'] as $key => $value){ // +2
                if($value['name']=='ocstate') { // +3
                    $state[] = $value['value'];
                }
                if($value['name']=='ocregion') { // +3
                    $region[] = $value['value'];
                }
                if($value['name']=='ipea_location_id') { // +3
                    $branch[] = $value['value'];
                }
                if($value['name']=='oc'){ // +3
                    $users[] = $value['value'];
                }
                if($value['name']=='pending') { // +3
                    $pending[] = $value['value'];
                }
                if($value['name']=='ocdefault') { // +3
                    $defaultMode=1;
                }
            }
            if(count($users)) { // +2
                foreach($users as $user) { // +3
                    $userData = User::select("branchid")->where("id",$user)->first()->toArray();
                    foreach($userData as $bb) { // +4
                        $branchArray = explode(",",$bb);
                        foreach($branchArray as $b) { // +5
                            $branch[]=$b;
                        }
                    }
                }
            }
        }

        if($defaultMode) { // +1
            $users = [];
            $branch = [];
            $users = explode(",", $loggedInUser['id']);
            $branch = explode(",", $loggedInUser['branchid']);
        }

        if(
            count($branch) ||  // +1
            count($users) // +1
        ) {
            $recruiting->where(function($q) use ($branch,$users){
                $q->where(function($q) use($branch){
                    if(count($branch) > 0){ // +4
                        $q->whereIn("recruiting_schedule.ipea_location_id", $branch);
                    }
                })->orWhere(function($q) use($users){
                    if (count($users) > 0) { // +4
                        $q->whereIn("recruiting_schedule.assisted_by", $users);
                    }
                });
            });
        }
       
            if(count($region)>0) { // +1
                if(LD::checkFeatureFlag('orientation_coordinator_region_filter_by_ipea_location')) { // +2
                    $questionMarkString = H::generateQuestionMarksArray($region);
                    $recruiting->where(function($q) use ($region,$questionMarkString){
                            $q->whereRaw("(select region from mssql_facilities where mssql_facilities.branchid = recruiting_schedule.ipea_location_id limit 1)  IN (".$questionMarkString.")",$region);
                    });
                    
                } else {
                    $recruiting->leftJoin('mssql_facilities', function($join) {
                        $join->on('recruiting_schedule.aoc_facility_id', '=', 'mssql_facilities.id');
                    });
                    $recruiting->where(function($q) use ($region){
                        if (count($region) > 0) { // +3
                            $q->whereIn("mssql_facilities.region", $region);
                        }
                    });
                }
                
            }
        
        if(count($state)) { // +1
            $recruiting->where(function($q) use ($state){
                if (count($state) > 0) { // +3
                    $q->whereIn("branch_locations.state", $state);
                }
            });
        }

        if(count($pending)) { // +1
            $recruiting->where(function($q) use ($pending){
                foreach($pending as $item)  { // +3
                    $q->orWhereRaw("FIND_IN_SET(?, recruiting_schedule.IPEA_pending)", $item);
                }
            });
        }
    }

    public function getDataTableHtml($type)
    {
        $field = [];
       return view('orientation.datatable',["type"=>$type,"field"=>$field]);
    }
}
