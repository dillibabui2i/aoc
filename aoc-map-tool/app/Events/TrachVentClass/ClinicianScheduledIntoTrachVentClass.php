<?php

namespace App\Events\TrachVentClass;

use App\TrachVentClassOccurrence;
use App\TrachVentClassRequest;

final readonly class ClinicianScheduledIntoTrachVentClass
{
    public function __construct(
        public string $scheduledByName,
        public TrachVentClassOccurrence $occurrence,
        public string $nurseFirstName,
        public string $nurseLastName,
        public TrachVentClassRequest $registration,
    ) {
    }
}
