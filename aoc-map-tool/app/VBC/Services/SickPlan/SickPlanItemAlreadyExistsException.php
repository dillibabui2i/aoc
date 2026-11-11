<?php

namespace App\VBC\Services\SickPlan;

use RuntimeException;

/** The client's Sick Plan already has an item for the requested Sick Plan Type (unique per plan). */
class SickPlanItemAlreadyExistsException extends RuntimeException
{
}
