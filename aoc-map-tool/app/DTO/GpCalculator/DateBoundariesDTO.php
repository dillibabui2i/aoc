<?php

namespace App\DTO\GpCalculator;

readonly class DateBoundariesDTO
{
    public function __construct(
        public string $mainStartDate,
        public string $subStartDate,
        public string $endDate,
        public string $hoursStartDate,
        public string $hoursEndDate,
        public string $desiredRateStartDate,
        public string $desiredRateEndDate,
        public bool $applyRange,
        public string $rangeStartDate,
        public string $rangeEndDate
    ) {
    }
}
