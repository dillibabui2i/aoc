<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;

final class AocNthRepository
{
    private const NTH_ACCOUNTS_TABLE = 'NeedToHire_Accounts';
    private const NTH_SCHEDULES_TABLE = 'NeedToHire_Schedules';
    private const MAPS_NTH_TABLE = 'maps_nth';

    /**
     * Find an active NTH account for the given AOC patient.
     * Returns an object with at least {id, user_id}, or null.
     */
    public function findActiveByPatientId(int $aocPatientId): ?object
    {
        return DB::table(self::NTH_ACCOUNTS_TABLE)
            ->select('id', 'user_id')
            ->where('aoc_patient_id', $aocPatientId)
            ->where('IsActive', 1)
            ->first();
    }

    /**
     * Deactivate all active schedules for the given NTH account (non-admit removal).
     */
    public function deactivateSchedules(int $nthAccountId): void
    {
        DB::table(self::NTH_SCHEDULES_TABLE)
            ->where('aoc_nthaccounts_id', $nthAccountId)
            ->where('IsActive', 1)
            ->update(['IsActive' => 0, 'RemovalReason' => 'Non-admit']);
    }

    /**
     * Deactivate the NTH account itself (non-admit removal).
     */
    public function deactivateAccount(int $nthAccountId): void
    {
        DB::table(self::NTH_ACCOUNTS_TABLE)
            ->where('id', $nthAccountId)
            ->update(['IsActive' => 0, 'RemovalReason' => 'Non-admit']);
    }

    /**
     * Deactivate the maps_nth entry for the given NTH account.
     */
    public function deactivateMap(int $nthAccountId): void
    {
        DB::table(self::MAPS_NTH_TABLE)
            ->where('aoc_nthaccounts_id', $nthAccountId)
            ->update(['IsActive' => 0]);
    }
}
