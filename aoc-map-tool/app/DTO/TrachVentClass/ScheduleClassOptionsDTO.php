<?php

namespace App\DTO\TrachVentClass;

final readonly class ScheduleClassOptionsDTO
{
    public function __construct(
        public int $locationId,
        public int $trachVentClassType,
        public ?string $classTypeLabel,
    ) {
    }
}
