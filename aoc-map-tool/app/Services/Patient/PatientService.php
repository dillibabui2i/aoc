<?php

namespace App\Services\Patient;

use App\Helpers\H;
use App\Helpers\LD;
use App\MssqlPatients;
use App\NeedToHireAccounts;
use App\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PatientService
{
    private const AUTHORIZATION_CLINICIAN_ROLE = 'authorization_clinician';
    private const DIRECTOR_OF_BRANCH_OPERATIONS_ROLE = 'director_of_branch_operations';
    private const CLIENT_CARE_COORDINATOR_ROLE = 'client_care_coordinator';
    private const DIRECTOR_OF_NURSING_ROLE = 'director_of_nursing';
    private const START_OF_CARE_CLINICIAN_ROLE = 'start_of_care_clinician';
    private const INTAKE_COORDINATOR_ROLE = 'intake_coordinator';
    private const RECRUITER_ROLE = 'recruiter';
    private const RECRUITER_TEAM_LEAD_ROLE = 'recruiting_team_leader';
    private const RECRUITER_ADMIN_ROLE = 'recruiting_admin';
    private const CLINICAL_CARE_MANAGER_ROLE = 'clinical_care_manager';
    private const BUSINESS_DEVELOPMENT_REP_ROLE = 'business_development_rep';


    public function getPatientCareTeam(int $profileID): Collection
    {
        $clientDetails = MssqlPatients::select(
            "mssql_patients.*",
            "mssql_facilities.FacilityName",
            DB::raw(
                "CONCAT(FLOOR((TIMESTAMPDIFF(MONTH, mssql_patients.Patient_DOB, CURDATE()) / 12)), ' Years ') AS Patient_age"
            )
        )
            ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
            ->where("mssql_patients.id", $profileID)->first();

        $recruiter_data = [];
        $needToHireChecklistDetails = DB::table('NeedToHire_Accounts')->select(
            "NeedToHire_Checklist.*",
            'NeedToHire_Accounts.*',
            DB::raw("DATEDIFF(now(),NeedToHire_Accounts.created_at) as daysInNTH"),
            DB::raw("NeedToHire_Accounts.id as needToHireId"),
            DB::raw("NeedToHire_Checklist.id as needToHireChecklistId")
        )
            ->leftJoin('NeedToHire_Checklist', 'NeedToHire_Checklist.aoc_nthaccounts_id', '=', 'NeedToHire_Accounts.id')
            ->where("NeedToHire_Accounts.aoc_patient_id", $profileID)->orderBy(
                'NeedToHire_Accounts.IsActive',
                'desc'
            )->orderBy('NeedToHire_Accounts.id', 'desc')->first();
        if (!empty($needToHireChecklistDetails)) {
            if (LD::checkFeatureFlag('add-recruiter-data-client-care')) {
                if (!empty($needToHireChecklistDetails->user_id)) {
                    $user = User::find($needToHireChecklistDetails->user_id);
                    if (!empty($user)) {
                        $recruiter_data['role'] = 'recruiter';
                        $recruiter_data['id'] = $user->id;
                    }
                    if (LD::checkFeatureFlag('add-recruiting-team-lead-and-recruiting-admin-in-care-team')) {
                        $recruitingDetail = NeedToHireAccounts::select(
                            'users.recruiting_teamleader',
                            'users.recruiting_admin',
                            'rtl.profile_picture as rtl_pc',
                            'rtl.id as rtl_id',
                            'ra.profile_picture as ra_pc',
                            'ra.id as ra_id'
                        )
                            ->leftJoin('users', 'users.id', '=', 'NeedToHire_Accounts.user_id')
                            ->leftJoin('users as rtl', 'rtl.id', '=', 'users.recruiting_teamleader')
                            ->leftJoin('users as ra', 'ra.id', '=', 'users.recruiting_admin')
                            ->where('NeedToHire_Accounts.aoc_patient_id', '=', $profileID)
                            ->where('NeedToHire_Accounts.isActive', 1)
                            ->get();
                        if (!empty($recruitingDetail)) {
                            if (isset($recruitingDetail[0]['rtl_id'])) {
                                $recruitingDetails[] =
                                    [
                                        'role' => self::RECRUITER_TEAM_LEAD_ROLE,
                                        'id' => $recruitingDetail[0]['rtl_id'],
                                    ];
                            }
                            if (isset($recruitingDetail[0]['ra_id'])) {
                                $recruitingDetails[] =
                                    [
                                        'role' => self::RECRUITER_ADMIN_ROLE,
                                        'id' => $recruitingDetail[0]['ra_id'],
                                    ];
                            }
                        }
                    }
                }
            }
        }

        $careTeamIds = [
            'auth_nurse_id' => self::AUTHORIZATION_CLINICIAN_ROLE,
            'dbo_id' => self::DIRECTOR_OF_BRANCH_OPERATIONS_ROLE,
            'ccc_id' => self::CLIENT_CARE_COORDINATOR_ROLE,
            'ic_id' => self::INTAKE_COORDINATOR_ROLE,
            'r_id' => self::RECRUITER_ROLE,
            'ccm_id' => self::CLINICAL_CARE_MANAGER_ROLE,
            'bd_id' => self::BUSINESS_DEVELOPMENT_REP_ROLE
        ];
        $careTeam = [];
        $i = 0;
        foreach ($careTeamIds as $k => $v) {
            if ($clientDetails->{$k} != '' && $clientDetails->{$k} != 1) {
                $user = User::find($clientDetails->{$k});
                if (!empty($user)) {
                    $careTeam[$i]['role'] = $v;
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
                    $careTeam[$i]['role'] = self::DIRECTOR_OF_BRANCH_OPERATIONS_ROLE;
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
                    $careTeam[$i]['role'] = self::DIRECTOR_OF_NURSING_ROLE;
                    $careTeam[$i]['id'] = $user->id;
                    $i++;
                }
            }
        }

        if (LD::checkFeatureFlag('add-recruiter-data-client-care')) {
            if (!empty($recruiter_data)) {
                $careTeam = array_merge($careTeam, [$recruiter_data]);
                $i = $i + 1;
            }
        }
        if (LD::checkFeatureFlag('add-recruiting-team-lead-and-recruiting-admin-in-care-team')) {
            if (!empty($recruitingDetails)) {
                $careTeam = array_merge($careTeam, $recruitingDetails);
                $i = $i + count($recruitingDetails);
            }
        }


        $isSOCExists = array_search('Start of Care Clinician', array_column($careTeam, 'role'));
        if ($isSOCExists === false && !empty($clientDetails->SOCClinicianID)) {
            $user = DB::table('mssql_providers')
                ->select('users.profile_picture', 'users.name', 'users.id')
                ->join('users', 'users.email', '=', 'mssql_providers.AOC_Email')
                ->where('mssql_providers.ProviderID', $clientDetails->SOCClinicianID)
                ->first();
            if (!empty($user)) {
                $careTeam[$i]['role'] = self::START_OF_CARE_CLINICIAN_ROLE;
                $careTeam[$i]['id'] = $user->id;
                $i++;
            }
        }

        return collect($careTeam);
    }
}
