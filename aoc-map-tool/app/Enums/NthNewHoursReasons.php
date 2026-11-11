<?php

declare(strict_types=1);

namespace App\Enums;

enum NthNewHoursReasons: string
{
    case CAREGIVER_AUTHORIZED_ADDITIONAL_HOURS = 'Caregiver - Authorized for Additional Hours';
    case CAREGIVER_NEW_ELIGIBILITY = 'Caregiver - New Eligibility';
    case CAREGIVER_UTILIZING_ADDITIONAL_HOURS = 'Caregiver - Utilizing Additional Hours';
    case AOC_NEW_INTAKE = 'AOC - New Intake';
    case AOC_FAMILY_DECLINED_HOURS = 'AOC - Family declined hours';
    case CAREGIVER_DECLINED_SERVICES = 'Caregiver - Declined services';
    case CAREGIVER_FAMILY_DECLINED_HOURS = 'Caregiver - Family declined hours';
    case FAMILY_DECLINED_HOURS = 'Family declined hours';
    case AOC_REDUCED_HOURS = 'AOC - Reduced hours';
    case CAREGIVER_REDUCED_HOURS = 'Caregiver - Reduced hours';
    case CAREGIVER_REDUCTION_AUTHORIZED_HOURS = 'Caregiver - Reduction in Authorized Hours';
    case REDUCED_HOURS = 'Reduced hours';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getSqlString(): string
    {
        return "'" . implode("', '", self::values()) . "'";
    }
}
