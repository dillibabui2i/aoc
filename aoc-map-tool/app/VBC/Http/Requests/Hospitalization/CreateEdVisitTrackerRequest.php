<?php

namespace App\VBC\Http\Requests\Hospitalization;

use App\VBC\DTO\Hospitalization\HospitalizationTrackerCreationDTO;
use App\VBC\DTO\Hospitalization\HospitalizationTrackerFieldsDTO;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFieldEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;

/**
 * "ED Visit Record" form submit (PD-3983). Same client/header-field validation as the Hospitalization
 * Record form — reuses its `rules()`/`attributes()` as-is, since the task carries over the data
 * dictionary verbatim — but the tracker it creates is an ED encounter saved straight to "In Progress"
 * (AC7) instead of an IP encounter saved to "Open".
 */
class CreateEdVisitTrackerRequest extends CreateHospitalizationTrackerRequest
{
    public function toDto(): HospitalizationTrackerCreationDTO
    {
        $submitted = array_intersect_key(
            $this->validated(),
            array_flip(HospitalizationTrackerFieldEnum::headerFieldValues())
        );

        return new HospitalizationTrackerCreationDTO(
            patientId: (int) $this->input('patient_id'),
            fields: HospitalizationTrackerFieldsDTO::cast($submitted),
            encounterType: EncounterTypeEnum::ED,
            initialStatus: HospitalizationTrackerStatusEnum::IN_PROGRESS,
        );
    }
}
