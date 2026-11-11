<?php

namespace App\VBC\DTO\Hospitalization;

use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerClosingReasonEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;

/**
 * An Outcome submission on the "Hospitalization Record" modal: the status to move the tracker to, and
 * whichever of the following the outcome needs — the closing reason (Closed), the discharge-time fields
 * (Discharged), the user to assign the tracker to (Ready for Review / Ready for Next Steps), and the note
 * text to append to the Activity Log (every discharge-lifecycle outcome).
 *
 * Closed with the Transfer reason is the transfer workflow: `fields` then holds the Current Hospitalization
 * Info of the hospitalization the patient moves to, and `notes` the Transfer Reason (stored on the new encounter).
 */
final readonly class HospitalizationTrackerOutcomeDTO
{
    public function __construct(
        public HospitalizationTrackerStatusEnum $outcome,
        public ?HospitalizationTrackerClosingReasonEnum $reason,
        public ?HospitalizationTrackerFieldsDTO $fields = null,
        public ?int $assigneeId = null,
        public ?string $notes = null,
    )
    {}

    /** What makes an Outcome submission a transfer — static because the Request checks it before a DTO exists. */
    public static function isTransfer(?HospitalizationTrackerStatusEnum $outcome, ?HospitalizationTrackerClosingReasonEnum $reason): bool
    {
        return $outcome === HospitalizationTrackerStatusEnum::CLOSED
            && $reason === HospitalizationTrackerClosingReasonEnum::TRANSFERRED;
    }
}
