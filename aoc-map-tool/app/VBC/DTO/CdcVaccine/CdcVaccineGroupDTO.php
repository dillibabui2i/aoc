<?php

namespace App\VBC\DTO\CdcVaccine;

final readonly class CdcVaccineGroupDTO
{
    public function __construct(
        public int $cvxGroupCode,
        public ?string $cdcName,
    ) {
    }
}
