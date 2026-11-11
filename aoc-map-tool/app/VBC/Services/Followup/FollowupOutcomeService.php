<?php

namespace App\VBC\Services\Followup;

use App\VBC\DTO\Followup\FollowupOutcomeDTO;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFollowupStatusEnum;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerFollowupModel;
use App\VBC\Repositories\Followup\FollowupReadRepository;
use App\VBC\Repositories\Followup\FollowupWriteRepository;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Applies a Follow-up Outcome (AC7-AC19): Open -> {Scheduled | Completed | Attempted | Canceled},
 * Scheduled -> {Completed | Attempted | Canceled} (re-selecting Scheduled from an already-Scheduled
 * follow-up is not offered). Completed/Attempted are terminal and, when Next Follow-up Due was
 * submitted, chain a new Scheduled follow-up against the same tracker/patient (AC13/AC17).
 */
class FollowupOutcomeService
{
    /** Which statuses an outcome may be applied from. */
    private const ALLOWED_SOURCE_STATUSES = [
        'SCHEDULED' => [HospitalizationTrackerFollowupStatusEnum::OPEN],
        'COMPLETED' => [HospitalizationTrackerFollowupStatusEnum::OPEN, HospitalizationTrackerFollowupStatusEnum::SCHEDULED],
        'ATTEMPTED' => [HospitalizationTrackerFollowupStatusEnum::OPEN, HospitalizationTrackerFollowupStatusEnum::SCHEDULED],
        'CANCELED' => [HospitalizationTrackerFollowupStatusEnum::OPEN, HospitalizationTrackerFollowupStatusEnum::SCHEDULED],
    ];

    private const CHAINING_OUTCOMES = [
        HospitalizationTrackerFollowupStatusEnum::COMPLETED,
        HospitalizationTrackerFollowupStatusEnum::ATTEMPTED,
    ];

    public function __construct(
        private readonly FollowupReadRepository $readRepository,
        private readonly FollowupWriteRepository $writeRepository,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function transition(HospitalizationTrackerFollowupModel $followup, FollowupOutcomeDTO $dto): HospitalizationTrackerFollowupModel
    {
        $actorId = (int) auth()->id();

        DB::transaction(function () use ($followup, $dto, $actorId) {
            $locked = $this->writeRepository->lockForUpdate($followup);
            $this->guardTransitionIsAllowed($locked, $dto->outcome);

            $this->writeRepository->applyOutcome($locked, $dto, $actorId);

            if (in_array($dto->outcome, self::CHAINING_OUTCOMES, true) && $dto->nextFollowUpDue) {
                $this->writeRepository->createScheduled(
                    $locked->hospitalization_tracker_id,
                    $locked->patient_id,
                    $actorId,
                    $dto->nextFollowUpDueDate,
                    $dto->nextAssignedToId,
                );
            }
        });

        return $this->readRepository->findForForm($followup->id);
    }

    private function guardTransitionIsAllowed(HospitalizationTrackerFollowupModel $followup, HospitalizationTrackerFollowupStatusEnum $outcome): void
    {
        $allowedSources = self::ALLOWED_SOURCE_STATUSES[$outcome->value] ?? [];

        if (!in_array($followup->status, $allowedSources, true)) {
            throw new InvalidFollowupOutcomeTransitionException(
                "Cannot move a {$followup->status->label()} follow-up to {$outcome->label()}."
            );
        }
    }
}
