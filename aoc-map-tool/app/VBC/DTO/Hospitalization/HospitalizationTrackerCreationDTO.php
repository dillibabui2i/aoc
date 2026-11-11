<?php

namespace App\VBC\DTO\Hospitalization;

use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;

/**
 * One submit of a tracker-creation form: the client it is opened for, the fields set on it, and which
 * kind of tracker is being created. Defaults to the "Hospitalization Record" form's own values (IP /
 * Open); `CreateEdVisitTrackerRequest` overrides both for the "ED Visit Record" form (PD-3983), which
 * reuses this same creation path rather than a separate one.
 */
final readonly class HospitalizationTrackerCreationDTO
{
    public function __construct(
        public int $patientId,
        public HospitalizationTrackerFieldsDTO $fields,
        public EncounterTypeEnum $encounterType = EncounterTypeEnum::IP,
        public HospitalizationTrackerStatusEnum $initialStatus = HospitalizationTrackerStatusEnum::OPEN,
    ) {
    }
}
