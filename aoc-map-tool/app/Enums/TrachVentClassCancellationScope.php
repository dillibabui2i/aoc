<?php

namespace App\Enums;

enum TrachVentClassCancellationScope: string
{
    case OCCURRENCE = 'occurrence';
    case FUTURE = 'future';
    case SERIES = 'series';
}
