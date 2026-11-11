<?php

namespace App\VBC\Enum\CdcVaccine;

/** `cdc_vaccines.cdc_status` as published by the CDC. */
enum CdcVaccineStatusEnum: string
{
    case ACTIVE = 'Active';
    case INACTIVE = 'Inactive';
    case NEVER_ACTIVE = 'Never Active';
    case NON_US = 'Non-US';
}
