<?php

namespace App\Exceptions;

use Exception;

class MissingArSupervisorException extends Exception
{
    protected $message = 'No AR Supervisor found for this branch. Please contact administrator.';
}
