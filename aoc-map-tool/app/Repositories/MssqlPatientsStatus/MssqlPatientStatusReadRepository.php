<?php

namespace App\Repositories\MssqlPatientsStatus;

use App\MssqlPatientStatus;
use App\Enums\MssqlPatientStatus as MssqlPatientStatusEnum;

class MssqlPatientStatusReadRepository
{
    /**
     * @param MssqlPatientStatus $mssqlPatientStatus
     */
    public function __construct(private MssqlPatientStatus $mssqlPatientStatus)
    {
    }

    /**
     * @return array
     */
    public function getNotActiveStatusesIds(): array
    {
        return $this->mssqlPatientStatus
            ->query()
            ->whereIn('PatientStatus', [
                MssqlPatientStatusEnum::INACTIVE->label(),
                MssqlPatientStatusEnum::NON_ADMIT->label(),
                MssqlPatientStatusEnum::DISCHARGED->label(),
            ])
            ->pluck('id')
            ->toArray();
    }
}
