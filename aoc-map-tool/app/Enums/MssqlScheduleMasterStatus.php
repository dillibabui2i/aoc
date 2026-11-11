<?php

namespace App\Enums;

enum MssqlScheduleMasterStatus: int
{
    case APPROVED = 1;
    case COMPLETED = 2;
    case IN_PROGRESS = 3;
    case MISSED_VISIT = 4;
    case PLANNED = 5;
    case NA = 6;
    case NO = 7;
    case OFFICE_AUTH = 8;
    case REAL_AUTH = 9;
}


