<?php

namespace App\Enums;

enum NTHLeadsAndInqInquiriesStatusEnum: string
{
    case NEW = 'New';
    case PENDING = 'Pending';
    case RESOLVED = 'Resolved';
}
