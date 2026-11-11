<?php

namespace App\Repositories\NeedToHireAccounts;

use App\NeedToHireAccounts;

class NeedToHireAccountsWriteRepository
{
    public function __construct(private NeedToHireAccounts $needToHireAccounts)
    {
    }

    /**
     * @return int[] ids of NTH accounts that were active and got closed
     */
    public function syncOpenNthWithNotActiveAdmits(array $notActiveStatusesIds): array
    {
        $closedIds = $this->needToHireAccounts
            ->query()
            ->where('IsActive', 1)
            ->whereIn('aoc_patient_id', function ($q) use ($notActiveStatusesIds) {
                $q->select('id')
                    ->from('mssql_patients')
                    ->whereIn('Patient_CurrentStatusID', $notActiveStatusesIds);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (empty($closedIds)) {
            return [];
        }

        $this->needToHireAccounts
            ->query()
            ->whereIn('id', $closedIds)
            ->update(['IsActive' => 0]);

        return $closedIds;
    }
}
