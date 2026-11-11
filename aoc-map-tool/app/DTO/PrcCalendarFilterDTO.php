<?php

namespace App\DTO;

use App\Enums\PrcShiftType;

class PrcCalendarFilterDTO
{
    /**
     * @param array<PrcShiftType> $shiftTypes Empty array means no Shift Type filter is applied (all shown).
     */
    public function __construct(
        public readonly array $shiftTypes = [],
        public readonly string $state = '',
        public readonly array $branch = []
    ) {
    }
}
