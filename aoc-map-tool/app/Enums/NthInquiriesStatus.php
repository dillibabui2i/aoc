<?php

namespace App\Enums;

enum NthInquiriesStatus: string
{
    case RESOLVED = 'Resolved';
    case NEW = 'New';
    case PENDING = 'Pending';
}
