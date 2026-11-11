<?php

namespace App\DTO\TrachVentClass;

final readonly class WeekEventsFilterDTO
{
    public function __construct(
        public string $state,
        public string $classDate,
        public ?int $branchId,
        public ?string $searchLabel,
    ) {
    }
}
