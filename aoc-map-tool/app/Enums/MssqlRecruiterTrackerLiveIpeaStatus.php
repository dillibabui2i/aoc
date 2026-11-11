<?php

namespace App\Enums;

enum MssqlRecruiterTrackerLiveIpeaStatus: string
{
    case READY_TO_WORK = 'Ready to work';
    case READY_TO_SCHEDULE_NEXT_STEPS = 'Ready to schedule next steps';
    case RESCHEDULED = 'Rescheduled';
    case CANCELLED = 'Cancelled';
    case NCNS = 'NCNS';
    case NEED_TO_REVIEW_NEW_CLIENTS = 'Need to review new clients';
    case REMOVED = 'Removed';
}
