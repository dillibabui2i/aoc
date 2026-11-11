<?php

namespace App\Helpers;

use App\BranchLocations as BranchLocations;
use App\Enums\DisciplineEnum;
use App\Enums\NationalRecruitingTabName;
use App\Enums\PayerRatesStatus;
use App\MssqlFacilities as MssqlFacilities;
use App\Notification;
use App\PayerRates;
use App\Roles;
use App\ScheduleType;
use App\User;
use App\Candidates;
use App\MssqlPatients;
use App\MssqlProviders;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Jobs;
use Illuminate\Support\Facades\Auth;
use App\NeedToHireAccounts;
use App\SocialMedia;
use App\OfficeEvaluation;
use Illuminate\Support\Str;
use App\RecruitingSchedule;
use Illuminate\Support\Collection;
use App\Intake;
use App\MssqlRecruitertracker;
use App\Helpers\LD;
use Arr;

class ConstantHelper
{
    public function getSkillSets()
    {
        return config('constant.skills');
    }

    public function getCandidateCurrentStatus()
    {
        return config('constant.candidateCurrentStatus');
    }

    public function getFacilities()
    {
        $facilities = MssqlFacilities::select('*')->where('hide', '!=', '1')->orWhereNull('hide')->orderBy(
            'FacilityName'
        )->get();
        return $facilities;
    }

    public function getFacilityDataById($id)
    {
        if ($id != '') {
            $facility = MssqlFacilities::select('*')->where('id', '=', $id)->first();
            return $facility;
        } else {
            return '';
        }
    }

    public function getFacilityById($id)
    {
        if ($id != '') {
            $facility = MssqlFacilities::select('*')->where('id', '=', $id)->first();
            return $facility->FacilityName;
        } else {
            return '';
        }
    }

    public function getFacilityNameByFacilityId($facilityId)
    {
        if ($facilityId != '') {
            $facility = MssqlFacilities::select('*')->where('FacilityID', '=', $facilityId)->first();
            return $facility->FacilityName;
        } else {
            return '';
        }
    }

    public function getFacilitiesByState($state, $btype = 'all')
    {
        $flippedStates = array_flip(config('constant.dashboard_state'));

        if ($btype === NationalRecruitingTabName::CANDIDATE->value && is_array($state)) {
            $state = array_map(fn($name) => $flippedStates[$name] ?? null, $state);
        }

        $facilities = MssqlFacilities::select('id', 'FacilityName')->where(function ($query) {
            $query->where('hide', '!=', '1')->orWhereNull('hide');
        });
        if (!empty($state)) {
            $facilities->where(function ($query) use ($state) {
                if (!is_array($state)) {
                    $st = explode(',', $state);
                } else {
                    $st = $state;
                }
                foreach ($st as $s) {
                    $query->orWhere("state", '=', $s);
                }
            });
        }
        if (LD::checkFeatureFlag('cc-dashboard-remove-tx-skilled-visits-branch-monthly-calls')
            && $btype == 'monthlycall') {
            $facilities->whereNotIn("id", ['14']);
        }
        $facilities->orderBy('FacilityName');
        return $facilities->get();
    }

    public function getOCBranchByState($state)
    {
        $branches = BranchLocations::select('id', 'branch')->where(function ($query) use ($state) {
            $st = explode(',', $state);
            foreach ($st as $s) {
                $query->orWhere("state", '=', $s);
            }
        })->orderBy('branch');
        return $branches->get();
    }

    public function getBranches()
    {
        $facilities = BranchLocations::select('*')->orderBy("branch")->get();

        return $facilities;
    }

    public function getBranchStates()
    {
        $states = BranchLocations::select('state')->distinct()->orderBy('state')->get();
        return $states;
    }

    public function getJobviteBranchByState($state)
    {
        $branches = DB::table('NeedToHire_Hub')->select("Branch")->distinct()->where('Branch', '<>', 'none')->where(
            'Branch',
            '<>',
            ''
        )->where(function ($query) use ($state) {
            if (is_array($state)) {
                $st = $state;
            } else {
                $st = explode(',', $state);
            }
            foreach ($st as $s) {
                $query->orWhere("state", '=', $s);
            }
        })->orderBy('state')->orderBy('Branch');
        return $branches->get();
    }

    // PD-5392: branch list for the Jobvite dashboard buckets, sourced from `jobs`
    // (the same table the bucket filters on) instead of NeedToHire_Hub, which has no GA/KS/OK/TN/VA.
    public function getJobsBranchByState($state)
    {
        $states = is_array($state) ? $state : explode(',', $state);

        // A branch is listed only under the state where it has the most jobs:
        // jobs.state comes from the category, so stray pairs
        // (e.g. Dallas/Florida) would otherwise pollute other states' lists.
        return $this->getJobviteBranchStats()
            ->groupBy('branch')
            ->filter(fn(Collection $rows) => in_array($rows->sortByDesc('total')->first()->state, $states, true))
            ->keys()
            ->sort()
            ->values()
            ->map(fn($branch) => (object)['Branch' => $branch]);
    }

    // PD-5392: canonical branch name + all its aliases, used to expand the branch filter.
    public function getJobviteBranchAliases(string $branch): array
    {
        $aliases = config('constant.jobvite_branch_aliases');
        $canonical = $aliases[$branch] ?? $branch;

        return array_merge([$canonical], array_keys($aliases, $canonical, true));
    }

    /**
     * PD-5392: jobs per (canonical branch, state); `none`/empty branches skipped.
     */
    private function getJobviteBranchStats(): Collection
    {
        $aliases = config('constant.jobvite_branch_aliases');

        return DB::table('jobs')
            ->select('branch', 'state', DB::raw('COUNT(*) as total'))
            ->whereNotNull('branch')
            ->whereNotIn('branch', ['none', ''])
            ->groupBy('branch', 'state')
            ->get()
            ->map(function ($row) use ($aliases) {
                $row->branch = $aliases[$row->branch] ?? $row->branch;
                return $row;
            })
            ->groupBy(fn($row) => $row->branch . '|' . $row->state)
            ->map(function (Collection $rows) {
                $first = $rows->first();
                $first->total = $rows->sum('total');
                return $first;
            })
            ->values();
    }

    public function getJobviteBranches()
    {
        return DB::table('NeedToHire_Hub')->select("Branch")->distinct()->where('Branch', '<>', 'none')->where(
            'Branch',
            '<>',
            ''
        )->get();
    }

    public function getScheduleTypes()
    {
        $scheduleType = ScheduleType::all()->toArray();
        usort($scheduleType, fn($a, $b) => $a['sort_order'] - $b['sort_order']);

        return $scheduleType;
    }

    public function getScheduleType($type = '')
    {
        $scheduleTypeId = '';
        if ($type != '') {
            $scheduleType = '';
            $schedTypes = ScheduleType::select('id')->where('type', $type)->first();
            if (!empty($schedTypes)) {
                $scheduleTypeId = $schedTypes->id;
            }
        }
        return $scheduleTypeId;
    }


    public function getRecruiters($fieldValue = '')
    {
        return $this->getRecruitersList($fieldValue);
        //deprecated old way based on users.role field in db bellow
        //decided to not delete it for now for safety purpose

        $roles = Roles::select('id')->where('department', 'Recruiting')->get()->toArray();
        $role = [];
        foreach ($roles as $r) {
            $role[] = $r['id'];
        }
        $users = User::select((!empty($fieldValue) ? array('name', $fieldValue) : 'name'))->whereIn(
            'role',
            $role
        )->where('status', 1)->orderBy('name')->get()->toArray();
        $recruiter = [];
        foreach ($users as $u) {
            $recruiter[] = ['value' => (!empty($fieldValue) ? $u[$fieldValue] : $u['name']), 'text' => $u['name']];
        }

        return $recruiter;
    }

    public function getRecruiterManager($fieldValue = '')
    {
        $roles = Roles::select('id')->where('name', 'Recruiting Manager')->first();

        $users = User::select((!empty($fieldValue) ? array('name', $fieldValue) : 'name'))->where(
            'role',
            $roles->id
        )->get()->toArray();
        $recruiterManager = [];
        foreach ($users as $u) {
            $recruiterManager[] = [
                'value' => (!empty($fieldValue) ? $u[$fieldValue] : $u['name']),
                'text' => $u['name']
            ];
        }

        return $recruiterManager;
    }

    public function getRecruitersWithIds()
    {
        $users = User::select("users.id", "users.name")
            ->distinct()
            ->leftJoin('users_has_roles', 'users.id', '=', 'users_has_roles.user_id')
            ->leftJoin('roles', 'users_has_roles.role_id', '=', 'roles.id')
            ->whereIn('users_has_roles.role_id', function ($query) {
                $query->select('id')
                    ->from('roles')
                    ->where('department', 'Recruiting');
            })
            ->where('status', 1)
            ->orderBy('users.name')->get();
        $recruiter = [];
        foreach ($users as $u) {
            $recruiter[] = ['value' => $u['name'] . '##' . $u['id'], 'text' => $u['name']];
        }

        return $recruiter;
    }


    public function getUsers()
    {
        return User::where("status", 1)->get()->map(function ($user) {
            $user->name = $user->name . (!empty($user->title) ? ' (' . $user->title . ')' : '');
            return $user;
        })->toArray();
    }

    public function getUsersWithCareTeam()
    {
        $careTeam = [];


        $careUser[0]["name"] = 'careteam';
        $careUser[0]["email"] = 'careteam@dummy.com';
        $careUser[0]["id"] = '-1';

        $users = User::where("status", 1)->get()->map(function ($user) {
            $user->name = $user->name . (!empty($user->title) ? ' (' . $user->title . ')' : '');
            return $user;
        })->toArray();

        $careTeam = array_merge($careUser, $users);
        return $careTeam;
    }

    public function getUserByEmail($userEmail)
    {
        $user = User::select('id')->where('email', $userEmail)->first();
        if (isset($user->id)) {
            return $user->id;
        } else {
            return '';
        }
    }

    public function getUserByName($userName)
    {
        $user = User::select('id')->where('name', $userName)->first();
        if (isset($user->id)) {
            return $user->id;
        } else {
            return '';
        }
    }

    public function getUserById($userid)
    {
        $user = User::select('name')->where('id', $userid)->first();
        if (isset($user->name)) {
            return $user->name;
        } else {
            return '';
        }
    }

    public function getUserIdByProviderId($providerId)
    {
        $user = DB::table("mssql_providers")
            ->leftJoin("users", "users.email", "=", "mssql_providers.AOC_Email")
            ->select("users.id")->where("mssql_providers.mssqlProviderID", $providerId)->first();
        if (isset($user->id)) {
            return $user->id;
        } else {
            return '';
        }
    }

    public function getUserIdByBillingProviderId($providerId)
    {
        $user = DB::table("mssql_providers")
            ->leftJoin("users", "users.email", "=", "mssql_providers.AOC_Email")
            ->select("users.id")->where("mssql_providers.ProviderID", $providerId)->first();
        if (isset($user->id)) {
            return $user->id;
        } else {
            return '';
        }
    }

    public function getProviderIdByUserId($userId)
    {
        $mssql_providers = DB::table("mssql_providers")
            ->leftJoin("users", "users.email", "=", "mssql_providers.AOC_Email")
            ->select("mssql_providers.ProviderID")->where("users.id", $userId)->first();
        if (isset($mssql_providers->ProviderID)) {
            return $mssql_providers->ProviderID;
        } else {
            return '';
        }
    }

    public function getDashboardState()
    {
        $dashboardState = config('constant.dashboard_state');
        $state = [];
        foreach ($dashboardState as $key => $value) {
            $state[] = ["value" => $key, "text" => $value];
        }
        return $state;
    }

    public function getSourceType()
    {
        $soruceType = Candidates::select('appSourceType')->distinct()->whereRaw('appSourceType <> ""')->get();
        return $soruceType;
    }

    public function getAllSource()
    {
        $sources = DB::table("candidate_applications")->select('source')->distinct()->whereRaw('source <> ""')->orderBy(
            'source'
        )->get()->toArray();
        $sources = H::objToArr($sources);
        $sourceList = [];
        foreach ($sources as $source) {
            $sourceList[] = ["value" => $source['source'], "text" => $source['source']];
        }
        return $sourceList;
    }

    public function getCurrentSteps()
    {
        return [
            ['value' => '1', 'text' => 'Text Blast'],
            ['value' => '2', 'text' => 'P.E.T'],
            ['value' => '3', 'text' => 'Social Media'],
            ['value' => '4', 'text' => 'Private Message'],
            ['value' => '5', 'text' => 'Linkedin'],
            ['value' => '6', 'text' => 'Indeed'],
            ['value' => '7', 'text' => 'Cold Call'],
            ['value' => '8', 'text' => 'Mail Matters'],
            ['value' => '9', 'text' => 'Bon Nurse Search'],
            ['value' => '10', 'text' => 'Social Media Sponsored'],
            ['value' => '11', 'text' => 'Escalted'],
        ];
    }

    public function isNTHAlertMap($currentMap)
    {
        if ($currentMap == 'maps_nthalerts') {
            return true;
        } else {
            return false;
        }
    }

    public function isNTHMap($currentMap)
    {
        if ($currentMap == 'maps_nth') {
            return true;
        } else {
            return false;
        }
    }

    public function isCoverageClinicianMap($currentMap)
    {
        if ($currentMap == 'maps_covclin') {
            return true;
        } else {
            return false;
        }
    }

    public function isCoverageMap($currentMap)
    {
        if ($currentMap == 'maps_coverageneeds') {
            return true;
        } else {
            return false;
        }
    }

    public function getUniqueIdentifierForMap($mapId)
    {
        return DB::table("map_configurations")->select("unique_identifier_col")->where("map_id", $mapId)->get();
    }

    public function getAllMaps()
    {
        return DB::table("map_configurations")->select("*")->get();
    }

    public function getJobsLocationCity()
    {
        return Jobs::select('locationCity')->distinct()->orderBy('locationCity')->get()->toArray();
    }

    public function getEmploymentStatus()
    {
        if (LD::checkFeatureFlag('prn-changes-add-clinician')) {
            return DB::table("mssql_employmentstatus")
                ->where('EmploymentStatus', '!=', 'PRN')
                ->where('EmploymentStatus', '!=', 'PRN Agreement')
                ->get()->toArray();
        } else {
            return DB::table("mssql_employmentstatus")->get()->toArray();
        }
    }

    public function isTextBlastMap($currentMap)
    {
        if ($currentMap == 'maps_textblast') {
            return true;
        } else {
            return false;
        }
    }

    public function getMssqlPatientStatus()
    {
        $status = DB::table("mssql_patientstatus")->select('PatientStatus', 'id')->where(
            "PatientStatus",
            "!=",
            ""
        )->get();
        return $status;
    }

    public function getLobStatus()
    {
        $status = DB::table("lob")->select('LOBName', 'LOBID')->get();
        return $status;
    }

    public function getUserNameById($id, $field = "name")
    {
        if ($id != '') {
            $user = User::find($id);
            if (!empty($user)) {
                return $user->{$field};
            }
            return '';
        } else {
            return '';
        }
    }

    public function getUserEmailById($id)
    {
        if ($id != '') {
            $user = User::find($id);
            if (!empty($user)) {
                return $user->email;
            }
            return '';
        } else {
            return '';
        }
    }

    public function getClientCareCoordinator()
    {
        $roleName = 'Client Care Coordinator';

        if (LD::checkFeatureFlag('multiple-roles-to-user')) {
            $ccArray = [];
            $ccUser = User::select("users.id", "users.name")
                ->leftJoin('users_has_roles', 'users.id', '=', 'users_has_roles.user_id')
                ->leftJoin('roles', 'users_has_roles.role_id', '=', 'roles.id')
                ->leftJoin('roles as alternateRoleTbl', 'users.alternateRole', '=', 'alternateRoleTbl.id')
                ->where(function ($query) use ($roleName) {
                    $query->where('roles.name', $roleName)->orWhere('alternateRoleTbl.name', $roleName);
                })
                ->where('status', 1)
                ->orderBy('users.name')->get();
            foreach ($ccUser as $user) {
                $ccArray[] = ["value" => $user->id, 'text' => $user->name];
            }
            return $ccArray;
        } else {
            $ccArray = [];
            $ccUser = User::select("users.id", "users.name")
                ->leftJoin('roles', 'users.role', '=', 'roles.id')
                ->leftJoin('roles as alternateRoleTbl', 'users.alternateRole', '=', 'alternateRoleTbl.id')
                ->where(function ($query) use ($roleName) {
                    $query->where('roles.name', $roleName)->orWhere('alternateRoleTbl.name', $roleName);
                })
                ->where('status', 1)
                ->orderBy('users.name')->get();
            foreach ($ccUser as $user) {
                $ccArray[] = ["value" => $user->id, 'text' => $user->name];
            }
            return $ccArray;
        }
    }

    public function getIntakeCaseManager()
    {
        $optionArray = [];

        $bucket = DB::table("mssql_patients")
            ->distinct()
            ->select(
                'users.id',
                'users.name',

            );

        $bucket->leftJoin("users", function ($query) {
            $query->on("users.id", "=", "mssql_patients.ccm_id");
        });


        $bucket->orderBy("users.name", "asc");

        $caseMangerData = $bucket->get();

        foreach ($caseMangerData as $data) {
            if (!empty($data->id)) {
                $optionArray[] = ["value" => $data->id, 'text' => $data->name];
            }
        }

        return $optionArray;
    }

    public function getIntakeCoordinator()
    {
        $roleName = 'Intake Coordinator';
        if (LD::checkFeatureFlag('multiple-roles-to-user')) {
            $ccArray = [];
            $ccUser = User::select("users.id", "users.name")
                ->leftJoin('users_has_roles', 'users.id', '=', 'users_has_roles.user_id')
                ->leftJoin('roles', 'users_has_roles.role_id', '=', 'roles.id')
                ->leftJoin('roles as alternateRoleTbl2', 'users.alternateRole', '=', 'alternateRoleTbl2.id')
                ->where(function ($query) use ($roleName) {
                    $query->where('roles.name', $roleName)->orWhere('alternateRoleTbl2.name', $roleName);
                })
                ->where('status', 1)
                ->orderBy('users.name')->get();
            foreach ($ccUser as $user) {
                $ccArray[] = ["value" => $user->id, 'text' => $user->name];
            }
            return $ccArray;
        } else {
            $ccArray = [];
            $ccUser = User::select("users.id", "users.name")
                ->leftJoin('roles', 'users.role', '=', 'roles.id')
                ->leftJoin('roles as alternateRoleTbl2', 'users.alternateRole', '=', 'alternateRoleTbl2.id')
                ->where(function ($query) use ($roleName) {
                    $query->where('roles.name', $roleName)->orWhere('alternateRoleTbl2.name', $roleName);
                })
                ->where('status', 1)
                ->orderBy('users.name')->get();
            foreach ($ccUser as $user) {
                $ccArray[] = ["value" => $user->id, 'text' => $user->name];
            }
            return $ccArray;
        }
    }

    public function getClinicalCareManager()
    {
        if (LD::checkFeatureFlag('multiple-roles-to-user')) {
            $ccArray = [];
            $ccUser = User::select("users.id", "users.name")
                ->leftJoin('users_has_roles', 'users.id', '=', 'users_has_roles.user_id')
                ->leftJoin('roles', 'users_has_roles.role_id', '=', 'roles.id')
                ->where(function ($query) {
                    $query->where('roles.name', 'Clinical Care Manager');
                })
                ->where('status', 1)
                ->orderBy('users.name')->get();
            foreach ($ccUser as $user) {
                $ccArray[] = ["value" => $user->id, 'text' => $user->name];
            }
            return $ccArray;
        } else {
            $ccArray = [];
            $ccUser = User::select("users.id", "users.name")
                ->leftJoin('roles', 'users.role', '=', 'roles.id')
                ->where(function ($query) {
                    $query->where('roles.name', 'Clinical Care Manager');
                })
                ->where('status', 1)
                ->orderBy('users.name')->get();
            foreach ($ccUser as $user) {
                $ccArray[] = ["value" => $user->id, 'text' => $user->name];
            }
            return $ccArray;
        }
    }

    public function getRecruitersList($valueField = 'id')
    {
        if ($valueField === false || $valueField === '') {    //legacy code compatibility
            $valueField = 'name';
        } elseif ($valueField === true) {
            $valueField = 'id';
        }

        $allowedFields = ['email', 'name', 'id'];
        if (!empty($valueField) && !in_array($valueField, $allowedFields, true)) {
            return [];
        }

        if (LD::checkFeatureFlag('multiple-roles-to-user')) {
            $columns = ['users.name'];

            if ($valueField !== 'name') {
                $columns[] = "users.{$valueField}";
            }

            $users = User::select($columns)
                ->distinct()
                ->leftJoin('users_has_roles', 'users.id', '=', 'users_has_roles.user_id')
                ->leftJoin('roles', 'users_has_roles.role_id', '=', 'roles.id')
                ->whereIn('users_has_roles.role_id', function ($query) {
                    $query->select('id')
                        ->from('roles')
                        ->where('department', 'Recruiting');
                })
                ->where('status', 1)
                ->orderBy('users.name')->cursor();

        } else {
            $columns = ['users.name'];

            if ($valueField !== 'name') {
                $columns[] = "users.{$valueField}";
            }

            $users = User::select($columns)
                ->leftJoin('roles', 'users.role', '=', 'roles.id')
                ->where('roles.name', 'Recruiter')
                ->where('status', 1)
                ->orderBy('users.name')->cursor();
        }

        $usersArr = [];
        foreach ($users as $user) {
            $value = $user->{$valueField};

            $usersArr[] = [
                'value' => $value,
                'text'  => $user->name
            ];
        }

        return $usersArr;
    }

    public function getReporterList($needId = true)
    {
        $usersArr = [];
        $users = H::getHelper("UserPermissionResolver")->getUserByPermission('triage.addTriageCallForm');
        foreach ($users as $user) {
            $usersArr[] = ["value" => $needId ? $user->id : $user->name, 'text' => $user->name];
        }
        return $usersArr;
    }

    public function getShiftType($day, $startTimeAMPM, $endTimeAMPM)
    {
        $shiftMapping = DB::table("NeedToHire_ShiftTypeMapping")->select(
            "NeedToHire_ShiftTypeMapping.ShiftType",
            "NeedToHire_ShiftType.ShiftTypeDescription"
        )
            ->leftJoin("NeedToHire_ShiftType", function ($join) {
                $join->on("NeedToHire_ShiftType.id", '=', 'NeedToHire_ShiftTypeMapping.ShiftType');
            })
            ->where("StartDayOfWeek", $day)
            ->where("StartTimeAMPM", $startTimeAMPM)
            ->where("EndTimeAMPM", $endTimeAMPM)->first();
        if ($shiftMapping) {
            return $shiftMapping;
        } else {
            return null;
        }
    }

    public function getTimeDifference($firstTime, $secondTime)
    {
        $data = [];
        $ftime = strtotime($firstTime);
        $stime = strtotime($secondTime);
        $mins = ($stime - $ftime) / 60;

        $data['totalminutes'] = $mins;
        $data['minutes'] = ($mins % 60);
        $data['hours'] = round((float)($mins / 60), 2);
        $data['starthour'] = date('H', $ftime);
        $data['startminute'] = date('i', $ftime);
        $data['startAMPM'] = date("A", $ftime);
        $data['endAMPM'] = date("A", $stime);
        $data['endhour'] = date('H', $stime);
        $data['endminute'] = date('i', $stime);
        $data['StartAndEndOnSameDay'] = 1;

        if ($data['totalminutes'] < 0) {
            $mins = (strtotime($secondTime . " +1 days") - strtotime($firstTime)) / 60;
            $data['totalminutes'] = $mins;
            $data['minutes'] = ($mins % 60);
            $data['hours'] = round((float)($mins / 60), 2);
            $data['StartAndEndOnSameDay'] = 0;
        }

        return $data;
    }

    public function getSmallTimeFormat($time)
    {
        $stime = strtotime($time);
        return date("h:i A", $stime);
    }

    public function setNTHAuditRecord($ItemId, $action, $beforeArray = [])
    {
        $data = [];
        $reason = '';
        $type = '';
        $shiftHour = 0;
        $loggedInUser = H::getLoggedInUser();
        $currentData = DB::table('NeedToHire_Schedules')->where("id", $ItemId)->useWritePdo()->first();
        H::ExcelLog(print_r($beforeArray, true), 'setNTHAudit.log');
        H::ExcelLog(print_r($currentData, true), 'setNTHAudit.log');

        if ($action == 'add') {
            $reason = $currentData->AdditionReason;
            $type = 'A';
        } else {
            if ($action == 'edit') {
                $reason = $currentData->ModificationReason;
                $type = 'M';
            } else {
                if ($action == 'remove') {
                    $reason = $currentData->RemovalReason;
                    $type = 'R';
                }
            }
        }

        $data['aoc_nthaccounts_id'] = $currentData->aoc_nthaccounts_id;
        $data['schedule_id'] = $currentData->id;
        $data['StartDayOfWeek'] = $currentData->StartDayOfWeek;
        $data['StartDayOfWeekInt'] = $currentData->StartDayOfWeekInt;
        $data['StartHour'] = $currentData->StartHour;
        $data['StartMinute'] = $currentData->StartMinute;
        $data['EndHour'] = $currentData->EndHour;
        $data['EndMinute'] = $currentData->EndMinute;
        $data['StartAndEndOnSameDay'] = $currentData->StartAndEndOnSameDay;
        $data['ShiftType'] = $currentData->ShiftType;
        $data['Reason'] = $reason;
        $data['Type'] = $type;
        $data['ShiftHours'] = $currentData->ShiftHours;
        $data['ShiftTypeDescription'] = $currentData->ShiftTypeDescription;
        $data['ChangeDescription'] = '';
        $data['created_at'] = date(config('constant')['longdatetimeformat']);
        if (isset($loggedInUser['id'])) {
            $data['created_by'] = $loggedInUser['id'];
        } else {
            $data['created_by'] = 1;
        }

        $HoursAdded = 0;
        $HoursRemoved = 0;
        $NetChangeinHours = 0;

        if (empty($beforeArray)) {
            if ($action == 'add') {
                $HoursAdded = $currentData->ShiftHours;
                $NetChangeinHours = abs($HoursAdded);
            }
        } else {
            if ($action == 'edit') {
                $totalChange = ($currentData->ShiftHours - $beforeArray->ShiftHours);
                if ($totalChange < 0) {
                    $HoursRemoved = abs($totalChange);
                    $NetChangeinHours = $totalChange;
                } else {
                    $HoursAdded = $totalChange;
                    $NetChangeinHours = abs($totalChange);
                }
            }

            if ($action == 'remove') {
                $HoursRemoved = ($beforeArray->ShiftHours);
                $NetChangeinHours = (0 - $beforeArray->ShiftHours);
            }
        }
        $data['HoursAdded'] = $HoursAdded;
        $data['HoursRemoved'] = $HoursRemoved;
        $data['NetChangeinHours'] = $NetChangeinHours;
        H::ExcelLog(print_r($data, true), 'setNTHAudit.log');

        return DB::table("NeedToHire_Audit")->insert($data);
    }

    public function getLiftingRequirements()
    {
        $arr = MssqlPatients::select('CF_LiftingRequired')->whereNotNull('CF_LiftingRequired')->where(
            'CF_LiftingRequired',
            '!=',
            ''
        )->distinct()->get()->toArray();
        return array_column($arr, 'CF_LiftingRequired');
    }

    public function getCoverageTrackerFilledBy()
    {
        $rs = DB::table("coverage_tracker")->select("users.name", "users.id")->leftJoin(
            "users",
            "users.id",
            "=",
            "coverage_tracker.filled_by"
        )->distinct("filled_by")->orderBy('users.name')->get()->toArray();
        $data = [];
        foreach ($rs as $user) {
            $data[] = ["value" => $user->id, "text" => $user->name];
        }
        return $data;
    }

    public function getCoverageTrackerCreatedBy()
    {
        $rs = DB::table("coverage_tracker")
            ->select("users.name", "users.id")
            ->leftJoin("users", "users.id", "=", "coverage_tracker.created_by")
            ->distinct("created_by")
            ->orderBy('users.name')->get()->toArray();
        $data = [];
        foreach ($rs as $user) {
            $data[] = ["value" => $user->id, "text" => $user->name];
        }
        return $data;
    }

    public function getCCCNameByProviderID($id)
    {
        $arr = MssqlProviders::select(DB::raw('concat(FirstName," ",LastName) as name'))->where(
            'ProviderID',
            $id
        )->first();
        if ($arr) {
            $arr = $arr->toArray();
        } else {
            $arr['name'] = '';
        }
        return isset($arr['name']) ? $arr['name'] : '';
    }

    public function getPayers(?array $excludePatterns = ['%EVV%', '%Therapy%'])
    {
        // Kantime payers
        $payers = MssqlPatients::distinct()->select('Payer')
            ->where('Payer', '!=', '')
            ->orderBy('Payer');
        /* AOC-2501 Added exclusion list for payers. Maybe old exclusion list is not relevant overall system */
        if (count($excludePatterns) > 0) {
            foreach ($excludePatterns as $pattern) {
                $payers->where('Payer', 'NOT LIKE', $pattern);
            }
        }

        // Custom payers from payer_rates
        $customPayers = PayerRates::query()
            ->where('is_custom', 1)
            ->where('is_deleted', 0)
            ->where('status', PayerRatesStatus::APPROVED->value)
            ->whereDate('effectiveFrom', '<=', now())
            ->whereDate('effectiveThrough', '>=', now())
            ->distinct()
            ->pluck('payer')
            ->toArray();


        $allPayers = collect($payers->pluck('Payer')->toArray())
            ->merge($customPayers)
            ->unique()
            ->sort()
            ->values();

        return $allPayers->map(function ($payer) {
            return [
                'value' => $payer,
                'text'  => $payer,
            ];
        })->toArray();
    }

    public function getRespitePayers()
    {
        $respitePayers = MssqlPatients::distinct()->select('RespitePayer')
            ->where('RespitePayer', '!=', '')
            ->whereNotNull('RespitePayer')
            ->orderBy('RespitePayer')
            ->get()->toArray();
        $payerList = [];
        foreach ($respitePayers as $respitePayer) {
            $payerList[] = [
                "value" => $respitePayer['RespitePayer'],
                'text' => $respitePayer['RespitePayer']
            ];
        }
        return $payerList;
    }

    public function checkUserHasAccessOfBadgeRequestBucket()
    {
        $id = Auth::user()->id;
        $badgeConstant = config('constant')['badgeLocations'];
        $badgeLocations = [];
        foreach ($badgeConstant as $key => $value) {
            $badgeLocations[] = $key;
        }
        $user = User::where('id', $id)->whereRaw('FIND_IN_SET(?, branchid)', $badgeLocations)->first();
        if (isset($user->id)) {
            $branches = explode(',', $user->branchid);
            $branchId = array_intersect($badgeLocations, $branches);
            if (isset($branchId[0])) {
                return $badgeConstant[$branchId[0]];
            } else {
                return [];
            }
        } else {
            return [];
        }
    }

    public function getRecruiterByPolygon($address)
    {
        H::ExcelLog('in getRecruiterByPolygon: ' . print_r($address, true), 'tsAssignRecruiterNTH.log');
        $latLong = Geocoding::getGeoGoogle(implode(' ', $address));

        H::ExcelLog(print_r($latLong, true), 'tsAssignRecruiterNTH.log');
        $mapPolygons = [];
        if (LD::checkFeatureFlag('map-polygon-usertype-client-care-coordinator')) {
            if ($this->isNTHMap("maps_nth")) {
                $mapPolygons = DB::table("polygons")->select("polygons.*")
                    ->leftJoin("map_configurations", "map_configurations.map_id", "=", "polygons.map_id")
                    ->where("map_configurations.map_tablename", "maps_nth")
                    ->where(function ($q) {
                        $q->where("user_type", "recruiter")->orWhereNull("user_type")->orWhere("user_type", '');
                    })->get();
            }
        } else {
            if ($this->isNTHMap("maps_nth")) {
                $mapPolygons = DB::table("polygons")->select("polygons.*")
                    ->leftJoin("map_configurations", "map_configurations.map_id", "=", "polygons.map_id")
                    ->where("map_configurations.map_tablename", "maps_nth")->get();
            }
        }


        // addded for testing other
        $points = $latLong['lat'] . " " . $latLong['lng'];

        foreach ($mapPolygons as $polygons) {
            $polygonData = json_decode($polygons->coordinates, true);
            H::ExcelLog(print_r($polygons->name, true), 'tsAssignRecruiterNTH.log');
            H::ExcelLog(print_r($polygonData, true), 'tsAssignRecruiterNTH.log');
            $polygon = [];
            $counter = 0;
            $firstX = "";
            $firstY = "";
            foreach ($polygonData as $xy) {
                $polygon[] = $xy['lat'] . ' ' . $xy['lng'];
                if ($counter == 0) {
                    $firstX = $xy['lat'];
                    $firstY = $xy['lng'];
                }
                $counter++;
            }

            $polygon[] = $firstX . ' ' . $firstY;

            if ($this->pointInPolygon($points, $polygon)) {
                $polygonName = explode("-", $polygons->name);
                $recruiters = $this->getRecruitersList(true);
                foreach ($recruiters as $recruiter) {
                    H::ExcelLog($recruiter['text'], 'tsAssignRecruiterNTH.log');
                    if ($recruiter['text'] == trim($polygonName[0])) {
                        H::ExcelLog('matched!', 'tsAssignRecruiterNTH.log');
                        return $recruiter['value'];
                    }
                }
                H::ExcelLog(trim($polygonName[0]) . ' NOT MATCHED!', 'tsAssignRecruiterNTH.log');
                H::ExcelLog('in getRecruiterByPolygon: ' . print_r($address, true), 'tsAssignRecruiterNTH.log');
            }
        }
        return 0;
    }

    public function autoAssignCoverageCoordinatorMap($polygonId)
    {
        $coverageClinician = DB::table("maps_covclin")->select("mssql_providers.id", "lat", "lng")
            ->leftJoin("mssql_providers", "mssql_providers.ClinicianID", "=", "maps_covclin.ClinicianID")
            ->where("is_ignored", "!=", 1)
            ->where("maps_covclin.EmploymentStatus", "Active Coverage")
            ->where("mssql_providers.EmploymentStatusID", "24")
            ->whereNotNull("lat")->whereNotNull("lng")->get();

        $polygonD = DB::table("polygons")->select("*")->where("id", $polygonId)->first();
        $polygonData = json_decode($polygonD->coordinates, true);

        $polygon = [];
        $counter = 0;
        $firstX = "";
        $firstY = "";
        foreach ($polygonData as $xy) {
            $polygon[] = $xy['lat'] . ' ' . $xy['lng'];
            if ($counter == 0) {
                $firstX = $xy['lat'];
                $firstY = $xy['lng'];
            }
            $counter++;
        }

        $polygon[] = $firstX . ' ' . $firstY;

        $matchClinician = [];
        $ccId = 0;
        foreach ($coverageClinician as $clinician) {
            $points = $clinician->lat . " " . $clinician->lng;
            if ($this->pointInPolygon($points, $polygon)) {
                $polygonName = $this->extractCoordinatorName($polygonD->name);
                $matchClinician[] = $clinician->id;

                H::ExcelLog(trim($polygonName[0]) . ' MATCHED ' . $clinician->id, 'autocoverageclinician.log');
            }
        }

        if (!empty($matchClinician)) {
            $polygonName = $this->extractCoordinatorName($polygonD->name);
            $coordinators = $this->getCoverageCoordinatorList();
            foreach ($coordinators as $coordinator) {
                if ($coordinator['text'] == trim($polygonName)) {
                    $ccId = $coordinator['value'];
                }
            }
        }

        return [$matchClinician, $ccId];
    }

    public function extractCoordinatorName($name): string
    {
        $segments = explode('-', $name);

        if (count($segments) === 3) {
            unset($segments[count($segments) - 1]);

            return trim(implode('-', $segments));
        }

        return trim($segments[0]);
    }

    public function isInPolygon($points_polygon, $vertices_x, $vertices_y, $longitude_x, $latitude_y)
    {
        $i = $j = $c = 0;
        for ($i = 0, $j = $points_polygon; $i < $points_polygon; $j = $i++) {
            if ((($vertices_y[$i] > $latitude_y != ($vertices_y[$j] > $latitude_y)) &&
                ($longitude_x < ($vertices_x[$j] - $vertices_x[$i]) * ($latitude_y - $vertices_y[$i]) / ($vertices_y[$j] - $vertices_y[$i]) + $vertices_x[$i]))) {
                $c = !$c;
            }
        }
        return $c;
    }

    function pointInPolygon($point, $polygon, $pointOnVertex = true)
    {
        // Transform string coordinates into arrays with x and y values
        $point = $this->pointStringToCoordinates($point);
        $vertices = array();
        foreach ($polygon as $vertex) {
            $vertices[] = $this->pointStringToCoordinates($vertex);
        }

        // Check if the point sits exactly on a vertex
        if ($pointOnVertex == true and $this->pointOnVertex($point, $vertices) == true) {
            return 1;
        }

        // Check if the point is inside the polygon or on the boundary
        $intersections = 0;
        $vertices_count = count($vertices);

        for ($i = 1; $i < $vertices_count; $i++) {
            $vertex1 = $vertices[$i - 1];
            $vertex2 = $vertices[$i];
            if ($vertex1['y'] == $vertex2['y'] and $vertex1['y'] == $point['y'] and $point['x'] > min(
                    $vertex1['x'],
                    $vertex2['x']
                ) and $point['x'] < max(
                    $vertex1['x'],
                    $vertex2['x']
                )) { // Check if point is on an horizontal polygon boundary
                return 1;
            }
            if ($point['y'] > min($vertex1['y'], $vertex2['y']) and $point['y'] <= max(
                    $vertex1['y'],
                    $vertex2['y']
                ) and $point['x'] <= max($vertex1['x'], $vertex2['x']) and $vertex1['y'] != $vertex2['y']) {
                $xinters = ($point['y'] - $vertex1['y']) * ($vertex2['x'] - $vertex1['x']) / ($vertex2['y'] - $vertex1['y']) + $vertex1['x'];
                if ($xinters == $point['x']) { // Check if point is on the polygon boundary (other than horizontal)
                    return 1;
                }
                if ($vertex1['x'] == $vertex2['x'] || $point['x'] <= $xinters) {
                    $intersections++;
                }
            }
        }
        // If the number of edges we passed through is odd, then it's in the polygon.
        if ($intersections % 2 != 0) {
            return 1;
        } else {
            return 0;
        }
    }

    function pointOnVertex($point, $vertices)
    {
        foreach ($vertices as $vertex) {
            if ($point == $vertex) {
                return true;
            }
        }
    }

    function pointStringToCoordinates($pointString)
    {
        $coordinates = explode(" ", $pointString);
        return array("x" => $coordinates[0], "y" => $coordinates[1]);
    }

    /**
     * @return array
     */
    public function getPayerGroup(): array
    {
        $payerGroupList = [];
        $payerGroups = MssqlPatients::distinct()->select('payer_group')
            ->where('payer_group', '!=', '')
            ->orderBy('payer_group')
            ->cursor();

        foreach ($payerGroups as $payerGroup) {
            $payerGroupList[] = ['value' => $payerGroup['payer_group'], 'text' => $payerGroup['payer_group']];
        }

        return $payerGroupList;
    }

    /**
     * @return array
     */
    public function getPayerGroupForNRR(): array
    {
        $payerGroupList = [];
        $payerGroups = DB::table('payment_source')->distinct()->select('payergroup_name')
            ->where('payergroup_name', '!=', '')
            ->orderBy('payergroup_name')
            ->cursor();

        foreach ($payerGroups as $payerGroup) {
            $payerGroupList[] = ['value' => $payerGroup->payergroup_name, 'text' => $payerGroup->payergroup_name];
        }

        return $payerGroupList;
    }

    public function getRegion($fieldValue = 'region_name', $showall = false)
    {
        $subQueryState = "(select State from mssql_facilities f where f.region = regions.id limit 1) as State";
        if ($showall) {
            $getRegionList = DB::table('regions')->select("*", DB::raw($subQueryState))->get()->toArray();
        } else {
            $getRegionList = DB::table('regions')->select("*", DB::raw($subQueryState))->whereRaw(
                "(select State from mssql_facilities f where f.region = regions.id limit 1) = 'TX'"
            )->get()->toArray();
        }

        $getRegionList = H::objToArr($getRegionList);
        $regions = [];
        foreach ($getRegionList as $getRegionData) {
            $regions[] = ["value" => $getRegionData[$fieldValue], "text" => $getRegionData['region_name']];
        }
        return $regions;
    }

    public function getTherapyRegions($fieldValue = 'id')
    {
        $subQueryState = "(select State from mssql_facilities f where f.region = regions.id limit 1) as State";
        $getRegionList = DB::table('regions')
            ->select("*", DB::raw($subQueryState))
            ->whereRaw("(select State from mssql_facilities f where f.region = regions.id limit 1) IN ('CO', 'TX')")
            ->get()->toArray();

        $getRegionList = H::objToArr($getRegionList);
        $regions = [];
        foreach ($getRegionList as $getRegionData) {
            $regions[] = ["value" => $getRegionData[$fieldValue], "text" => $getRegionData['region_name']];
        }
        return $regions;
    }

    public function getTriageReason()
    {
        $reason = DB::table('reasons')->get()->toArray();
        $collection = new Collection($reason);

        $cleanedAndSorted = $collection->map(function ($item) {
            $item->reason = str_replace('"', '', $item->reason);
            return $item;
        })->sortBy('reason')->values()->toArray();

        return H::objToArr($cleanedAndSorted);
    }

    public function getTriageFollowupType($followup)
    {
        $data = [
            '1D' => '24 Hours',
            '2D' => '48 Hours',
            '7D' => '7 Day',
            '30D' => '30 Day',
        ];
        return isset($data[$followup]) ? $data[$followup] : '';
    }

    public function getJobsBranches()
    {
        // PD-5392: unique canonical names (see constant.jobvite_branch_aliases)
        return $this->getJobviteBranchStats()
            ->pluck('branch')
            ->unique()
            ->sort()
            ->values()
            ->map(fn($branch) => ['value' => $branch, 'text' => $branch])
            ->all();
    }

    public function getPreviousSteps($index)
    {
        $data = [
            '1D' => '24 Hours',
            '2D' => '48 Hours',
            '7D' => '7 Day',
            '30D' => '30 Day',
        ];
        $stepData = [];

        foreach ($data as $key => $value) {
            if ($key == $index) {
                return array_reverse($stepData);
            }
            $stepData[$key] = $value;
        }
    }

    public function getBodySystems()
    {
        $data = config("constant.bodySystem");
        $bodySystems = [];

        sort($data);

        foreach ($data as $value) {
            $bodySystems[] = ['value' => $value, "text" => $value];
        }
        return $bodySystems;
    }

    public function getFollowupStatus()
    {
        $data = config("constant.followupAction");
        $bodySystems = [];

        sort($data);

        foreach ($data as $value) {
            $bodySystems[] = ['value' => $value, "text" => $value];
        }
        return $bodySystems;
    }

    public function getMeetingNurses()
    {
        $meetingNursesData = [];
        $seenValues = [];
        $meetingNurses = MssqlPatients::select('CF_MeetingNurses')->whereNotNull('CF_MeetingNurses')->where(
            'CF_MeetingNurses',
            '!=',
            ''
        )->distinct()->get();
        foreach ($meetingNurses as $meetingNurse) {
            $value = str_replace("\u{2013}", '-', $meetingNurse->CF_MeetingNurses);
            if (isset($seenValues[$value])) {
                continue;
            }

            $seenValues[$value] = true;
            $meetingNursesData[] = [
                "value" => $value,
                'text' => $value
            ];
        }
        return $meetingNursesData;
    }

    public function getDiscipline()
    {
        $disciplineData = [];
        $disciplines = MssqlProviders::select('DisciplineName')->whereNotNull('DisciplineName')->where(
            'DisciplineName',
            '!=',
            ''
        )->distinct()->get();
        foreach ($disciplines as $discipline) {
            $disciplineData[] = ["value" => $discipline->DisciplineName, 'text' => $discipline->DisciplineName];
        }
        return $disciplineData;
    }

    public function getDisciplineForZeroRates(): array
    {
        $disciplineData = [];

        foreach (DisciplineEnum::zeroRates() as $discipline) {
            $disciplineData[] = ['value' => $discipline->value, 'text' => $discipline->value];
        }
        return $disciplineData;
    }

    public function getProgressBar($request)
    {
        $progress = 0;
        $total = 0;
        $whereRawQueryCondition = "(mssql_facilities.State <> 'CO' OR mssql_patients.LOBID = 1023)";
        $blankDateTime = ' 00:00:00';
        $progressType = $request['bucketType'];
        $ignoreOutcomeList = [
            'No Longer on Case',
            'Candidate already established with client',
            'Completed',
            'Unable to contact'
        ];
        if ($progressType == 'monthly_calls') {
            $fday = date("Y-m-1 00:00:00");
            $exceptLineOfBusiness = ['PT', 'OT', 'ST'];
            if (LD::checkFeatureFlag('ccc_monthly_call_remove_att')) {
                array_push($exceptLineOfBusiness, 'Attendant Care');
            }
            $patient = DB::table("mssql_patients");
            $patient->leftJoin("lob", function ($query) {
                $query->on("lob.LOBID", "=", "mssql_patients.LOBID");
            });
            $patient->leftJoin("mssql_facilities", function ($query) {
                $query->on("mssql_patients.aoc_facility_id", "=", "mssql_facilities.id");
            });
            $patient->leftJoin("users", function ($query) {
                $query->on("users.id", "=", "mssql_patients.ccc_id");
            });
            $patient->whereNotIn("lob.LOBName", $exceptLineOfBusiness);
            if (LD::checkFeatureFlag('cc-dashboard-remove-tx-skilled-visits-branch-monthly-calls')) {
                $patient->whereNotIn("mssql_patients.aoc_facility_id", ['14']);
            }
            if (LD::checkFeatureFlag('remove-2-FL-branch-client-from-monthly-call')) {
                $patient->whereNotIn("mssql_patients.aoc_facility_id", ['43', '44']);
            }
            $patient->where("mssql_patients.created_at", "<", $fday);
            $patient->whereIn("mssql_patients.Patient_CurrentStatusID", [1, 6, 7, 8]);
            $patient->where("mssql_patients.IsNonAdmit", '<>', 1);
            $patient->where("isPRN", 0);
            $patient->whereRaw("mssql_patients.Search_Name not like '%PRN%'");
            $patient->whereRaw("mssql_patients.Search_Name not like '%Test%'");
            $patient->whereRaw("mssql_patients.Search_Name not like '%KanTime%'");
            $patient->whereRaw($whereRawQueryCondition);
            if (LD::checkFeatureFlag('client-new-changes')) {
                $patient->where('mssql_patients.IsLatestAdmit', 1);
            }
            $patient = $this->addProgressFilters($patient, $request);
            H::ExcelLog('monthly calls ProgressBar total : ' . $patient->toSql(), 'ProgressBar.log');
            $patient->get();
            $total = $patient->count();

            $PatientMonthly = DB::table("mssql_patients");
            $PatientMonthly->leftJoin("lob", function ($query) {
                $query->on("lob.LOBID", "=", "mssql_patients.LOBID");
            });
            $PatientMonthly->leftJoin("mssql_facilities", function ($query) {
                $query->on("mssql_patients.aoc_facility_id", "=", "mssql_facilities.id");
            });
            $PatientMonthly->leftJoin("users", function ($query) {
                $query->on("users.id", "=", "mssql_patients.ccc_id");
            });
            $PatientMonthly->whereNotIn("lob.LOBName", $exceptLineOfBusiness);
            if (LD::checkFeatureFlag('remove-2-FL-branch-client-from-monthly-call')) {
                $patient->whereNotIn("mssql_patients.aoc_facility_id", ['43', '44']);
            }
            $PatientMonthly->where("mssql_patients.created_at", "<", $fday);
            $PatientMonthly->whereIn("mssql_patients.Patient_CurrentStatusID", [1, 6, 7, 8]);
            $PatientMonthly->where("mssql_patients.IsNonAdmit", '<>', 1);
            $PatientMonthly->where("isPRN", 0);
            $PatientMonthly = $this->addProgressFilters($PatientMonthly, $request);
            $PatientMonthly->whereNotIn("lob.LOBName", $exceptLineOfBusiness);
            $PatientMonthly->whereRaw("mssql_patients.Search_Name not like '%PRN%'");
            $PatientMonthly->whereRaw("mssql_patients.Search_Name not like '%Test%'");
            $PatientMonthly->whereRaw("mssql_patients.Search_Name not like '%KanTime%'");
            $PatientMonthly->whereRaw($whereRawQueryCondition);
            if (LD::checkFeatureFlag('client-new-changes')) {
                $PatientMonthly->where('mssql_patients.IsLatestAdmit', 1);
            }
            $PatientMonthly->whereRaw(
                '(last_monthly_followup >= ? OR (mssql_patients.attempt_count = 3 AND mssql_patients.monthly_follow_up_attempts >= ?))',
                [$fday . $blankDateTime, $fday . $blankDateTime]
            )->get();
            H::ExcelLog('monthly calls ProgressBar progress : ' . $PatientMonthly->toSql(), 'ProgressBar.log');
            $progress = $PatientMonthly->count();
        } else {
            if ($progressType == 'weekly_nth_calls') {
                $fday = date('Y-m-d', strtotime("this week"));

                $NTH = DB::table("NeedToHire_Accounts")->select("NeedToHire_Accounts.*");
                $NTH->leftJoin("mssql_patients", function ($q) {
                    $q->on("NeedToHire_Accounts.aoc_patient_id", "=", "mssql_patients.id");
                });
                $NTH->whereIn("mssql_patients.Patient_CurrentStatusID", [1, 6, 7, 8]);
                $NTH->where("NeedToHire_Accounts.created_at", "<", $fday . $blankDateTime);
                $NTH->leftJoin("mssql_facilities", function ($query) {
                    $query->on("mssql_patients.aoc_facility_id", "=", "mssql_facilities.id");
                });
                $NTH->whereRaw($whereRawQueryCondition);
                $NTH = $this->addProgressFilters($NTH, $request);

                $NTH->where("NeedToHire_Accounts.isActive", '=', 1)->get();
                $total = $NTH->count();

                $NTHWeekly = DB::table("NeedToHire_Accounts")->select("NeedToHire_Accounts.*")
                    ->leftjoin("mssql_patients", function ($q) {
                        $q->on("NeedToHire_Accounts.aoc_patient_id", "=", "mssql_patients.id");
                    });
                $NTHWeekly->leftJoin("mssql_facilities", function ($query) {
                    $query->on("mssql_patients.aoc_facility_id", "=", "mssql_facilities.id");
                });
                $NTHWeekly = $this->addProgressFilters($NTHWeekly, $request);

                $NTHWeekly->where("NeedToHire_Accounts.isActive", '=', 1);
                $NTHWeekly->whereIn("mssql_patients.Patient_CurrentStatusID", [1, 6, 7, 8]);
                $NTHWeekly->where("NeedToHire_Accounts.created_at", "<", $fday . $blankDateTime);

                $NTHWeekly->whereRaw($whereRawQueryCondition);
                $NTHWeekly->whereRaw(
                    '(last_weekly_followup >= ? OR (NeedToHire_Accounts.attempt_count >= 2 AND NeedToHire_Accounts.weekly_follow_up_attempts >= ?))',
                    [$fday . $blankDateTime, $fday . $blankDateTime]
                )->get();

                $progress = $NTHWeekly->count();
            } else {
                if ($progressType == 'first_shift_follow_up') {
                    //get total record.
                    $getTotalCount = RecruitingSchedule::getCandidateStartQuery($request);
                    $getTotalCount->whereDate("recruiting_schedule.appointment_date", ">", '2023-03-24');
                    $total = $getTotalCount->count();

                    //get completed record.
                    $getCompletedCount = RecruitingSchedule::getCandidateStartQuery($request);
                    $getCompletedCount->whereIn("first_followup_outcome", $ignoreOutcomeList);
                    $getCompletedCount->whereDate("recruiting_schedule.appointment_date", ">", '2023-03-24');
                    $progress = $getCompletedCount->count();
                } else {
                    if ($progressType == 'seven_day_follow_up') {
                        $startAppDate = date("Y-m-d", strtotime("-6 days"));
                        $endAppDate = date("Y-m-d", strtotime("-30 days"));
                        //get total record.
                        $getTotalCount = RecruitingSchedule::getCandidateStartQuery($request);
                        $getTotalCount->whereDate("recruiting_schedule.appointment_date", "<", $startAppDate)
                            ->whereDate("recruiting_schedule.appointment_date", ">", '2023-03-17');
                        $total = $getTotalCount->count();

                        //get completed record.
                        $getCompletedCount = RecruitingSchedule::getCandidateStartQuery($request);
                        $getCompletedCount->whereIn("sevenday_followup_outcome", $ignoreOutcomeList);
                        $getCompletedCount->whereDate("recruiting_schedule.appointment_date", "<", $startAppDate)
                            ->whereDate("recruiting_schedule.appointment_date", ">", '2023-03-17');
                        $progress = $getCompletedCount->count();
                    } else {
                        if ($progressType == 'thirty_day_follow_up') {
                            $endAppDate = date("Y-m-d", strtotime("-29 days"));
                            //get total record.
                            $getTotalCount = RecruitingSchedule::getCandidateStartQuery($request);
                            $getTotalCount->whereDate("recruiting_schedule.appointment_date", "<", $endAppDate)
                                ->whereDate("recruiting_schedule.appointment_date", ">", '2023-02-23');
                            $total = $getTotalCount->count();

                            //get completed record.
                            $getCompletedCount = RecruitingSchedule::getCandidateStartQuery($request);
                            $getCompletedCount->whereIn("thirtyday_followup_outcome", $ignoreOutcomeList);
                            $getCompletedCount->whereDate("recruiting_schedule.appointment_date", "<", $endAppDate)
                                ->whereDate("recruiting_schedule.appointment_date", ">", '2023-02-23');
                            $progress = $getCompletedCount->count();
                        }
                    }
                }
            }
        }

        if ($total > 0) {
            H::ExcelLog('monthly calls ProgressBar progress count : ' . $progress, 'ProgressBar.log');
            H::ExcelLog('monthly calls ProgressBar total count : ' . $total, 'ProgressBar.log');
            $percentage = round(($progress / $total) * 100, 0);
            H::ExcelLog('monthly calls ProgressBar percentage : ' . $percentage, 'ProgressBar.log');
            return [
                "html" => '<div class="progress"><div class="progress-bar" role="progressbar" style="width: ' . $percentage . '%;" aria-valuenow="' . $percentage . '" aria-valuemin="0" aria-valuemax="100">' . $percentage . '%</div></div>',
                "percentage" => $percentage
            ];
        } else {
            $percentage = 0;
            return ["html" => '', "percentage" => $percentage];
        }
    }

    public function getPatientIdByAccountId($clientId)
    {
        $patient = MssqlPatients::select('id')->where('PatientAccount', $clientId)->first();
        return isset($patient->id) ? $patient->id : '';
    }

    public function nthRemovedAllHours($aoc_nthaccounts_id, $reason)
    {
        $loggedInUser = H::getLoggedInUser();
        if (isset($loggedInUser['id'])) {
            $userId = $loggedInUser['id'];
        } else {
            $userId = 1;
        }
        H::ExcelLog('removing schedules for ' . $aoc_nthaccounts_id . ' - ' . $reason, 'nthRemoveAllHours.log');
        $items = DB::table("NeedToHire_Schedules")->where('aoc_nthaccounts_id', $aoc_nthaccounts_id)->where(
            'IsActive',
            1
        )->get()->toArray();
        foreach ($items as $item) {
            $currentData = $item;
            DB::table("NeedToHire_Schedules")->where("id", $item->id)->update(
                [
                    "updated_at" => date(config('constant')['longdatetimeformat']),
                    "IsActive" => "0",
                    'RemovalReason' => $reason,
                    'updated_by' => $userId
                ]
            );
            H::getHelper('ConstantHelper')->setNTHAuditRecord($item->id, 'remove', $currentData);
            H::getHelper('NTHChecklist')->ignoreNTHScheduleReason($reason, $currentData);
        }
        if (isset(H::getLoggedInUser()['name'])) {
            $userName = H::getLoggedInUser()['name'];
        } else {
            $userName = 'App Bot';
        }
        $notes = $userName . ' removed ALL schedules on the NTH for reason: ' . $reason;
        $notesData = [
            'entity' => 'NeedToHire',
            'entity_id' => $aoc_nthaccounts_id,
            'notes' => $notes,
            'completed' => 1
        ];
        H::getHelper('ActivityNotes')->setActivityNotes($notesData);

        NeedToHireAccounts::where('id', $aoc_nthaccounts_id)->update(
            ["updated_at" => date(config('constant')['longdatetimeformat']), 'IsActive' => '0', 'updated_by' => $userId]
        );
        DB::table("maps_nth")->where("aoc_nthaccounts_id", $aoc_nthaccounts_id)->update(['IsActive' => '0']);
    }

    public function getYesNoOptions()
    {
        $options = [];
        $options[] = ['value' => 1, 'text' => 'Yes'];
        $options[] = ['value' => 0, 'text' => 'No'];

        return $options;
    }

    public function getProviderNameByID($id)
    {
        $arr = MssqlProviders::select(DB::raw('concat(FirstName," ",LastName) as name'))->where('id', $id)->first();
        if ($arr) {
            $arr = $arr->toArray();
        } else {
            $arr['name'] = '';
        }
        return isset($arr['name']) ? $arr['name'] : '';
    }

    public function getChecklist()
    {
        $fields = [
            'hard_stop' => 'Hard Stop Adjusted',
            'pay_rate_entered_in_kantime' => 'Pay Rate Entered',
            'right_signature_sent' => 'Right Signature Sent',
            'first_week_created' => 'First Week Created'
        ];

        return $fields;
    }

    public function getCurrentRolesByUserId($userId): array
    {
        $roles = Cache::tags(['{roles_cache}'])->rememberForever('user_'.$userId.'_roles', function () use ($userId) {
           return DB::table('users_has_roles')
               ->select('role_id')
               ->where('user_id', $userId)
               ->get()
               ->toArray();
        });

        return array_column($roles, 'role_id');
    }

    public function getCurrentRolesNameByUserId($userId)
    {
        $roles = DB::table('roles')->select('roles.name')->leftJoin(
            'users_has_roles',
            'users_has_roles.role_id',
            '=',
            'roles.id'
        )->where('users_has_roles.user_id', $userId)->get()->toArray();
        return array_column($roles, 'name');
    }

    public function getUserIdsByRoles($roles)
    {
        $roleIds = DB::table("roles")->select("id")->whereIn("name", $roles)->get()->toArray();
        $roleIds = array_column($roleIds, 'id');
        $users = DB::table('users_has_roles')->select('user_id')->whereIn('role_id', $roleIds)->get()->toArray();
        return array_column($users, 'user_id');
    }

    //TODO CHECK THAT GUY USAGE, IT VERY POSSIBLE THAT WE HAVE A BUG HERE
    public function getIsUserInRole($role, $user_id)
    {
        $roleId = DB::table("roles")->select("id")->where("name", $role)->first();
        $users = DB::table('users_has_roles')->select('user_id')->where('role_id', $roleId->id)->first();

        if (!empty($users)) {
            return 1;
        } else {
            return 0;
        }
    }

    public function getIsUserInRoleSafe($role, $user_id)
    {
        $roleId = DB::table("roles")->select("id")->where("name", $role)->first();
        $users = DB::table('users_has_roles')->select('user_id')->where('role_id', $roleId->id)->where('user_id', $user_id)->first();

        if (!empty($users)) {
            return 1;
        } else {
            return 0;
        }
    }

    public function getUserIdsByRolesAndFacility($roles, $facililtyId)
    {
        $roleIds = DB::table("roles")->select("id")->whereIn("name", $roles)->get()->toArray();
        $roleIds = array_column($roleIds, 'id');
        $users = DB::table('users_has_roles')->select('user_id')
            ->leftJoin("users", "users.id", "=", "users_has_roles.user_id")->whereRaw(
                'FIND_IN_SET("' . $facililtyId . '", users.facilityid)'
            )->whereIn('role_id', $roleIds)->get()->toArray();

        return array_column($users, 'user_id');
    }

    public function getUserIdsByFacility($facilityId)
    {
        $users = DB::table('users')->select('id')->where('facilityid', $facilityId)->get()->toArray();
        return array_column($users, 'id');
    }

    public function getStateByPatientId($patientId)
    {
        $facility = DB::table("mssql_patients")->select("mssql_facilities.State")
            ->leftJoin("mssql_facilities", "mssql_facilities.id", "=", "mssql_patients.aoc_facility_id")
            ->where("mssql_patients.id", $patientId)->first();
        return $facility->State;
    }

    public function addProgressFilters($query, $request)
    {
        $branch = [];
        $state = [];
        $users = [];
        $payer = [];
        $payerGroup = [];
        $isMeMode = 0;
        $region = [];


        if (isset($request['filters'])) {
            foreach ($request['filters'] as $key => $value) {
                if ($value['name'] == 'ccstate' || $value['name'] == 'weeklyccstate' || $value['name'] == 'monthlyccstate') {
                    $state[] = $value['value'];
                }
                if ($value['name'] == 'ccbranch' || $value['name'] == 'weeklyccbranch' || $value['name'] == 'monthlyccbranch') {
                    $branch[] = $value['value'];
                }
                if ($value['name'] == 'clientcare' || $value['name'] == 'weeklyclientcare' || $value['name'] == 'monthlyclientcare') {
                    $users[] = $value['value'];
                }
                if ($value['name'] == 'ccregion' || $value['name'] == 'weeklyregion' || $value['name'] == 'monthlyccregion') {
                    $region[] = $value['value'];
                }
                if ($value['name'] == 'weeklypayer' || $value['name'] == 'monthlypayer') {
                    $payer[] = $value['value'];
                }
                if ($value['name'] == 'weeklypayergroup' || $value['name'] == 'monthlypayergroup') {
                    $payerGroup[] = $value['value'];
                }
                if ($value['name'] == 'monthlymemode' || $value['name'] == 'weeklymemode') {
                    $isMeMode = 1;
                }
            }
        }

        if (count($state) > 0) {
            $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
            $query->where(function ($q) use ($facilities) {
                foreach ($facilities as $facility) {
                    $q->orWhere("mssql_patients.aoc_facility_id", "=", $facility->id);
                }
            });
        }
        if (count($branch) > 0) {
            $query->where(function ($q) use ($branch) {
                foreach ($branch as $b) {
                    $q->orWhere("mssql_patients.aoc_facility_id", "=", $b);
                }
            });
        }

        if (count($region) > 0) {
            $query->where(function ($q) use ($region) {
                foreach ($region as $b) {
                    $q->orWhere("mssql_facilities.region", "=", $b);
                }
            });
        }

        if (!empty($users) && !$isMeMode) {
            $query->whereIn("mssql_patients.ccc_id", $users);
        } else {
            if ($isMeMode) {
                $query->where("mssql_patients.ccc_id", '=', Auth::user()->id);
            }
        }

        if (!empty($payer)) {
            $query->where(function ($q) use ($payer) {
                foreach ($payer as $t) {
                    $q->orWhere("mssql_patients.Payer", "=", $t);
                }
            });
        }

        if (!empty($payerGroup)) {
            $query->where(function ($q) use ($payerGroup) {
                foreach ($payerGroup as $t) {
                    $q->orWhere("mssql_patients.payer_group", "=", $t);
                }
            });
        }
        return $query;
    }

    public function getAdsType()
    {
        $data = config("constant.adsType");
        $adsType = [];
        sort($data);
        foreach ($data as $value) {
            $adsType[] = ['value' => $value, "text" => $value];
        }
        return $adsType;
    }

    public function getSocialMediaDiscipline()
    {
        $socialMediaDisciplines = SocialMedia::distinct()->select('discipline')
            ->where('discipline', '!=', '')
            ->orderBy('discipline')
            ->get()->toArray();
        $socialMediaDisciplineList = [];
        foreach ($socialMediaDisciplines as $socialMediaDiscipline) {
            $socialMediaDisciplineList[] = [
                "value" => $socialMediaDiscipline['discipline'],
                'text' => $socialMediaDiscipline['discipline']
            ];
        }
        return $socialMediaDisciplineList;
    }

    public function getSocialMediaCategory()
    {
        $socialMediaCategories = SocialMedia::distinct()->select('category')
            ->where('category', '!=', '')
            ->orderBy('category')
            ->get()->toArray();
        $socialMediaCategoryList = [];
        foreach ($socialMediaCategories as $socialMediaCategory) {
            $socialMediaCategoryList[] = [
                "value" => $socialMediaCategory['category'],
                'text' => $socialMediaCategory['category']
            ];
        }
        return $socialMediaCategoryList;
    }

    public function getSNSCCRCheckbox($index = '')
    {
        $data = [
            'plan_care' => 'Plan of Care reviewed with clinician (including medications, equipment, interventions and goals)',
            'timely_documentation' => 'Reviewed appropriate and timely documentation. (gave any updates in charting from previous patient to new patient - provided education on any charting issues thus far)',
            'reviewed_understanding_care_coordination' => 'Reviewed understanding of care coordination and advocating for all client needs',
            'reviewed_understanding_professional' => 'Reviewed understanding of professional boundaries and behavior as an employee. ',
            'clinician_is_competent' => LD::checkFeatureFlag('changes-to-ccr-form-for-pepr-process') ?
                'Clinician has required check-off on file' :
                'Clinician is competent in required skills and has required check-off on file',
            'clinician_knows_ccm_ccc' => 'Clinician knows who the CCM and CCC are and how to reach them',
            'clinician_knows_reach_hour' => 'Clinician knows when and how to reach after hours on-call',
            'kantime_login' => 'Clinician is able to login to KanTime',

        ];

        if (!empty($index)) {
            return $data[$index];
        } else {
            return $data;
        }
    }


    public function getRegionForDaily()
    {
        $getRegionList = DB::table('regions')->select(
            "*",
            DB::raw("(select State from mssql_facilities f where f.region = regions.id limit 1) as State")
        )->whereRaw("(select State from mssql_facilities f where f.region = regions.id limit 1) = 'TX'")->get(
        )->toArray();

        $getRegionList = H::objToArr($getRegionList);
        $regions = [];
        foreach ($getRegionList as $getRegionData) {
            $regions[] = ["value" => $getRegionData['id'], "text" => $getRegionData['region_name']];
        }
        return $regions;
    }

    public function getAssociatedKanTimeBranches()
    {
        $fid = MssqlFacilities::where('hide', 0)->get();
        return $fid;
    }

    public function getCCRRequestby()
    {
        $requestedByUsersCollection = DB::table('recruiting_schedule')->select(
            'recruiting_schedule.created_by',
            'users.name'
        )->distinct()->leftJoin("users", "users.id", "=", "recruiting_schedule.created_by")->where(
            'recruiting_schedule.created_by',
            '!=',
            ''
        )->where('recruiting_schedule.type_id', '=', '2')->orderBy('users.name')->get()->toArray();
        $requestedByUsersLists = [];
        foreach ($requestedByUsersCollection as $requestedByUser) {
            $requestedByUsersLists[] = ["value" => $requestedByUser->created_by, 'text' => $requestedByUser->name];
        }
        return $requestedByUsersLists;
    }

    public function getTimeZoneList()
    {
        $timezones = [];
        $timezone_identifiers = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, 'US');
        foreach ($timezone_identifiers as $timezone_identifier) {
            $local_tz = new \DateTimeZone(date_default_timezone_get());
            $local = new \DateTime('now', $local_tz);

            $user_tz = new \DateTimeZone($timezone_identifier);
            $user = new \DateTime('now', $user_tz);

            $local_offset = $local->getOffset() / 3600;
            $user_offset = $user->getOffset() / 3600;

            $diff = $local_offset - $user_offset;
            $timezones[$timezone_identifier . '#' . $diff] = $timezone_identifier;
        }

        return $timezones;
    }

    public function getUsersByRoles($roleArray)
    {
        $ccArray = $userList = [];
        if (LD::checkFeatureFlag('multiple-roles-to-user')) {
            $ccUser = User::select("users.id", "users.name")
                ->leftJoin('users_has_roles', 'users.id', '=', 'users_has_roles.user_id')
                ->leftJoin('roles', 'users_has_roles.role_id', '=', 'roles.id');
            $ccUser->where(function ($query) use ($roleArray) {
                foreach ($roleArray as $val) {
                    $query->orWhere("roles.name", '=', $val);
                }
            });
            $ccUser->where('status', 1)->orderBy('users.name');
            H::ExcelLog('getUsersByRoles: ' . $ccUser->toSql(), 'getUsersByRoles.log');
            $ccUser = $ccUser->get();
            foreach ($ccUser as $user) {
                $ccArray[] = ["value" => $user->id, 'text' => $user->name];
            }
        } else {
            $ccUser = User::select("users.id", "users.name")
                ->leftJoin('roles', 'users.role', '=', 'roles.id')
                ->leftJoin('roles', 'users_has_roles.role_id', '=', 'roles.id');
            $ccUser->where(function ($query) use ($roleArray) {
                foreach ($roleArray as $val) {
                    $query->orWhere("roles.name", '=', $val);
                }
            });
            $ccUser->where('status', 1)->orderBy('users.name');
            $ccUser = $ccUser->get();
            foreach ($ccUser as $user) {
                $ccArray[] = ["value" => $user->id, 'text' => $user->name];
            }
        }
        $temp = array_unique(array_column($ccArray, 'text'));
        $userList = array_intersect_key($ccArray, $temp);
        return $userList;
    }

    public function getTriageDisposition()
    {
        $triageDisposition = [];
        foreach (config('constant')['disposition'] as $v) {
            $triageDisposition[] = ["value" => $v, 'text' => $v];
        }
        return $triageDisposition;
    }

    public function getIpeaStatus()
    {
        $ipeaStatusList = DB::table('mssql_recruitertracker')->select('live_ipea_status')->distinct()->where(
            'live_ipea_status',
            '<>',
            ''
        )->orderBy('live_ipea_status', 'ASC')->get()->toArray();
        $ipeaStatusList = H::objToArr($ipeaStatusList);
        $status = [];
        if (LD::checkFeatureFlag('ipea-filter-group-selection')) {
            foreach ($ipeaStatusList as $ipeaStatusData) {
                if (strpos(strtolower($ipeaStatusData['live_ipea_status']), 'pending') === false) {
                    $status[] = [
                        "value" => $ipeaStatusData['live_ipea_status'],
                        "text" => $ipeaStatusData['live_ipea_status']
                    ];
                }
            }
            $status[] = ["value" => 'pending', "text" => 'Pending'];
        } else {
            foreach ($ipeaStatusList as $ipeaStatusData) {
                $status[] = [
                    "value" => $ipeaStatusData['live_ipea_status'],
                    "text" => $ipeaStatusData['live_ipea_status']
                ];
            }
        }

        usort($status, fn($a, $b) => $a['text'] <=> $b['text']);
        return $status;
    }

    public function getAllRoles()
    {
        $roles = DB::table('roles')->orderBy('name', 'ASC')->get();
        return $roles;
    }

    public function getTwilioUsersNumber()
    {
        $users = DB::table('users')->select(DB::raw('GROUP_CONCAT(sending_sms_number) as author'))->whereNotNull(
            'sending_sms_number'
        )->where('sending_sms_number', '!=', '')->get();
        return isset($users[0]) ? $users[0]->author : '';
    }

    public function getFieldEvaluationStatus()
    {
        $fielEvaluationStatus = config('constant.fieldEvaluationStatus');
        $status = [];
        foreach ($fielEvaluationStatus as $key => $value) {
            $status[] = ["value" => $value, "text" => $value];
        }
        return $status;
    }

    public function getOfficeEvaluationStatus()
    {
        $fielEvaluationStatus = config('constant.officeEvaluationStatus');
        $status = [];
        foreach ($fielEvaluationStatus as $key => $value) {
            $status[] = ["value" => $value, "text" => $value];
        }
        return $status;
    }

    public function getClientStatusById($id)
    {
        $data = DB::table('mssql_patients')->select('PatientStatus')->leftJoin(
            'mssql_patientstatus',
            'mssql_patients.Patient_CurrentStatusID',
            '=',
            'mssql_patientstatus.id'
        )->where('mssql_patients.id', $id)->first();
        return isset($data->PatientStatus) ? $data->PatientStatus : '';
    }

    public function getOfficeEvaluationSupervisor()
    {
        $fielEvaluationSupervisors = OfficeEvaluation::select(
            DB::Raw(
                "(SELECT CONCAT(id, '|' , name) from users where users.id IN (SELECT recruiting_manager from users where mssql_providers.AOC_Email = users.email)) as supervisor"
            ),
        )->distinct()
            ->leftJoin('mssql_providers', 'aoc_provider_id', '=', 'mssql_providers.id')->whereRaw(
                "(SELECT CONCAT(id, name) from users where users.id IN (SELECT recruiting_manager from users where mssql_providers.AOC_Email = users.email)) != ''"
            )->get()->toArray();
        $usersList = [];
        foreach ($fielEvaluationSupervisors as $key => $value) {
            list($id, $name) = explode("|", $value['supervisor']);
            $usersList[] = ["value" => $id, "text" => $name];
        }
        $key_values = array_column($usersList, 'text');
        array_multisort($key_values, SORT_ASC, $usersList);
        return $usersList;
    }

    public function getOfficeEvaluationManagers()
    {
        $fielEvaluationManagers = OfficeEvaluation::select(
            DB::Raw(
                "(SELECT CONCAT(id, '|' , name) from users where users.id IN (
                    SELECT manager_id from users where mssql_providers.AOC_Email = users.email)
                ) as managers"
            ),
        )->distinct()
            ->leftJoin('mssql_providers', 'aoc_provider_id', '=', 'mssql_providers.id')->whereRaw(
                "(SELECT CONCAT(id, name) from users where users.id IN (

                SELECT manager_id from users where mssql_providers.AOC_Email = users.email)

            ) != ''"
            )->get()->toArray();
        $usersList = [];
        foreach ($fielEvaluationManagers as $key => $value) {
            list($id, $name) = explode("|", $value['managers']);
            $usersList[] = ["value" => $id, "text" => $name];
        }
        $key_values = array_column($usersList, 'text');
        array_multisort($key_values, SORT_ASC, $usersList);
        return $usersList;
    }

    public function getCandidateNameByID($id)
    {
        $arr = Candidates::select(DB::raw('concat(firstName," ",lastName) as name'))->where('id', $id)->first();
        if ($arr) {
            $arr = $arr->toArray();
        } else {
            $arr['name'] = '';
        }
        return isset($arr['name']) ? $arr['name'] : '';
    }


    public function getOfficeEvaluationExecManagers()
    {
        $fielEvaluationManagers = OfficeEvaluation::select(
            DB::Raw(
                "(SELECT CONCAT(id, '|' , name) from users where users.id IN (
                SELECT recruiting_manager from users where users.id IN (
                    SELECT recruiting_manager from users where users.id IN (
                        SELECT recruiting_manager from users where mssql_providers.AOC_Email = users.email)
                    )
                )
            ) as exec_managers"
            ),
        )->distinct()
            ->leftJoin('mssql_providers', 'aoc_provider_id', '=', 'mssql_providers.id')->whereRaw(
                "
            (SELECT CONCAT(id, name) from users where users.id IN (
                SELECT recruiting_manager from users where users.id IN (
                    SELECT recruiting_manager from users where users.id IN (
                        SELECT recruiting_manager from users where mssql_providers.AOC_Email = users.email
                    )
                )
            )) != ''"
            )->get()->toArray();
        $usersList = [];
        foreach ($fielEvaluationManagers as $key => $value) {
            list($id, $name) = explode("|", $value['exec_managers']);
            $usersList[] = ["value" => $id, "text" => $name];
        }
        return $usersList;
    }

    public function getTwilioUsersWithName()
    {
        $users = DB::table('users')->select('sending_sms_number', 'name')->whereNotNull('sending_sms_number')->where(
            'sending_sms_number',
            '!=',
            ''
        )->get();
        $data = [];
        foreach ($users as $key => $value) {
            $data[$value->sending_sms_number] = $value->name;
        }
        return addslashes(json_encode($data));
    }

    public function convertBase64ToImageAndStore($base64Image, $id, $type)
    {
        $filename = '';
        try {
            if (!file_exists(storage_path('' . $type . '/'))) {
                mkdir(storage_path('' . $type . '/'), 0755);
            }
            $imageData = Str::of($base64Image)->after('base64,')->trim();
            preg_match('/data:image\/(.*?);/', $base64Image, $matches);
            $fileExtension = $matches[1];
            $filename = time() . $id . '.' . $fileExtension;
            $decodedImage = base64_decode($imageData);

            $filePath = storage_path('' . $type . '/' . $filename);
            file_put_contents($filePath, $decodedImage);
            if ($type == 'halos_image') {
                DB::table('notes')->where('id', $id)->update(['misc3' => $filename, 'misc4' => '']);
            } else {
                if ($type == 'feature_photos') {
                    $t = [$id, $filename];
                    DB::table('aoc_documents')->where('id', $id)->update(
                        ['document_filename' => $filename, 'document_link' => '']
                    );
                }
            }
            return $filename;
        } catch (\Exception $e) {
            H::ExcelLog($e->getMessage(), 'convertBase64ToImageAndStore.log');
            return '';
        }
    }

    public function getRenewableClinicianStatus()
    {
        $renewableClinicianStatus = config('constant.renewable_clinician_status');

        $status = [];
        foreach ($renewableClinicianStatus as $key => $value) {
            $status[] = ["value" => $value, "text" => $value];
        }
        return $status;
    }

    public function getRenewableItems()
    {
        $renewableTypes = DB::table("renewables_types")->select("renewables_types.id", "renewables_types.name")
            ->join('renewable_rules', 'renewables_types.id', '=', 'renewable_rules.renewable_type_id')
            ->where("is_deleted", "0")->distinct()->orderBy("renewables_types.name")->get();

        $type = [];
        foreach ($renewableTypes as $key => $value) {
            $type[] = ["value" => $value->id, "text" => $value->name];
        }
        return $type;
    }

    public function getPaycomKantimeDiscrepancyType()
    {
        $type = [
            ["value" => 'ID Discrepancy', "text" => 'ID Discrepancy'],
            ["value" => 'Employment Type Discrepancy', "text" => 'Employment Type Discrepancy'],
            ["value" => 'Status Discrepancy', "text" => 'Status Discrepancy'],
            ["value" => 'Location Discrepancy', "text" => 'Location Discrepancy'],
            ["value" => 'Official/Work Email Discrepancy', "text" => 'Official/Work Email Discrepancy']
        ];

        return $type;
    }

    public function getClientEmergencyContactDetails($aoc_patient_id)
    {
        $patient = DB::table('mssql_patients')
            ->select('BillingInternalPatientID', 'IntakeID')
            ->where('id', $aoc_patient_id)
            ->first();

        if (empty($patient)) {
            return collect();
        }

        $clientId = (int) ($patient->BillingInternalPatientID ?? 0);
        $intakeId = (int) ($patient->IntakeID ?? 0);

        if ($clientId === 0 && $intakeId === 0) {
            return collect();
        }

        // client_id is 0 for clients KanTime hasn't assigned a real Client ID to yet
        // (e.g. pending intakes) - matching on it would fan out across every such client,
        // so only intake_id (always unique per KanTime intake) is used in that case.
        return DB::table('client_emergency_contacts')
            ->select(
                'relation',
                'telephone',
                'alternate_phone',
                'work_phone',
                'priority',
                DB::raw("CONCAT(first_name, ' ', last_name) as full_name")
            )
            ->where(function ($query) use ($clientId, $intakeId) {
                if ($clientId !== 0) {
                    $query->orWhere('client_id', $clientId);
                }
                if ($intakeId !== 0) {
                    $query->orWhere('intake_id', $intakeId);
                }
            })
            ->get();
    }

    public function getExternalUserNameById($id, $field = 'name')
    {
        if ($id != '') {
            $user = DB::table('external_users')->where('id', $id)
                ->first();
            if (!empty($user)) {
                return $user->{$field};
            }
            return '';
        } else {
            return '';
        }
    }

    public function getUserListByRoles($roles, $needId = true)
    {
        $roleid = [];
        foreach ($roles as $role) {
            $department = Roles::where('name', $role)->first();
            if (!empty($department)) {
                if ($department->department != 0) {
                    $roleids = Roles::where('department', $department->department)->get();
                    foreach ($roleids as $id) {
                        $roleid[] = $id->id;
                    }
                } else {
                    $roleid[] = $department->id;
                }
            }
        }
        $usersArr = [];
        if (!empty($roleid)) {
            $users = User::select("users.id", "users.name")
                ->distinct()
                ->leftJoin('users_has_roles', 'users.id', '=', 'users_has_roles.user_id')
                ->leftJoin('roles', 'users_has_roles.role_id', '=', 'roles.id')
                ->whereIn('users_has_roles.role_id', $roleid)
                ->where('status', 1)
                ->orderBy('users.name')->get();
            foreach ($users as $user) {
                $usersArr[] = ["value" => $needId ? $user->id : $user->name, 'text' => $user->name];
            }
        }
        return $usersArr;
    }

    public function addNoteWhenIntakeUpdate($request)
    {
        $intakeData = Intake::where('id', $request['intakeid'])->first();
        $newref = self::checkNewRefOnLob($request['intakeid']);
        $patientData = MssqlPatients::where('id', $request['clientPId'])->first();
        $nthData = NeedToHireAccounts::where('id', $request['nthID'])->first();
        $icList = H::getHelper("ConstantHelper")->getIntakeCoordinator();
        $facilities = H::getHelper("ConstantHelper")->getFacilities();
        $skill = isset($request['skills']) ? implode(',', $request['skills']) : '';
        $clientName = $patientData->Patient_FirstName . ' ' . $patientData->Patient_LastName;
        $admissionVisitScheduled = 0;
        $addToNTH = 0;
        $clientName = $patientData->Patient_FirstName . ' ' . $patientData->Patient_LastName;
        if (isset($request['admissionVisitScheduled'])) {
            $admissionVisitScheduled = $request['admissionVisitScheduled'] == 'on' ? 1 : 0;
        }
        if (isset($request['addToNTH'])) {
            $addToNTH = $request['addToNTH'] == 'on' ? 1 : 0;
        }
        $labelArr = [
            'ic_id' => 'Intake Coordinator',
            'clientId' => 'Client ID',
            'clientFirstName' => 'Client FirstName',
            'clientLastName' => 'Client LastName',
            'address1' => 'Address Line 1',
            'address2' => 'Address Line 2',
            'city' => 'City',
            'state' => 'State',
            'zipCode' => 'Zip Code',
            'aoc_facility_id' => 'Branch',
            'linkToKanTime' => 'Link To KanTime',
            'notes' => 'Notes',
            'referralType' => 'Referral Type',
            'hospital' => 'Hospital',
            'hospitalDischargeDate' => 'Hospital Discharge Date',
            'anticipatedHours' => 'Anticipated Hours',
            'goalSocDate' => 'Goal SOC Date',
            'terminalPlayer' => 'Terminal Payer',
            'respiteTerminalPlayer' => 'Respite Terminal Payer',
            'admissionVisitScheduled' => 'Admission Visit Scheduled',
            'specializedClient' => 'Specialized Client?',
            'intakeNurse' => 'Intake Nurse',
            'admissionVisitDate' => 'Admission Visit Date',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'subject' => 'Subject',
            'location' => 'Location',
            'description' => 'Description',
            'addToNTH' => 'Add to NTH?',
            'recruiter' => 'Recruiter',
            'auth_nurse_id' => 'Auth Nurse',
            'serviceNeeded' => 'Service Needed',
            'skills' => 'Skills',
            'clinicianDiscipline' => 'Clinician Discipline',
            'scheduleDescription' => 'Schedule Description',
            'lob' => 'Line of Business'
        ];
        if (LD::checkFeatureFlag('hide-add-nth-part-from-edit-intake-form')) {
            unset($labelArr['serviceNeeded']);
            unset($labelArr['skills']);
            unset($labelArr['clinicianDiscipline']);
            unset($labelArr['addToNTH']);
            unset($labelArr['recruiter']);
        }
        $oldvalue = [];
        $newvalue = [];
        if (!empty($patientData)) {
            foreach ($icList as $list) {
                if ($newref) {
                    if ($intakeData->ic_id != $request['ic_id']) {
                        if ($list['value'] == $intakeData->ic_id) {
                            $oldvalue['ic_id'] = $list['text'];
                        }
                        if ($list['value'] == $request['ic_id']) {
                            $newvalue['ic_id'] = $list['text'];
                        }
                    }
                } else {
                    if ($patientData->ic_id != $request['ic_id']) {
                        if ($list['value'] == $patientData->ic_id) {
                            $oldvalue['ic_id'] = $list['text'];
                        }
                        if ($list['value'] == $request['ic_id']) {
                            $newvalue['ic_id'] = $list['text'];
                        }
                    }
                }
            }
            if (trim($patientData->PatientAccount) != trim($request['clientId'])) {
                $oldvalue['clientId'] = $patientData->PatientAccount;
                $newvalue['clientId'] = $request['clientId'];
            }
            if (trim($patientData->Patient_Address1) != trim($request['address1'])) {
                $oldvalue['address1'] = $patientData->Patient_Address1;
                $newvalue['address1'] = $request['address1'];
            }
            if (trim($patientData->Patient_Address2) != trim($request['address2'])) {
                $oldvalue['address2'] = $patientData->Patient_Address2;
                $newvalue['address2'] = $request['address2'];
            }
            if (trim($patientData->Patient_City) != trim($request['city'])) {
                $oldvalue['city'] = $patientData->Patient_City;
                $newvalue['city'] = $request['city'];
            }
            if (trim($patientData->Patient_State) != trim($request['state'])) {
                $oldvalue['state'] = $patientData->Patient_State;
                $newvalue['state'] = $request['state'];
            }
            if ($patientData->Patient_Zip != $request['zipCode']) {
                $oldvalue['zipCode'] = $patientData->Patient_Zip;
                $newvalue['zipCode'] = $request['zipCode'];
            }
            foreach ($facilities as $data) {
                if ($patientData->aoc_facility_id != $request['aoc_facility_id']) {
                    if ($data->id == $patientData->aoc_facility_id) {
                        $oldvalue['aoc_facility_id'] = $data->FacilityName;
                    }
                    if ($data->id == $request['aoc_facility_id']) {
                        $newvalue['aoc_facility_id'] = $data->FacilityName;
                    }
                }
            }
            if ($newref) {
                if ($intakeData->auth_nurse_id != $request['auth_nurse_id']) {
                    $oldvalue['auth_nurse_id'] = H::getUserNameById($intakeData->auth_nurse_id);
                    $newvalue['auth_nurse_id'] = H::getUserNameById($request['auth_nurse_id']);
                }
                if (!LD::checkFeatureFlag('hide-add-nth-part-from-edit-intake-form')) {
                    if ($intakeData->CF_ServicesNeeded != $request['serviceNeeded']) {
                        $oldvalue['serviceNeeded'] = H::getUserNameById($intakeData->CF_ServicesNeeded);
                        $newvalue['serviceNeeded'] = H::getUserNameById($request['serviceNeeded']);
                    }
                    if ($intakeData->CF_Skills != $skill) {
                        $oldvalue['skills'] = $intakeData->CF_Skills;
                        $newvalue['skills'] = $skill;
                    }
                    if ($intakeData->CF_ClinicianDiscipline != $request['clinicianDiscipline']) {
                        $oldvalue['clinicianDiscipline'] = $intakeData->CF_ClinicianDiscipline;
                        $newvalue['clinicianDiscipline'] = $request['clinicianDiscipline'];
                    }
                    if ($intakeData->NTHNotes != $request['scheduleDescription']) {
                        $oldvalue['scheduleDescription'] = $intakeData->NTHNotes;
                        $newvalue['scheduleDescription'] = $request['scheduleDescription'];
                    }
                }
                if ($intakeData->RespitePayer != $request['respiteTerminalPlayer']) {
                    $oldvalue['respiteTerminalPlayer'] = $intakeData->RespitePayer;
                    $newvalue['respiteTerminalPlayer'] = $request['respiteTerminalPlayer'];
                }
                if ($intakeData->Payer != $request['terminalPlayer']) {
                    $oldvalue['terminalPlayer'] = $intakeData->Payer;
                    $newvalue['terminalPlayer'] = $request['terminalPlayer'];
                }
                if ($intakeData->LOBID != $request['lob']) {
                    $oldvalue['lob'] = self::getLobNameFromId($intakeData->LOBID);
                    $newvalue['lob'] = self::getLobNameFromId($request['lob']);
                }
                if (isset($intakeData->CF_GoalSOCDate) && isset($request['goalSocDate'])) {
                    if ($intakeData->CF_GoalSOCDate != date('Y-m-d H:i:s', strtotime($request['goalSocDate']))) {
                        $oldvalue['goalSocDate'] = H::getFormatedDate($intakeData->CF_GoalSOCDate);
                        $newvalue['goalSocDate'] = H::getFormatedDate(
                            date('Y-m-d H:i:s', strtotime($request['goalSocDate']))
                        );
                    }
                }
            } else {
                if ($patientData->auth_nurse_id != $request['auth_nurse_id']) {
                    $oldvalue['auth_nurse_id'] = H::getUserNameById($patientData->auth_nurse_id);
                    $newvalue['auth_nurse_id'] = H::getUserNameById($request['auth_nurse_id']);
                }
                if (!LD::checkFeatureFlag('hide-add-nth-part-from-edit-intake-form')) {
                    if ($patientData->CF_ServicesNeeded != $request['serviceNeeded']) {
                        $oldvalue['serviceNeeded'] = H::getUserNameById($patientData->CF_ServicesNeeded);
                        $newvalue['serviceNeeded'] = H::getUserNameById($request['serviceNeeded']);
                    }
                    if ($patientData->CF_Skills != $skill) {
                        $oldvalue['skills'] = $patientData->CF_Skills;
                        $newvalue['skills'] = $skill;
                    }
                    if ($patientData->CF_ClinicianDiscipline != $request['clinicianDiscipline']) {
                        $oldvalue['clinicianDiscipline'] = $patientData->CF_ClinicianDiscipline;
                        $newvalue['clinicianDiscipline'] = $request['clinicianDiscipline'];
                    }
                    if ($patientData->NTHNotes != $request['scheduleDescription']) {
                        $oldvalue['scheduleDescription'] = $patientData->NTHNotes;
                        $newvalue['scheduleDescription'] = $request['scheduleDescription'];
                    }
                }
                if ($patientData->RespitePayer != $request['respiteTerminalPlayer']) {
                    $oldvalue['respiteTerminalPlayer'] = $patientData->RespitePayer;
                    $newvalue['respiteTerminalPlayer'] = $request['respiteTerminalPlayer'];
                }
                if ($patientData->Payer != $request['terminalPlayer']) {
                    $oldvalue['terminalPlayer'] = $patientData->Payer;
                    $newvalue['terminalPlayer'] = $request['terminalPlayer'];
                }
                if ($patientData->LOBID != $request['lob']) {
                    $oldvalue['lob'] = self::getLobNameFromId($patientData->LOBID);
                    $newvalue['lob'] = self::getLobNameFromId($request['lob']);
                }
                if (isset($patientData->CF_GoalSOCDate) && isset($request['goalSocDate'])) {
                    if ($patientData->CF_GoalSOCDate != date('Y-m-d H:i:s', strtotime($request['goalSocDate']))) {
                        $oldvalue['goalSocDate'] = H::getFormatedDate($patientData->CF_GoalSOCDate);
                        $newvalue['goalSocDate'] = H::getFormatedDate(
                            date('Y-m-d H:i:s', strtotime($request['goalSocDate']))
                        );
                    }
                }
            }
        }
        if (!empty($intakeData)) {
            if ($intakeData->kantime_url != $request['linkToKanTime']) {
                $oldvalue['linkToKanTime'] = $intakeData->kantime_url;
                $newvalue['linkToKanTime'] = $request['linkToKanTime'];
            }
            if (trim($intakeData->notes) != trim($request['notes'])) {
                $oldvalue['notes'] = $intakeData->notes;
                $newvalue['notes'] = $request['notes'];
            }
            if ($intakeData->referral_type != $request['referralType']) {
                $oldvalue['referralType'] = $intakeData->referral_type;
                $newvalue['referralType'] = $request['referralType'];
            }
            if ($intakeData->hospital != $request['hospital']) {
                $oldvalue['hospital'] = $intakeData->hospital;
                $newvalue['hospital'] = $request['hospital'];
            }
            if (isset($intakeData->hospital_discharge_date) && isset($request['hospitalDischargeDate'])) {
                if ($intakeData->hospital_discharge_date != date(
                        'Y-m-d H:i:s',
                        strtotime($request['hospitalDischargeDate'])
                    )) {
                    $oldvalue['hospitalDischargeDate'] = H::getFormatedDate($intakeData->hospital_discharge_date);
                    $newvalue['hospitalDischargeDate'] = H::getFormatedDate(
                        date('Y-m-d H:i:s', strtotime($request['hospitalDischargeDate']))
                    );
                }
            }
            if ($patientData->AuthorizedWeeklyHours != $request['anticipatedHours']) {
                $oldvalue['anticipatedHours'] = $patientData->AuthorizedWeeklyHours;
                $newvalue['anticipatedHours'] = $request['anticipatedHours'];
            }
            if ($intakeData->admission_visit_scheduled != $admissionVisitScheduled) {
                $oldvalue['admissionVisitScheduled'] = ($intakeData->admission_visit_scheduled == 1) ? 'Yes' : 'No';
                $newvalue['admissionVisitScheduled'] = ($admissionVisitScheduled == 1) ? 'Yes' : 'No';
            }
            if ($intakeData->intake_nurse_id != $request['intakeNurse']) {
                $oldvalue['intakeNurse'] = H::getUserNameById($intakeData->intake_nurse_id);
                $newvalue['intakeNurse'] = H::getUserNameById($request['intakeNurse']);
            }
            if (isset($intakeData->admission_visit_date) && isset($request['admissionVisitDate'])) {
                if ($intakeData->admission_visit_date != date(
                        'Y-m-d H:i:s',
                        strtotime($request['admissionVisitDate'])
                    )) {
                    $oldvalue['admissionVisitDate'] = H::getFormatedDate($intakeData->admission_visit_date);
                    $newvalue['admissionVisitDate'] = H::getFormatedDate(
                        date('Y-m-d H:i:s', strtotime($request['admissionVisitDate']))
                    );
                }
            }
            if (!empty($request['start_time'])
                && ($intakeData->start_time != date('H:i', strtotime($request['start_time'])))) {
                $oldvalue['start_time'] = date('g:ia', strtotime($intakeData->start_time));
                $newvalue['start_time'] = $request['start_time'];
            }
            if (!empty($request['end_time'])
                && ($intakeData->end_time != date('H:i', strtotime($request['end_time'])))) {
                $oldvalue['end_time'] = date('g:ia', strtotime($intakeData->end_time));
                $newvalue['end_time'] = $request['end_time'];
            }
            if (trim($intakeData->subject) != trim($request['subject'])) {
                $oldvalue['subject'] = $intakeData->subject;
                $newvalue['subject'] = $request['subject'];
            }
            if (trim($intakeData->location) != trim($request['location'])) {
                $oldvalue['location'] = $intakeData->location;
                $newvalue['location'] = $request['location'];
            }
            if (trim($intakeData->description) != trim($request['description'])) {
                $oldvalue['description'] = $intakeData->description;
                $newvalue['description'] = $request['description'];
            }
            if (!LD::checkFeatureFlag('hide-add-nth-part-from-edit-intake-form')) {
                if ($intakeData->add_to_nth != $addToNTH) {
                    $oldvalue['addToNTH'] = ($intakeData->add_to_nth == 1) ? 'Yes' : 'No';
                    $newvalue['addToNTH'] = ($addToNTH == 1) ? 'Yes' : 'No';
                }
            }
        }
        if (!LD::checkFeatureFlag('hide-add-nth-part-from-edit-intake-form')) {
            if (!empty($nthData)) {
                if ($nthData->user_id != $request['recruiter']) {
                    $oldvalue['recruiter'] = H::getUserNameById($nthData->user_id);
                    $newvalue['recruiter'] = H::getUserNameById($request['recruiter']);
                }
            }
        }
        $logData = [];
        $logData['entity'] = 'client';
        $logData['entity_id'] = $request['clientPId'];
        $logData['notes'] = '';
        if (!empty($oldvalue) && !empty($newvalue)) {
            $logData['notes'] .= 'Intake edited for ' . $clientName . '';
            foreach ($oldvalue as $oldkey => $data) {
                $logData['notes'] .= "<li>" . $labelArr[$oldkey] . ' updated from ' . $data . ' to ' . (is_array(
                        $newvalue[$oldkey]
                    ) ? implode(",", $newvalue[$oldkey]) : $newvalue[$oldkey]) . "</li>";
            }
            $logData['notes'] .= '<p>by ' . Auth::user()->name . '</p>';
            H::getHelper("ActivityNotes")->setActivityNotes($logData);
        }
    }

    public function getSkillsForNTH()
    {
        $skills = config('constant')['skillsForNTH'];
        $skillList = [];
        foreach ($skills as $skill) {
            $skillList[] = [
                "value" => $skill,
                'text' => $skill
            ];
        }
        return $skillList;
    }

    public function getPendingReason()
    {
        if (LD::checkFeatureFlag('update-pending-reason-prospective-clients-bucket')) {
            $pendingReasons = config('constant')['intakePendingReasonUpdated'];
            sort($pendingReasons);
        } else {
            $pendingReasons = config('constant')['intakePendingReason'];
        }
        $pendingReasonsList = [];
        foreach ($pendingReasons as $pendingReason) {
            $pendingReasonsList[] = [
                "value" => $pendingReason,
                'text' => $pendingReason
            ];
        }
        return $pendingReasonsList;
    }

    public function getReadyToStartReason()
    {
        $readyToStartReasons = config('constant')['intake_readytostart_reason'];
        if (LD::checkFeatureFlag('add-new-reason-in-pending-start-of-care')) {
            $readyToStartReasons[] = "Post Hospital Reassessment";
        }
        if (LD::checkFeatureFlag('add-new-option-in-pending-reasons-to-intake-dashboard')) {
            $readyToStartReasons[] = "Comp Validation/Test";
            $readyToStartReasons[] = "Clinical Training";
            $readyToStartReasons[] = "Self Directed Training";
        }
        $readyToStartReasons = Arr::sort($readyToStartReasons);
        $readyToStartReasonsList = [];
        foreach ($readyToStartReasons as $readyToStartReason) {
            $readyToStartReasonsList[] = [
                "value" => $readyToStartReason,
                'text' => $readyToStartReason
            ];
        }
        return $readyToStartReasonsList;
    }

    public function getBdRep()
    {
        // normalizes "Last, First" -> "First Last" and lowercases, so BD Rep name variants
        // (e.g. "LeeAnna Mendoza" / "Mendoza, Leeanna") collapse into a single filter option
        $normalizedBdRep = "LOWER(TRIM(IF(LOCATE(',', BD_Rep) > 0, CONCAT(TRIM(SUBSTRING_INDEX(BD_Rep, ',', -1)), ' ', TRIM(SUBSTRING_INDEX(BD_Rep, ',', 1))), BD_Rep)))";

        $bdReps = DB::table("mssql_patients")
            ->selectRaw("$normalizedBdRep as normalized_bd_rep, MIN(BD_Rep) as BD_Rep")
            ->whereNotNull('BD_Rep')
            ->where('BD_Rep', '!=', '')
            ->groupBy(DB::raw($normalizedBdRep))
            ->orderBy("BD_Rep")
            ->get();

        $bdRepList = [];
        foreach ($bdReps as $key => $value) {
            $bdRepList[] = [
                "value" => $value->normalized_bd_rep,
                "text" => $value->BD_Rep
            ];
        }
        return $bdRepList;
    }

    /**
     * PD-4446: like getBdRep(), but normalizes "Last, First" to "First Last" and dedupes
     * case-insensitively, for use ONLY where the caller also compares against the
     * normalized form (National Intake Report). Other BD_Rep filters (e.g. the Intake
     * Coordinator Staff Transfers grid, IntakeCoordinatorStaffTransfers.php) still
     * compare selections against the raw BD_Rep column, so they must keep using getBdRep().
     */
    public function getNormalizedBdRep()
    {
        $bdReps = DB::table("mssql_patients")
            ->select(DB::raw("
                CASE
                    WHEN TRIM(BD_Rep) LIKE '%,%' THEN CONCAT(
                        TRIM(SUBSTRING_INDEX(TRIM(BD_Rep), ',', -1)),
                        ' ',
                        TRIM(SUBSTRING_INDEX(TRIM(BD_Rep), ',', 1))
                    )
                    ELSE TRIM(BD_Rep)
                END as BD_Rep
            "))
            ->distinct()
            ->whereNotNull('BD_Rep')
            ->where('BD_Rep', '!=', '')
            ->orderBy("BD_Rep")
            ->get();

        $seen = [];
        $bdRepList = [];
        foreach ($bdReps as $value) {
            $dedupeKey = mb_strtoupper($value->BD_Rep);
            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;
            $bdRepList[] = [
                "value" => $value->BD_Rep,
                "text" => $value->BD_Rep
            ];
        }
        return $bdRepList;
    }

    public function getNonAdmitReason()
    {
        $nonAdmitReasons = config('constant.intake_nonadmit_reason');
        $nonAdmitReasonsList = [];
        foreach ($nonAdmitReasons as $nonAdmitReason) {
            $nonAdmitReasonsList[] = [
                "value" => $nonAdmitReason,
                'text' => $nonAdmitReason
            ];
        }
        return $nonAdmitReasonsList;
    }

    //exclude Colorado
    public function getLob()
    {
        return DB::table('lob')->where('LOBName', '<>', 'Colorado')
            ->orderBy('LOBName', 'asc')
            ->get();
    }

    public function getLOBDropdown()
    {
        $data = $this->getLob();

        $lobs = [];
        foreach ($data as $lob) {
            $lobs[] = [
                "value" => $lob->LOBID,
                'text' => $lob->LOBName
            ];
        }
        return $lobs;
    }

    public static function getLobNameFromId($lobId)
    {
        return DB::table('lob')->where('LOBID', '=', $lobId)->value('LOBName');
    }

    public static function getCareTeam($aoc_patient_id)
    {
        $careTeam = [];
        if (!empty($aoc_patient_id)) {
            $clientDetails = MssqlPatients::find($aoc_patient_id);
            $careTeamIds = [
                'rdod_id' => 'Regional Director of Operations',
                'dbo_id' => 'Director of Branch Operations',
                'ccc_id' => 'Client Care Coordinator',
                'ic_id' => 'Intake Coordinator',
                'r_id' => 'Recruiter',
                'ccm_id' => 'Clinical Care Manager',
                'bd_id' => 'Business Development Rep'
            ];
            $i = 0;
            foreach ($careTeamIds as $k => $v) {
                if ($clientDetails->{$k} != '' && $clientDetails->{$k} != 1) {
                    $user = User::find($clientDetails->{$k});
                    if (!empty($user)) {
                        $careTeam[$i]['designation'] = $v;
                        $careTeam[$i]['profilePic'] = $user->profile_picture;
                        $careTeam[$i]['name'] = $user->name;
                        $careTeam[$i]['id'] = $user->id;
                        $i++;
                    }
                }
            }

            $rdo_ids = H::getHelper("UserPermissionResolver")
                ->getUserIdByFacilityAndRole($clientDetails->aoc_facility_id, 'Regional Director of Operations');
            if (count($rdo_ids) > 0) {
                foreach ($rdo_ids as $rdo_id) {
                    $user = User::find($rdo_id);
                    if (!empty($user)) {
                        $careTeam[$i]['designation'] = 'Regional Director of Operations';
                        $careTeam[$i]['profilePic'] = $user->profile_picture;
                        $careTeam[$i]['name'] = $user->name;
                        $careTeam[$i]['id'] = $user->id;
                        $i++;
                    }
                }
            }

            $dbo_ids = H::getHelper("UserPermissionResolver")
                ->getUserIdByFacilityAndRole($clientDetails->aoc_facility_id, 'Director of Branch Operations');
            if (count($dbo_ids) > 0) {
                foreach ($dbo_ids as $rdo_id) {
                    $user = User::find($rdo_id);
                    if (!empty($user)) {
                        $careTeam[$i]['designation'] = 'Director of Branch Operations';
                        $careTeam[$i]['profilePic'] = $user->profile_picture;
                        $careTeam[$i]['name'] = $user->name;
                        $careTeam[$i]['id'] = $user->id;
                        $i++;
                    }
                }
            }

            $rdn_ids = H::getHelper("UserPermissionResolver")
                ->getUserIdByFacilityAndRole($clientDetails->aoc_facility_id, 'Director of Nursing');
            if (count($rdn_ids) > 0) {
                foreach ($rdn_ids as $rdn_id) {
                    $user = User::find($rdn_id);
                    if (!empty($user)) {
                        $careTeam[$i]['designation'] = 'Director of Nursing';
                        $careTeam[$i]['profilePic'] = $user->profile_picture;
                        $careTeam[$i]['name'] = $user->name;
                        $careTeam[$i]['id'] = $user->id;
                        $i++;
                    }
                }
            }

            $intakeData = Intake::select(
                'mssql_patients.*',
                'intake.*',
                'intake.created_at as intake_created_date',
                'lob.LOBName'
            )
                ->leftJoin('mssql_patients', 'mssql_patients.id', 'intake.aoc_patient_id')
                ->leftJoin('lob', 'lob.LOBID', '=', 'mssql_patients.LOBID')
                ->where('intake.aoc_patient_id', $aoc_patient_id)
                ->orderby('intake.created_at', 'desc')
                ->first();
            if (!empty($intakeData) && !empty($intakeData['LOBID']) && !empty($intakeData['ic_id'])) {
                $clinicain = H::getUserById($intakeData['ic_id']);
                $careTeam[$i]['designation'] = 'Start of Care Clinician';
                $careTeam[$i]['profilePic'] = $clinicain->profile_picture;
                $careTeam[$i]['name'] = $clinicain->name;
                $careTeam[$i]['id'] = $clinicain->id;
                $i++;
            } else {
                if (!empty($clientDetails->SOCClinicianID)) {
                    $user = DB::table('mssql_providers')
                        ->select('users.profile_picture', 'users.name', 'users.id')
                        ->leftJoin('users', 'users.email', '=', 'mssql_providers.AOC_Email')
                        ->where('mssql_providers.ProviderID', $clientDetails->SOCClinicianID)
                        ->first();
                    if (!empty($user)) {
                        $careTeam[$i]['designation'] = 'Start of Care Clinician';
                        $careTeam[$i]['profilePic'] = $user->profile_picture;
                        $careTeam[$i]['name'] = $user->name;
                        $careTeam[$i]['id'] = $user->id;
                        $i++;
                    }
                }
            }
        }

        if (LD::checkFeatureFlag('add-recruiter-data-client-care')) {
            $needToHireChecklistDetails = DB::table('NeedToHire_Accounts')
                ->select(
                    "NeedToHire_Checklist.*",
                    'NeedToHire_Accounts.*',
                    DB::raw("DATEDIFF(now(),NeedToHire_Accounts.created_at) as daysInNTH"),
                    DB::raw("NeedToHire_Accounts.id as needToHireId"),
                    DB::raw("NeedToHire_Checklist.id as needToHireChecklistId")
                )
                ->leftJoin(
                    'NeedToHire_Checklist',
                    'NeedToHire_Checklist.aoc_nthaccounts_id',
                    '=',
                    'NeedToHire_Accounts.id'
                )
                ->where("NeedToHire_Accounts.aoc_patient_id", $aoc_patient_id)
                ->orderBy('NeedToHire_Accounts.IsActive', 'desc')
                ->orderBy('NeedToHire_Accounts.id', 'desc')
                ->first();
            if (!empty($needToHireChecklistDetails->user_id)) {
                $user = User::find($needToHireChecklistDetails->user_id);
                if (!empty($user)) {
                    $careTeam[$i]['designation'] = 'Recruiter';
                    $careTeam[$i]['profilePic'] = $user->profile_picture;
                    $careTeam[$i]['name'] = $user->name;
                    $careTeam[$i]['id'] = $user->id;
                    $i++;
                }
            }
        }

        if (LD::checkFeatureFlag('add-recruiting-team-lead-and-recruiting-admin-in-care-team')) {
            $recruitingDetail = NeedToHireAccounts::select(
                'users.recruiting_teamleader',
                'users.recruiting_admin',
                'rtl.profile_picture as rtl_pc',
                'rtl.name as rtl_name',
                'rtl.id as rtl_id',
                'ra.profile_picture as ra_pc',
                'ra.name as ra_name',
                'ra.id as ra_id',
            )
                ->leftJoin('users', 'users.id', '=', 'NeedToHire_Accounts.user_id')
                ->leftJoin('users as rtl', 'rtl.id', '=', 'users.recruiting_teamleader')
                ->leftJoin('users as ra', 'ra.id', '=', 'users.recruiting_admin')
                ->where('NeedToHire_Accounts.aoc_patient_id', '=', $aoc_patient_id)
                ->where('NeedToHire_Accounts.isActive', 1)
                ->get();
            if (!empty($recruitingDetail)) {
                if (isset($recruitingDetail[0]['rtl_name'])) {
                    $careTeam[$i]['designation'] = 'Recruiting Team Leader';
                    $careTeam[$i]['profilePic'] = $recruitingDetail[0]['rtl_pc'];
                    $careTeam[$i]['name'] = $recruitingDetail[0]['rtl_name'];
                    $careTeam[$i]['id'] = $recruitingDetail[0]['rtl_id'];
                    $i++;
                }
                if (isset($recruitingDetail[0]['ra_name'])) {
                    $careTeam[$i]['designation'] = 'Recruiting Admin';
                    $careTeam[$i]['profilePic'] = $recruitingDetail[0]['ra_pc'];
                    $careTeam[$i]['name'] = $recruitingDetail[0]['ra_name'];
                    $careTeam[$i]['id'] = $recruitingDetail[0]['ra_id'];
                    $i++;
                }
            }
        }

        if (LD::checkFeatureFlag('add-intake-tab-in-client-profile')) {
            $intakeData = Intake::select(
                'mssql_patients.*',
                'intake.*',
                'intake.created_at as intake_created_date',
                'lob.LOBName'
            )
                ->leftJoin('mssql_patients', 'mssql_patients.id', 'intake.aoc_patient_id')
                ->leftJoin('lob', 'lob.LOBID', '=', 'mssql_patients.LOBID')
                ->where('intake.aoc_patient_id', $aoc_patient_id)
                ->orderby('intake.created_at', 'desc')
                ->first();
            if (!empty($intakeData)) {
                if (!empty($intakeData['auth_nurse_id'])) {
                    $authNurse = H::getUserById($intakeData['auth_nurse_id']);
                    $careTeam[$i]['designation'] = 'Primary Clinician';
                    $careTeam[$i]['profilePic'] = $authNurse->profile_picture;
                    $careTeam[$i]['name'] = $authNurse->name;
                    $careTeam[$i]['id'] = $authNurse->id;
                    $i++;
                }
            }
        }

        return $careTeam;
    }

    public function getPayersWithTherapy()
    {
        $payers = MssqlPatients::distinct()->select('Payer')
            ->where('Payer', 'not like', '%EVV%')
            ->where('Payer', '!=', '')
            ->orderBy('Payer')
            ->get()->toArray();
        $payerList = [];
        foreach ($payers as $payer) {
            $payerList[] = ["value" => $payer['Payer'], 'text' => $payer['Payer']];
        }
        return $payerList;
    }

    public static function getCareTeamForIntakeSms($aoc_patient_id)
    {
        $careTeam = [];
        if (!empty($aoc_patient_id)) {
            $clientDetails = MssqlPatients::find($aoc_patient_id);
            $careTeamIds = config('constant.intake_care_team_dashboard');
            $i = 0;
            foreach ($careTeamIds as $k => $v) {
                if ($k == 'SOCClinicianID') {
                    if (LD::checkFeatureFlag('move-first-billable-shift-fields-to-intake-table')) {
                        $intakeData = DB::table('mssql_schedulemaster')
                            ->select('mssql_providers.Mobile', 'mssql_providers.fullName')
                            ->leftjoin('intake', 'intake.intake_admission_visit', '=', 'mssql_schedulemaster.CGTaskID')
                            ->leftJoin(
                                'mssql_providers',
                                'mssql_providers.ProviderID',
                                'mssql_schedulemaster.ProviderID'
                            )
                            ->where('intake.aoc_patient_id', $aoc_patient_id)
                            ->orderby('mssql_schedulemaster.ScheduleDate', 'desc')
                            ->first();
                    } else {
                        $intakeData = DB::table('mssql_schedulemaster')
                            ->select('mssql_providers.Mobile', 'mssql_providers.fullName')
                            ->leftjoin(
                                'mssql_patients',
                                'mssql_patients.intake_admission_visit',
                                '=',
                                'mssql_schedulemaster.CGTaskID'
                            )
                            ->leftJoin(
                                'mssql_providers',
                                'mssql_providers.ProviderID',
                                'mssql_schedulemaster.ProviderID'
                            )
                            ->where('mssql_patients.id', $aoc_patient_id)
                            ->orderby('mssql_schedulemaster.ScheduleDate', 'desc')
                            ->first();
                    }
                    if (!empty($intakeData)) {
                        $careTeam[$i]['designation'] = $v;
                        $careTeam[$i]['phone_number'] = $intakeData->Mobile;
                        $careTeam[$i]['name'] = $intakeData->fullName;
                        $careTeam[$i]['key'] = $k;
                        $i++;
                    } else {
                        if (!empty($clientDetails->{$k})) {
                            $providers = DB::table("mssql_providers")->where(
                                'mssql_providers.ProviderID',
                                $clientDetails->{$k}
                            )->first();
                            if (!empty($providers)) {
                                $careTeam[$i]['designation'] = $v;
                                $careTeam[$i]['phone_number'] = $providers->Mobile;
                                $careTeam[$i]['name'] = $providers->fullName;
                                $careTeam[$i]['key'] = $k;
                                $i++;
                            }
                        }
                    }
                } elseif ($k == 'dbo_id') {
                    $dbo_ids = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole(
                        $clientDetails->aoc_facility_id,
                        'Director of Branch Operations'
                    );
                    H::ExcelLog(
                        'Director of Branch Operations ' . print_r($dbo_ids, true),
                        'getCareTeamForIntakeSms.log'
                    );
                    if (count($dbo_ids) > 0) {
                        foreach ($dbo_ids as $rdo_id) {
                            $user = User::find($rdo_id);
                            if (!empty($user)) {
                                $careTeam[$i]['designation'] = 'Director of Branch Operations';
                                $careTeam[$i]['phone_number'] = $user->phone_number;
                                $careTeam[$i]['name'] = $user->name;
                                $careTeam[$i]['key'] = $k;
                                $i++;
                            }
                        }
                    }
                } else {
                    if ($clientDetails->{$k} != '' && $clientDetails->{$k} != 1) {
                        $user = User::find($clientDetails->{$k});
                        if (!empty($user)) {
                            $careTeam[$i]['designation'] = $user->title;
                            $careTeam[$i]['phone_number'] = $user->phone_number;
                            $careTeam[$i]['name'] = $user->name;
                            $careTeam[$i]['key'] = $k;
                            $i++;
                        }
                    }
                }
            }
        }
        H::ExcelLog('Care Team ' . print_r($careTeam, true), 'getCareTeamForIntakeSms.log');
        return $careTeam;
    }

    public static function getCareTeamByRoleForIntakeSms($data)
    {
        $careTeamRoleArr = config('constant.intake_care_team_dashboard');
        $careTeam = [];
        $aoc_patient_id = $data['aoc_patient_id'];
        $careTeamRole = $data['careTeamRole'];
        $careTeamIds = array_keys($careTeamRoleArr);
        if (!empty($aoc_patient_id)) {
            if (in_array($careTeamRole, $careTeamIds)) {
                $clientDetails = MssqlPatients::find($aoc_patient_id);
                if ($careTeamRole == 'dbo_id') {
                    $dbo_ids = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole(
                        $clientDetails->aoc_facility_id,
                        'Director of Branch Operations'
                    );
                    H::ExcelLog(
                        'Director of Branch Operations ' . print_r($dbo_ids, true),
                        'getCareTeamByRoleForIntakeSms.log'
                    );
                    if (count($dbo_ids) > 0) {
                        foreach ($dbo_ids as $rdo_id) {
                            $user = User::find($rdo_id);
                            if (!empty($user)) {
                                $careTeam['designation'] = 'Director of Branch Operations';
                                $careTeam['phone_number'] = $user->phone_number;
                                $careTeam['name'] = $user->name;
                            }
                        }
                    }
                } else {
                    if ($clientDetails->{$careTeamRole} != '' && $clientDetails->{$careTeamRole} != 1) {
                        if ($careTeamRole == 'SOCClinicianID') {
                            if (LD::checkFeatureFlag('move-first-billable-shift-fields-to-intake-table')) {
                                $intakeData = DB::table('mssql_schedulemaster')
                                    ->select('mssql_providers.Mobile', 'mssql_providers.fullName')
                                    ->leftjoin(
                                        'intake',
                                        'intake.intake_admission_visit',
                                        '=',
                                        'mssql_schedulemaster.CGTaskID'
                                    )
                                    ->leftJoin(
                                        'mssql_providers',
                                        'mssql_providers.ProviderID',
                                        'mssql_schedulemaster.ProviderID'
                                    )
                                    ->where('intake.aoc_patient_id', $aoc_patient_id)
                                    ->orderby('mssql_schedulemaster.ScheduleDate', 'desc')
                                    ->first();
                            } else {
                                $intakeData = DB::table('mssql_schedulemaster')
                                    ->select('mssql_providers.Mobile', 'mssql_providers.fullName')
                                    ->leftjoin(
                                        'mssql_patients',
                                        'mssql_patients.intake_admission_visit',
                                        '=',
                                        'mssql_schedulemaster.CGTaskID'
                                    )
                                    ->leftJoin(
                                        'mssql_providers',
                                        'mssql_providers.ProviderID',
                                        'mssql_schedulemaster.ProviderID'
                                    )
                                    ->where('mssql_patients.id', $aoc_patient_id)
                                    ->orderby('mssql_schedulemaster.ScheduleDate', 'desc')
                                    ->first();
                            }
                            if (!empty($intakeData)) {
                                $careTeam['designation'] = $careTeamRoleArr[$careTeamRole];
                                $careTeam['phone_number'] = $intakeData->Mobile;
                                $careTeam['name'] = $intakeData->fullName;
                            } else {
                                $providers = DB::table("mssql_providers")->where(
                                    'mssql_providers.ProviderID',
                                    $clientDetails->{$careTeamRole}
                                )->first();
                                if (!empty($providers)) {
                                    $careTeam['designation'] = $careTeamRoleArr[$careTeamRole];
                                    $careTeam['phone_number'] = $providers->Mobile;
                                    $careTeam['name'] = $providers->fullName;
                                }
                            }
                        } else {
                            $user = User::find($clientDetails->{$careTeamRole});
                            if (!empty($user)) {
                                $careTeam['designation'] = $user->title;
                                $careTeam['phone_number'] = $user->phone_number;
                                $careTeam['name'] = $user->name;
                                $careTeam['id'] = $user->id;
                            }
                        }
                    }
                }
            }
        }
        H::ExcelLog('Care Team ' . print_r($careTeam, true), 'getCareTeamByRoleForIntakeSms.log');
        return $careTeam;
    }

    public function getTinyUrl($data)
    {
        $responseArray = [];
        try {
            $apiToken = env('TINY_URL_TOKEN');
            $url = 'https://api.tinyurl.com/create?api_token=' . urlencode($apiToken);
            $customDomain = "tiny.angelsofcare.com";
            $data = [
                'url' => $data['url'],
                "domain" => $customDomain
            ];

            $jsonData = json_encode($data);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                H::ExcelLog('Error:' . curl_error($ch), 'getTinyUrl.log');
            } else {
                $responseArray = json_decode($response, true);
                H::ExcelLog("Response Array", "getTinyUrl.log");
                H::ExcelLog(print_r($responseArray, true), 'getTinyUrl.log');
            }
            curl_close($ch);
        } catch (\Throwable $th) {
            H::ExcelLog('getTinyUrl Throwable=' . $th->getMessage(), 'getTinyUrl.log');
        }
        return $responseArray;
    }

    public function getReferralStatus()
    {
        $referralStatus = [
            'Prospective Clients',
            'Intake Scheduled',
            'Pending Start of Care',
            'Client Starts',
            'Admitted',
            'Pending Non Admit',
            'Non Admit'
        ];
        $statusList = [];
        foreach ($referralStatus as $status) {
            $statusList[] = [
                "value" => $status,
                'text' => $status
            ];
        }
        return $statusList;
    }

    public function getCoverageCoordinatorByPolygon($address, $provider_id)
    {
        H::ExcelLog("Start Auto Assign Coordinator for provider : " . $provider_id, 'assignCoverageCoordinagor.log');
        H::ExcelLog(print_r($address, true), 'assignCoverageCoordinagor.log');
        $latLong = Geocoding::getGeoGoogle(implode(' ', $address));
        $mapPolygons = [];
        $mapPolygons = DB::table("polygons")->select("polygons.*")
            ->leftJoin("map_configurations", "map_configurations.map_id", "=", "polygons.map_id")
            ->where("map_configurations.map_tablename", "maps_covclin")
            ->where(function ($q) {
                $q->where("user_type", "client_care_coordinator")
                    ->orWhereNull("user_type")
                    ->orWhere("user_type", '');
            })->get();

        $points = $latLong['lat'] . " " . $latLong['lng'];
        H::ExcelLog("points : " . $points, 'assignCoverageCoordinagor.log');
        foreach ($mapPolygons as $polygons) {
            $polygonData = json_decode($polygons->coordinates, true);
            $polygon = [];
            $counter = 0;
            $firstX = "";
            $firstY = "";
            foreach ($polygonData as $xy) {
                $polygon[] = $xy['lat'] . ' ' . $xy['lng'];
                if ($counter == 0) {
                    $firstX = $xy['lat'];
                    $firstY = $xy['lng'];
                }
                $counter++;
            }

            $polygon[] = $firstX . ' ' . $firstY;

            if ($this->pointInPolygon($points, $polygon)) {
                H::ExcelLog("Polygon found", 'assignCoverageCoordinagor.log');
                $polygonName = explode("-", $polygons->name);
                $coordinators = $this->getCoverageCoordinatorList();
                foreach ($coordinators as $coordinator) {
                    if ($coordinator['text'] == trim($polygonName[0])) {
                        H::ExcelLog('matched!', 'assignCoverageCoordinagor.log');
                        H::ExcelLog('Value : ' . $coordinator['value'], 'assignCoverageCoordinagor.log');
                        H::ExcelLog(
                            "End Auto Assign Coordinator for provider : " . $provider_id,
                            'assignCoverageCoordinagor.log'
                        );
                        return $coordinator['value'];
                    }
                }
                H::ExcelLog(trim($polygonName[0]) . ' NOT MATCHED!', 'assignCoverageCoordinagor.log');
            }
        }
        H::ExcelLog("End Auto Assign Coordinator for provider : " . $provider_id, 'assignCoverageCoordinagor.log');
        return 0;
    }

    public function getCoverageCoordinatorList()
    {
        $ccArray = $userList = [];
        $ccUser = User::select("users.id", "users.name")
            ->leftJoin('users_has_roles', 'users.id', '=', 'users_has_roles.user_id')
            ->leftJoin('roles', 'users_has_roles.role_id', '=', 'roles.id');
        $ccUser->where(function ($query) {
            $query->where("roles.name", '=', 'Coverage Coordinator');
        });
        $ccUser->where('status', 1)->orderBy('users.name');
        H::ExcelLog('getUsersByRoles: ' . $ccUser->toSql(), 'getUsersByRoles.log');
        $ccUser = $ccUser->get();
        foreach ($ccUser as $user) {
            $ccArray[] = ["value" => $user->id, 'text' => $user->name];
        }
        $temp = array_unique(array_column($ccArray, 'text'));
        $userList = array_intersect_key($ccArray, $temp);
        return $userList;
    }

    public function getPRNClients()
    {
        $patients = MssqlPatients::select(
            'mssql_patients.id as aoc_patient_id',
            'mssql_facilities.FacilityName as branch',
            DB::raw('concat(Patient_LastName,", ",Patient_FirstName, " (", PatientAccount ,")") as client_name')
        )
            ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_patients.FacilityID')
            ->where('mssql_patients.isPRN', 1)
            ->where('mssql_patients.Patient_CurrentStatusID', 1)
            ->get();
        if (LD::checkFeatureFlag('client-new-changes')) {
            $patients->where('mssql_patients.IsLatestAdmit', 1);
        }
        return $patients;
    }

    public function getIntakeNurseFromSchedule($patientId)
    {
        $data = DB::table('mssql_schedulemaster')
            ->select(DB::raw('concat(FirstName," ",LastName) as name'))
            ->leftJoin('mssql_providers', 'mssql_providers.ProviderID', '=', 'mssql_schedulemaster.ProviderID')
            ->where('PatientID', $patientId)
            ->where('ServiceName', 'RN ADMISSION')
            ->first();
        if (!empty($data)) {
            return $data->name;
        }
        return '';
    }

    public function formatTextFromHtml($html)
    {
        $text = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $text = preg_replace('/<p.*?>/i', "\n", $text);
        $text = str_replace('</p>', '', $text);

        $text = preg_replace('/<ul.*?>/i', "\n", $text);
        $text = preg_replace('/<li.*?>/i', "- ", $text);
        $text = str_replace('</li>', "\n", $text);
        $text = str_replace('</ul>', '', $text);

        $text = strip_tags($text);

        return trim($text);
    }

    public function checkNewRefOnLob($id)
    {
        $intakeId = Intake::select('LOBID', 'aoc_patient_id')->where('id', $id)->first();
        $patientID = MssqlPatients::select('LOBID')->where('id', $intakeId->aoc_patient_id)->first();
        if ($intakeId->LOBID == $patientID->LOBID) {
            return false;
        }
        return true;
    }

    public function getCustomFields($entity, $category = '')
    {
        $customFieldsList = DB::table('custom_fields')->select("id", "field_name", 'category', "db_field_name")->where(
            'is_deleted',
            0
        )
            ->where('entity', $entity)
            ->orderBy('category')
            ->orderBy('sort');
        if ($category) {
            $customFieldsList->where('category', $category);
        }
        $fields = $customFieldsList->get();
        return $fields;
    }

    public function getLeadResolutions()
    {
        return [
            ['value' => 'Advanced via another Lead','text' => 'Advanced via another Lead'],
            ['value' => 'Scheduled Next Steps (advanced within same Lead)','text' => 'Scheduled Next Steps (advanced within same Lead)'],
            ['value' => 'Clinician Declined - Pay','text' => 'Clinician Declined - Pay'],
            ['value' => 'Clinician Declined - Hours','text' => 'Clinician Declined - Hours'],
            ['value' => 'Clinician Declined - Location/Distance','text' => 'Clinician Declined - Location/Distance'],
            ['value' => 'Clinician Declined - Schedule','text' => 'Clinician Declined - Schedule'],
            ['value' => 'Clinician Declined - Client Preference','text' => 'Clinician Declined - Client Preference'],
            ['value' => 'Clinician Declined - Other','text' => 'Clinician Declined - Other'],
            ['value' => 'No Response (after Attempt 3)','text' => 'No Response (after Attempt 3)'],
            ['value' => 'Not a Fit - Skills','text' => 'Not a Fit - Skills'],
            ['value' => 'Not a Fit - License','text' => 'Not a Fit - License'],
            ['value' => 'Not a Fit - Experience','text' => 'Not a Fit - Experience'],
            ['value' => 'Not a Fit - Availability','text' => 'Not a Fit - Availability'],
            ['value' => 'Duplicate Lead','text' => 'Duplicate Lead'],
            ['value' => 'Case Filled','text' => 'Case Filled'],
            ['value' => 'Assigned Elsewhere (Internal Transfer)','text' => 'Assigned Elsewhere (Internal Transfer)'],
            ['value' => 'Assigned Elsewhere (External/Another Employer)','text' => 'Assigned Elsewhere (External/Another Employer)'],
            ['value' => 'Compliance Hold','text' => 'Compliance Hold'],
            ['value' => 'Other','text' => 'Other'],
        ];
    }
}
