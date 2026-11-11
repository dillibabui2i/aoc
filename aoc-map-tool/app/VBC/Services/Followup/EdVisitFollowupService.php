<?php

namespace App\VBC\Services\Followup;

use App\VBC\DTO\Followup\EdVisitFollowupOutcomeDTO;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFollowupStatusEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use App\VBC\Repositories\Followup\FollowupReadRepository;
use App\VBC\Repositories\Followup\FollowupWriteRepository;
use App\VBC\Repositories\Hospitalization\HospitalizationReadRepository;
use App\VBC\Repositories\Hospitalization\HospitalizationWriteRepository;
use App\VBC\Services\Hospitalization\HospitalizationTrackerActivityLog;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * ED Visit follow-up workflow (PD-4073): Not Started -> First -> Second -> Third Attempt, with Completed
 * reachable before the third attempt (see HospitalizationTrackerFollowupStatusEnum::edVisitNextOutcomes()).
 * The tracker has no follow-up until the first submit, which lazily creates it Not Started and moves it on
 * in the same transaction. Every submit adds the notes to the tracker's Activity Log (the notes panel).
 */
class EdVisitFollowupService
{
    public function __construct(
        private readonly HospitalizationReadRepository $trackerReadRepository,
        private readonly HospitalizationWriteRepository $trackerWriteRepository,
        private readonly FollowupReadRepository $followupReadRepository,
        private readonly FollowupWriteRepository $followupWriteRepository,
        private readonly HospitalizationTrackerActivityLog $activityLog,
    ) {
    }

    /**
     * @throws Throwable
     * @throws InvalidFollowupOutcomeTransitionException
     */
    public function transition(HospitalizationTrackerModel $tracker, EdVisitFollowupOutcomeDTO $dto): ?HospitalizationTrackerModel
    {
        $actorId = (int) auth()->id();
        $author = auth()->user()?->name;
        $when = now()->format(config('vbc.date_time_format'));

        DB::transaction(function () use ($tracker, $dto, $actorId, $author, $when) {
            // Locks the tracker row, so two concurrent first submits can't both create a follow-up and
            // racing submits re-check the transition against the status the other one landed on.
            $locked = $this->trackerWriteRepository->lockForUpdate($tracker);

            if (in_array($locked->status, [HospitalizationTrackerStatusEnum::COMPLETED, HospitalizationTrackerStatusEnum::CLOSED], true)) {
                throw new InvalidFollowupOutcomeTransitionException(
                    "Cannot update the follow-up of a {$locked->status->label()} ED Visit."
                );
            }

            $followup = $this->followupReadRepository->findLatestEdVisitFollowup($locked->id)
                ?? $this->followupWriteRepository->createNotStarted(
                    $locked->id,
                    $locked->encounter->patient_id,
                    $actorId,
                );

            if (!in_array($dto->outcome, $followup->status->edVisitNextOutcomes(), true)) {
                throw new InvalidFollowupOutcomeTransitionException(
                    "Cannot move a {$followup->status->label()} follow-up to {$dto->outcome->label()}."
                );
            }

            $this->followupWriteRepository->applyEdVisitOutcome($followup, $dto, $actorId);

            $this->activityLog->write(
                $locked,
                "Follow-up {$dto->outcome->label()} by {$author} at {$when}\nFollow up notes: {$dto->notes}"
            );
        });

        return $this->trackerReadRepository->findForForm($tracker->id);
    }
}
