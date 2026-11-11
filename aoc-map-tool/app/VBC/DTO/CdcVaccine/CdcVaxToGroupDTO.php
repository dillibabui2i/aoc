<?php

namespace App\VBC\DTO\CdcVaccine;

final readonly class CdcVaxToGroupDTO
{
    /**
     * @param array<int, CdcVaccineGroupDTO> $groups Unique groups, keyed by cvx_group_code
     * @param array<int, int> $vaccineGroupCodes cvx_code => cvx_group_code (first group wins)
     */
    public function __construct(
        public array $groups,
        public array $vaccineGroupCodes,
    ) {
    }
}
