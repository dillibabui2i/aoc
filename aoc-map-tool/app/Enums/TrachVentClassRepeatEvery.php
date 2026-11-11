<?php

namespace App\Enums;

enum TrachVentClassRepeatEvery: string
{
    case DAY = 'day';
    case WEEK = 'week';
    case MONTH = 'month';
}
