<?php

namespace App\DTO\TrachVentClass;

use App\Enums\TrachVentClassRepeatEvery;

final readonly class ScheduleTrachVentClassDTO
{
    public function __construct(
        public string $type,
        public int $locationId,
        public ?int $instructorId,
        public string $classDate,
        public string $classTime,
        public string $endTime,
        public int $capacity,
        public ?string $notes,
        public ?string $classType,
        public ?TrachVentClassRepeatEvery $repeatEvery,
        public array $repeatOn,
        public ?string $repeatUntil,
    ) {
    }
}
