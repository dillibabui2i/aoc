<?php

namespace App\VBC\DTO\Hospitalization;

use App\VBC\Enum\Diagnosis\DiagnosisClinicalStatusEnum;
use App\VBC\Enum\Diagnosis\DiagnosisRankEnum;
use App\VBC\Enum\Diagnosis\DiagnosisSystemEnum;
use App\VBC\Enum\Diagnosis\DiagnosisVerificationStatusEnum;

/** One submit of the "Diagnosis Details" modal — shared shape for create and edit. */
final readonly class HospitalizationTrackerDiagnosisDTO
{
    public function __construct(
        public string $icd10Code,
        public string $description,
        public DiagnosisSystemEnum $system,
        public DiagnosisRankEnum $rank,
        public DiagnosisClinicalStatusEnum $clinicalStatus,
        public DiagnosisVerificationStatusEnum $verificationStatus,
        public ?string $notes,
    ) {
    }
}
