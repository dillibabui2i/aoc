<?php

namespace App\DTO\TrachVentClass;

readonly class ClinicianEnrollmentDTO
{
    public const CLINICIAN_TYPE_CURRENT = 'current';
    public const CLINICIAN_TYPE_NEW = 'new';

    public function __construct(
        public string $clinicianType,
        public ?int $providerId,
        public ?int $candidateId,
        public ?int $clientId,
        public ?string $discipline,
        public ?string $phoneNumber,
        public ?string $startDate,
    ) {
    }
}
