<?php

namespace App\DTO\TrachVentClass;

final readonly class UpdateTrachVentClassDTO
{
    public function __construct(
        public int $classId,
        public ?string $type,
        public ?int $locationId,
        public ?int $instructorId,
        public string $classDate,
        public string $classTime,
        public string $endTime,
        public int $capacity,
        public ?string $notes,
        public ?string $classType,
        public ?string $repeatUntil,
    ) {
    }
}
