<?php

namespace App\Events\TrachVentClass;

use App\TrachVentClassOccurrence;
use App\TrachVentClassRequest;

final readonly class ClinicianRescheduledWithinTrachVentClass
{
    public function __construct(
        public string $rescheduledByName,
        public TrachVentClassOccurrence $occurrence,
        public TrachVentClassOccurrence $newOccurrence,
        public string $clinicianName,
        public TrachVentClassRequest $registration,
    ) {
    }
}
