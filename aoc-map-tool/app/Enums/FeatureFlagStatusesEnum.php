<?php

namespace App\Enums;

enum FeatureFlagStatusesEnum: int
{
    case ENABLED = 1;
    case DISABLED = -1;
    case TARGETING_OFF = 0;
}
