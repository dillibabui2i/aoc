<?php

namespace App\SyncProcesses\Exceptions;

class KantimeSyncException extends \Exception
{
    protected $message = 'Kantime Sync Exception: ';

    public function __construct($message, $code = 0, \Exception $previous = null)
    {
        parent::__construct($this->message . $message, $code, $previous);
    }
}
