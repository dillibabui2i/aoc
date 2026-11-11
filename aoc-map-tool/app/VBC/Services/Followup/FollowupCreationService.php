<?php

namespace App\VBC\Services\Followup;

use App\VBC\DTO\Followup\FollowupCreationDTO;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerFollowupModel;
use App\VBC\Repositories\Followup\FollowupReadRepository;
use App\VBC\Repositories\Followup\FollowupWriteRepository;
use App\VBC\Repositories\Hospitalization\HospitalizationReadRepository;
use RuntimeException;

/** Creates a Follow-up against a selected hospitalization tracker, Open from the first save (AC6). */
class FollowupCreationService
{
    public function __construct(
        private readonly HospitalizationReadRepository $hospitalizationReadRepository,
        private readonly FollowupReadRepository $readRepository,
        private readonly FollowupWriteRepository $writeRepository,
    ) {
    }

    public function create(FollowupCreationDTO $dto): HospitalizationTrackerFollowupModel
    {
        $tracker = $this->hospitalizationReadRepository->findForForm($dto->hospitalizationTrackerId);

        if (empty($tracker)) {
            throw new RuntimeException("Hospitalization tracker [{$dto->hospitalizationTrackerId}] not found.");
        }

        $followup = $this->writeRepository->create(
            $tracker->id,
            $tracker->encounter->patient_id,
            (int) auth()->id(),
        );

        return $this->readRepository->findForForm($followup->id);
    }
}
