<?php

namespace App\Enums\NTH;

enum ScheduleTypeEnum: int
{
    case IPEA = 1;
    case CCR = 2;
    case PEPI = 3;
    case WEPI = 4;
    case TRAINING_SHIFT = 5;
    case CANDIDATE_SHIFT = 6;
    case SCHOOL_SET_UP = 7;
    case NEEDS_TO_BE_SCHEDULED_BY_RA = 8;
}
