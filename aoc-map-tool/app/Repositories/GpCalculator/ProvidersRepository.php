<?php

namespace App\Repositories\GpCalculator;

use App\Helpers\LD;
use Illuminate\Support\Facades\DB;

class ProvidersRepository
{
    public function getProvidersList(string $search, $limit = 30) : array
    {
        $clientList = DB::table('mssql_providers')
            ->select(
                'mssql_providers.id',
                'mssql_providers.FirstName',
                'mssql_providers.ClinicianID',
                'mssql_providers.LastName',
                'mssql_providers.External_Email',
                'mssql_providers.DisciplineName',
                'mssql_providers.Mobile',
                'mssql_providers.City',
                'mssql_providers.State',
                'mssql_providers.Mobile',
                'mssql_employmentstatus.EmploymentStatus',
                'mssql_facilities.BranchName',
                'mssql_providers.CF_NotEligibleForRehire'
            );

        if (LD::checkFeatureFlag('add-schedule-validation')) {
            $clientList->addSelect(DB::raw(
                '(SELECT GROUP_CONCAT(CONCAT(mssql_recruitertracker.Recruiter,"#",IFNULL(recruiting_schedule.appointment_date, ""),"#", IFNULL(mssql_recruitertracker.Branch_Location, "")) separator "**")
                FROM mssql_recruitertracker
                LEFT JOIN recruiting_schedule ON recruiting_schedule.tracker_id = mssql_recruitertracker.id
                WHERE mssql_recruitertracker.aoc_provider_id = mssql_providers.id
                    AND mssql_recruitertracker.is_active = 1 AND recruiting_schedule.type_id = 1
                    AND recruiting_schedule.appointment_date >= CURDATE()
                    GROUP BY mssql_recruitertracker.aoc_provider_id
                    ORDER BY mssql_recruitertracker.id DESC) AS ipea_schedule_ids'
            ));
            $clientList->addSelect(DB::raw(
                '(SELECT GROUP_CONCAT(CONCAT(Recruiter,"#",aoc_patient_id,"#",nth_schedule_ids,"#",Client_Name,"#",IFNULL(Agreed_Pay_Rate, "")) separator "**")
                FROM mssql_recruitertracker
                WHERE mssql_recruitertracker.aoc_provider_id = mssql_providers.id
                    AND mssql_recruitertracker.is_active = 1
                    AND mssql_recruitertracker.nth_schedule_ids IS NOT NULL
                    GROUP BY mssql_recruitertracker.aoc_provider_id
                    ORDER BY mssql_recruitertracker.id DESC) AS nth_schedule_ids'
            ));
        }

        $clientList
            ->join('mssql_employmentstatus', 'mssql_employmentstatus.EmploymentStatusID', '=', 'mssql_providers.EmploymentStatusID')
            ->join('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')
            ->where('mssql_providers.is_deleted', 0)
            ->where(
                function ($query) use ($search) {
                    $query->where('mssql_providers.Search_Name', 'like', '%' . $search . '%');
                    $query->orWhere('mssql_providers.ClinicianID', 'like', '%' . $search . '%');
                }
            )
            ->whereIn('mssql_providers.EmploymentStatusID', [1 /*Ready*/, 2/*Active*/, 12/*Terminated*/, 24/*Active Coverage */])
            ->orderBy('mssql_employmentstatus.employmentstatus', 'ASC');

        $clientList = $clientList->limit($limit)->get();

        return json_decode(json_encode($clientList), true);
    }
}
