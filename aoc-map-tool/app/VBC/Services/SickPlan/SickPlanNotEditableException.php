<?php

namespace App\VBC\Services\SickPlan;

use RuntimeException;

/** The tracker owning this Sick Plan is not "In Progress", so its items cannot be created/updated. */
class SickPlanNotEditableException extends RuntimeException
{
}
