<?php

namespace App\Enums;

enum MssqlScheduleMasterType: string
{
    case BILL_READY = 'bill_ready';
    case NOT_BILL_READY = 'bill_not_ready';
}


