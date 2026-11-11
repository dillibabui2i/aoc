<?php

namespace App\Repositories\NeedToHireAccounts;

use App\NeedToHireAccounts;
use Illuminate\Support\Facades\DB;

class NeedToHireAccountsReadRepository
{
    public function __construct(private NeedToHireAccounts $needToHireAccounts)
    {
    }

    public function getAccountsFilteredByNthAccountID(int $accountID): ?NeedToHireAccounts
    {
        return $this->needToHireAccounts->select(
            'NeedToHire_Accounts.user_id as assignedRecruiter',
            'NeedToHire_Accounts.IntakeCoordinator as intakeCoordinator',
            'NeedToHire_Accounts.ClientCareCoordinator as clientCareCoordinator',
            'users.recruiting_teamleader as recruitingTeamLead',
            DB::raw(
                "CONCAT(mssql_patients.Patient_FirstName, ' ', IFNULL(mssql_patients.Patient_MiddleName, ''), ' ', mssql_patients.Patient_LastName) as full_name"
            ),
            DB::raw("mssql_patients.id as profileID")
        )
            ->leftJoin('mssql_patients', 'mssql_patients.id', 'NeedToHire_Accounts.aoc_patient_id')
            ->leftJoin('users', 'users.id', 'NeedToHire_Accounts.user_id')
            ->where('NeedToHire_Accounts.id', $accountID)
            ->first();
    }
}
