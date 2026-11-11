<?php

namespace App\Enums;

enum EntityTypeEnum: string
{
    case CLINICIAN = 'clinician';
    case CANDIDATE = 'candidate';
    case CLIENT = 'client';
}
