<?php

namespace App\VBC\Services\HospitalizationTrackerDiagnosis;

use App\VBC\DTO\Hospitalization\HospitalizationTrackerDiagnosisDTO;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Models\EncounterDiagnosis\EncounterDiagnosisModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use App\VBC\Repositories\Hospitalization\HospitalizationWriteRepository;
use App\VBC\Repositories\HospitalizationTrackerDiagnosis\HospitalizationTrackerDiagnosisWriteRepository;

/**
 * Diagnoses (PD-3498) are only creatable/editable while the tracker they belong to is "In Progress" —
 * every other status shows them read-only (AC16/AC17). Mirrors HospitalizationTrackerContactService's
 * guardEditable() on both create and update, for the same reason.
 */
class HospitalizationTrackerDiagnosisService
{
    /** hospitalization_tracker_custom_property_definitions.key for the "Was the diagnosis made?" toggle. */
    private const WAS_DIAGNOSIS_MADE_PROPERTY_KEY = 'was_diagnosis_made';

    public function __construct(
        private readonly HospitalizationTrackerDiagnosisWriteRepository $writeRepository,
        private readonly HospitalizationWriteRepository $trackerWriteRepository,
    ) {
    }

    public function create(
        HospitalizationTrackerModel $tracker,
        HospitalizationTrackerDiagnosisDTO $dto,
    ): EncounterDiagnosisModel {
        $this->guardEditable($tracker);

        // "Created by" is always the current user, never client input — see the request classes.
        return $this->writeRepository->create($tracker->encounter_id, $dto, (int) auth()->id());
    }

    public function update(
        HospitalizationTrackerModel $tracker,
        EncounterDiagnosisModel $diagnosis,
        HospitalizationTrackerDiagnosisDTO $dto,
    ): EncounterDiagnosisModel {
        $this->guardEditable($tracker);

        return $this->writeRepository->update($diagnosis, $dto);
    }

    /**
     * The toggle isn't a HospitalizationTrackerFieldEnum case (it belongs to the Diagnosis tab, not the
     * header form or Current Hospitalization Info section — see HospitalizationTrackerDiagnosisController),
     * so it reuses HospitalizationWriteRepository's tracker-property EAV upsert directly rather than
     * going through that enum's dispatch.
     */
    public function setWasDiagnosisMade(HospitalizationTrackerModel $tracker, bool $value): void
    {
        $this->guardEditable($tracker);

        $this->trackerWriteRepository->updateTrackerProperty($tracker, self::WAS_DIAGNOSIS_MADE_PROPERTY_KEY, $value);
    }

    private function guardEditable(HospitalizationTrackerModel $tracker): void
    {
        if ($tracker->status !== HospitalizationTrackerStatusEnum::IN_PROGRESS) {
            throw new HospitalizationTrackerDiagnosisNotEditableException(
                "Diagnoses are only editable while the tracker is In Progress (currently {$tracker->status->label()})."
            );
        }
    }
}
