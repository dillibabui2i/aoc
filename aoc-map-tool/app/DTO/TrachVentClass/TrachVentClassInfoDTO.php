<?php

namespace App\DTO\TrachVentClass;

use App\Enums\TrachVentClassRepeatEvery;
use App\TrachVentClassOccurrence;

final readonly class TrachVentClassInfoDTO
{
    public function __construct(
        public int $occurrenceId,
        public int $locationId,
        public ?string $branchState,
        public string $classDate,
        public string $classTime,
        public ?string $endTime,
        public ?string $classCategoryName,
        public string $instructorName,
        public int $capacity,
        public ?string $notes,
        public int $enrolledCount,
        public bool $isFull,
        public bool $isRecurring,
        public bool $isLastOccurrence,
        public bool $canScheduleClinician,
        public bool $canCancelOccurrence,
        public ?TrachVentClassRepeatEvery $repeatEvery,
        public array $repeatOnDays,
    ) {
    }

    public static function fromOccurrence(TrachVentClassOccurrence $occurrence, string $instructorName, bool $canScheduleClinician, bool $canCancelOccurrence): self
    {
        $class = $occurrence->trachVentClass;
        $enrolledCount = $occurrence->enrolledCount();

        return new self(
            occurrenceId: $occurrence->id,
            locationId: $class->location_id,
            branchState: $class->branchLocation?->state,
            classDate: $occurrence->class_date,
            classTime: $occurrence->class_time,
            endTime: $occurrence->end_time,
            classCategoryName: $class->classCategory?->name,
            instructorName: $instructorName,
            capacity: (int) $occurrence->capacity,
            notes: $occurrence->notes,
            enrolledCount: $enrolledCount,
            isFull: $enrolledCount >= (int) $occurrence->capacity,
            isRecurring: $class->isRecurring(),
            isLastOccurrence: $class->isLastOccurrence($occurrence->class_date),
            canScheduleClinician: $canScheduleClinician,
            canCancelOccurrence: $canCancelOccurrence,
            repeatEvery: $class->repeat_every,
            repeatOnDays: $class->repeatOnDays(),
        );
    }
}
