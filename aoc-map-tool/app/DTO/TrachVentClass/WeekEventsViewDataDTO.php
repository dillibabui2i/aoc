<?php

namespace App\DTO\TrachVentClass;

final readonly class WeekEventsViewDataDTO
{
    public function __construct(
        public array $scheduleData,
        public array $branches,
        public array $branchData,
        public array $week,
        public string $state,
    ) {
    }
}
