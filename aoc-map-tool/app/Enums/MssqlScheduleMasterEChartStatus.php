<?php

namespace App\Enums;

enum MssqlScheduleMasterEChartStatus: int
{
    case APPROVED = 2;
    case SENT_FOR_CORRECTION = 3;
    case PLANNED = 4;
    case SUBMITTED = 5;
}


