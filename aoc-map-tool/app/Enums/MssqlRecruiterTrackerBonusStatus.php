<?php

namespace App\Enums;

/**
 * Values of `mssql_recruitertracker.Bonus_Status`. Mirrors config('constant.bonusStatus'),
 * which drives the UI dropdowns (Show All filter, tracker details inline-edit).
 */
enum MssqlRecruiterTrackerBonusStatus: string
{
    case PENDING_HOURS = 'Pending Hours';
    case PAID_OUT = 'Paid Out';
    case SUBMITTED_TO_PAYROLL = 'Submitted To Payroll';
    case BONUS_INELIGIBLE = 'Bonus Ineligible';
    case INACTIVE = 'Inactive';
}
