<?php

namespace App\Events\TrachVentClass;

use App\TrachVentClassOccurrence;
use App\TrachVentClassRequest;

final readonly class ClinicianCancelledFromTrachVentClass
{
    public function __construct(
        public string $cancelledByName,
        public TrachVentClassOccurrence $occurrence,
        public string $clinicianName,
        public TrachVentClassRequest $registration,
    ) {
    }
}
