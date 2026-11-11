<?php

namespace App\DTO\TrachVentClass;

use App\TrachVentClassOccurrence;
use Carbon\Carbon;

final readonly class ClassEmailDetailsDTO
{
    public function __construct(
        public int $occurrenceId,
        public string $classType,
        public string $longDate,
        public string $shortDate,
        public string $startTime,
        public string $timeRange,
        public string $location,
        public string $instructor,
        public string $branchPhone,
    ) {
    }

    public static function fromOccurrence(TrachVentClassOccurrence $occurrence, string $instructorName): self
    {
        $class = $occurrence->trachVentClass;
        $branch = $class->branchLocation;
        $date = Carbon::parse($occurrence->class_date);

        $locationParts = array_filter([
            $branch?->address,
            $branch?->city,
            trim(($branch?->state ?? '') . ' ' . ($branch?->zipcode ?? '')),
        ], fn ($part) => $part !== null && trim($part) !== '');

        return new self(
            occurrenceId: $occurrence->id,
            classType: (string) ($class->classCategory?->name ?? ''),
            longDate: $date->format('l, F j, Y'),
            shortDate: getFormatedDate($occurrence->class_date),
            startTime: getFormatedTime($occurrence->class_time),
            timeRange: getFormatedTimeRange($occurrence->class_time, $occurrence->end_time),
            location: implode(', ', $locationParts),
            instructor: $instructorName,
            branchPhone: trim((string) ($branch?->phone_number ?? '')),
        );
    }
}
