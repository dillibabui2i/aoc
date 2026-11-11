<?php

namespace App\Repositories\GpCalculator;

interface ScheduleMasterRepositoryInterface
{
    public function getPatientScheduledData(
        int $patientId,
        ?bool $permOnly = false,
        ?string $startWeek = '',
        ?string $endWeek = '',
        ?bool $serviceWise = false
    ): array;
}
