<?php

namespace App\VBC\DTO\Followup;

use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFollowupStatusEnum;
use Carbon\CarbonInterface;

/**
 * A Follow-up Outcome submission. Only the fields relevant to `outcome` are ever set by the request —
 * see UpdateFollowupOutcomeRequest — the rest stay null and are ignored by FollowupOutcomeService.
 */
final readonly class FollowupOutcomeDTO
{
    public function __construct(
        public HospitalizationTrackerFollowupStatusEnum $outcome,
        public ?CarbonInterface $scheduledAt = null,
        public ?int $assignedToId = null,
        public ?string $statusNote = null,
        public ?string $followUpsUpdates = null,
        public ?string $needs = null,
        public ?string $notes = null,
        public bool $nextFollowUpDue = false,
        public ?CarbonInterface $nextFollowUpDueDate = null,
        public ?int $nextAssignedToId = null,
    ) {
    }
}
