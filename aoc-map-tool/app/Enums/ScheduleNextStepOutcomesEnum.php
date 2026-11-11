<?php

namespace App\Enums;

enum ScheduleNextStepOutcomesEnum: string
{
    case SCHEDULE_NEXT_STEP = 'Schedule Next Step';
    case NURSE_DECIDED_NOT_TO_WORK_FOR_ANGELS_OF_CARE = 'Nurse decided not to work for Angels of Care';
    case INACTIVATE_ON_TRACKER = 'Inactivate on tracker';
    case PENDING_CLINICIAN_STATUS = 'Pending Clinician Status';
    case PENDING_CLIENT = 'Pending Client';

    public static function getPendingOutcomes(): array
    {
        return [
            self::PENDING_CLIENT->value,
            self::PENDING_CLINICIAN_STATUS->value,
        ];
    }
}
