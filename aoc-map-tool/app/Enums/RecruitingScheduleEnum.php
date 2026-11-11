<?php

namespace App\Enums;

enum RecruitingScheduleEnum: string
{
    case DISCIPLINE_RN = 'RN';
    case DISCIPLINE_LVN_LPN = 'LVN/LPN';
    case DISCIPLINE_CNA = 'CNA';
    case DISCIPLINE_LVN = 'LVN';
    case DISCIPLINE_LPN = 'LPN';
}
