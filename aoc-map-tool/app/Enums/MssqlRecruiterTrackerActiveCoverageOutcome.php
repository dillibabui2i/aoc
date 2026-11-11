<?php

namespace App\Enums;

enum MssqlRecruiterTrackerActiveCoverageOutcome: string
{
    case CONFIRM_COMPLETED = 'Confirm Completed';
    case COMPLETED = 'Completed';
    case INACTIVE = 'Inactive';
    case ESCALATED_TO_CCM = 'Escalated to CCM';
    case STATUS_CHANGE_REQUEST = 'Status Change Request';
    case NO_LONGER_INTERESTED_MOVE_TO_NEEDS_TO_REVIEW = 'No longer interested in Active Coverage - move to needs to review';
}
