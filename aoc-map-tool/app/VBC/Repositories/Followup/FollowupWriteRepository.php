<?php

namespace App\VBC\Repositories\Followup;

use App\VBC\DTO\Followup\EdVisitFollowupOutcomeDTO;
use App\VBC\DTO\Followup\FollowupOutcomeDTO;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFollowupStatusEnum;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerFollowupModel;
use Carbon\CarbonInterface;

class FollowupWriteRepository
{
    public function __construct(private HospitalizationTrackerFollowupModel $followupModel)
    {
    }

    /** A new Follow-up, always created Open (AC6) — "New" is a UI-only stage before the first save. */
    public function create(int $hospitalizationTrackerId, int $patientId, int $requestedById): HospitalizationTrackerFollowupModel
    {
        /** @var HospitalizationTrackerFollowupModel $followup */
        $followup = $this->followupModel->newQuery()->create([
            'hospitalization_tracker_id' => $hospitalizationTrackerId,
            'patient_id' => $patientId,
            'requested_by_id' => $requestedById,
            'status' => HospitalizationTrackerFollowupStatusEnum::OPEN,
        ]);

        return $followup;
    }

    /** ED Visit follow-up (PD-4073): created lazily on the first outcome submit, always Not Started. */
    public function createNotStarted(int $hospitalizationTrackerId, int $patientId, int $requestedById): HospitalizationTrackerFollowupModel
    {
        /** @var HospitalizationTrackerFollowupModel $followup */
        $followup = $this->followupModel->newQuery()->create([
            'hospitalization_tracker_id' => $hospitalizationTrackerId,
            'patient_id' => $patientId,
            'requested_by_id' => $requestedById,
            'status' => HospitalizationTrackerFollowupStatusEnum::NOT_STARTED,
        ]);

        return $followup;
    }

    public function applyEdVisitOutcome(HospitalizationTrackerFollowupModel $followup, EdVisitFollowupOutcomeDTO $dto, int $actorId): void
    {
        $isCompleted = $dto->outcome === HospitalizationTrackerFollowupStatusEnum::COMPLETED;

        $followup->update(array_filter([
            'status' => $dto->outcome,
            'notes' => $dto->notes,
            'attempted_at' => $dto->outcome->isAttempt() ? now() : null,
            'attempted_by_id' => $dto->outcome->isAttempt() ? $actorId : null,
            'completed_at' => $isCompleted ? now() : null,
            'completed_by_id' => $isCompleted ? $actorId : null,
        ], fn ($value) => $value !== null));
    }

    /**
     * Takes a write lock on this Follow-up's row so a concurrent Outcome submission blocks until this
     * one commits, then re-reads its status fresh — same TOCTOU concern as the Hospitalization Tracker's
     * own outcome transition.
     */
    public function lockForUpdate(HospitalizationTrackerFollowupModel $followup): HospitalizationTrackerFollowupModel
    {
        /** @var HospitalizationTrackerFollowupModel $locked */
        $locked = $this->followupModel->newQuery()
            ->lockForUpdate()
            ->findOrFail($followup->id);

        return $locked;
    }

    public function applyOutcome(HospitalizationTrackerFollowupModel $followup, FollowupOutcomeDTO $dto, int $actorId): void
    {
        $followup->update(array_filter([
            'status' => $dto->outcome,
            'scheduled_at' => $dto->scheduledAt,
            'assigned_to_id' => $dto->assignedToId,
            'attempted_at' => $dto->outcome === HospitalizationTrackerFollowupStatusEnum::ATTEMPTED ? now() : null,
            'attempted_by_id' => $dto->outcome === HospitalizationTrackerFollowupStatusEnum::ATTEMPTED ? $actorId : null,
            'completed_at' => $dto->outcome === HospitalizationTrackerFollowupStatusEnum::COMPLETED ? now() : null,
            'completed_by_id' => $dto->outcome === HospitalizationTrackerFollowupStatusEnum::COMPLETED ? $actorId : null,
            'status_note' => $dto->statusNote,
            'follow_ups_updates' => $dto->followUpsUpdates,
            'needs' => $dto->needs,
            'notes' => $dto->notes,
        ], fn ($value) => $value !== null));
    }

    /** The chained next Follow-up (AC13/AC17), created Scheduled directly against the same tracker/patient. */
    public function createScheduled(
        int $hospitalizationTrackerId,
        int $patientId,
        int $requestedById,
        CarbonInterface $scheduledAt,
        int $assignedToId,
    ): HospitalizationTrackerFollowupModel {
        /** @var HospitalizationTrackerFollowupModel $followup */
        $followup = $this->followupModel->newQuery()->create([
            'hospitalization_tracker_id' => $hospitalizationTrackerId,
            'patient_id' => $patientId,
            'requested_by_id' => $requestedById,
            'status' => HospitalizationTrackerFollowupStatusEnum::SCHEDULED,
            'scheduled_at' => $scheduledAt,
            'assigned_to_id' => $assignedToId,
        ]);

        return $followup;
    }
}
