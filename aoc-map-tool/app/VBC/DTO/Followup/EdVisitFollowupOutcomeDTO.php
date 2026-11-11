<?php

namespace App\VBC\DTO\Followup;

use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFollowupStatusEnum;

/** An ED Visit Follow-up outcome submission (PD-4073): the new follow-up status and its required notes. */
final readonly class EdVisitFollowupOutcomeDTO
{
    public function __construct(
        public HospitalizationTrackerFollowupStatusEnum $outcome,
        public string $notes,
    ) {
    }
}
