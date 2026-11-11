<?php

namespace App\VBC\Rules;

use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Repositories\CareCoordination\CareCoordinationReadRepository;
use Illuminate\Contracts\Validation\Rule;

/**
 * A Care Coordination activity may only be created against a hospitalization tracker that is currently
 * "In Progress" (AC6) — narrower than the "active" statuses the patient search itself offers (AC5), so
 * this is checked here rather than by restricting the search.
 */
readonly class CareCoordinationTrackerIsInProgress implements Rule
{
    public function __construct(private CareCoordinationReadRepository $repository, private EncounterTypeEnum $encounterType)
    {
    }

    /**
     * @param  string  $attribute
     * @param  mixed  $value
     */
    public function passes($attribute, $value): bool
    {
        $tracker = $this->repository->findTrackerById((int) $value);

        if (empty($tracker) || $tracker->status !== HospitalizationTrackerStatusEnum::IN_PROGRESS) {
            return false;
        }

        // PD-3969: an ED activity may only be created against an ED tracker (and IP against IP).
        return $tracker->encounter->type === $this->encounterType
            && $this->repository->isTrackerEligibleForCreation($tracker->id, $this->encounterType);
    }

    public function message(): string
    {
        return $this->encounterType === EncounterTypeEnum::ED
            ? 'The selected patient does not have an ED visit tracker in progress.'
            : 'The selected patient does not have a hospitalization tracker in progress.';
    }
}
