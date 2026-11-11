<?php

namespace App\Enums;

enum ScheduleCandidateType: string
{
    case NEW = 'new';
    case CURRENT = 'current';
    case ACTIVE_COVEREGE = 'prn';
}
