<?php

namespace App\VBC\Services\HospitalizationTrackerTraining;

use App\VBC\DTO\Hospitalization\HospitalizationTrackerTrainingDTO;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerTrainingModel;
use App\VBC\Repositories\Hospitalization\HospitalizationWriteRepository;
use App\VBC\Repositories\HospitalizationTrackerTraining\HospitalizationTrackerTrainingWriteRepository;

/**
 * Training/Education records are only creatable/editable while the tracker they belong to is
 * "In Progress" — every other status shows them read-only. Mirrors
 * HospitalizationTrackerContactService's guardEditable() on both create and update, for the same reason.
 */
class HospitalizationTrackerTrainingService
{
    /** hospitalization_tracker_custom_property_definitions.key for the "Staff / Family
     * Training/Education required?" toggle. */
    private const STAFF_FAMILY_TRAINING_REQUIRED_PROPERTY_KEY = 'staff_family_training_required';

    public function __construct(
        private readonly HospitalizationTrackerTrainingWriteRepository $writeRepository,
        private readonly HospitalizationWriteRepository $trackerWriteRepository,
    ) {
    }

    public function create(
        HospitalizationTrackerModel $tracker,
        HospitalizationTrackerTrainingDTO $dto,
    ): HospitalizationTrackerTrainingModel {
        $this->guardEditable($tracker);

        return $this->writeRepository->create($tracker->id, $dto, (int) auth()->id());
    }

    public function update(
        HospitalizationTrackerModel $tracker,
        HospitalizationTrackerTrainingModel $training,
        HospitalizationTrackerTrainingDTO $dto,
    ): HospitalizationTrackerTrainingModel {
        $this->guardEditable($tracker);

        return $this->writeRepository->update($training, $dto, (int) auth()->id());
    }

    /**
     * The toggle isn't a HospitalizationTrackerFieldEnum case (it belongs to the Education tab, not the
     * header form or Current Hospitalization Info section — see HospitalizationTrackerDiagnosisService's
     * identical reasoning for "Was the diagnosis made?"), so it reuses HospitalizationWriteRepository's
     * tracker-property EAV upsert directly rather than going through that enum's dispatch.
     */
    public function setStaffFamilyTrainingRequired(HospitalizationTrackerModel $tracker, bool $value): void
    {
        $this->guardEditable($tracker);

        $this->trackerWriteRepository->updateTrackerProperty(
            $tracker,
            self::STAFF_FAMILY_TRAINING_REQUIRED_PROPERTY_KEY,
            $value,
        );
    }

    private function guardEditable(HospitalizationTrackerModel $tracker): void
    {
        if ($tracker->status !== HospitalizationTrackerStatusEnum::IN_PROGRESS) {
            throw new HospitalizationTrackerTrainingNotEditableException(
                "Training/Education records are only editable while the tracker is In Progress (currently {$tracker->status->label()})."
            );
        }
    }
}
