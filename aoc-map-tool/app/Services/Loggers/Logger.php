<?php

namespace App\Services\Loggers;

abstract class Logger
{
    protected const N_PLUS_ONE_THRESHOLD = 5;
    protected const N_PLUS_ONE_MSG = 'Possible N+1 detected';
    protected const SQL_EXECUTED_MSG = 'SQL executed';
    protected const SQL_LOG_DRIVER = 'sqllog';
    protected const COMMANDS_LOG_DRIVER = 'commands';
    protected const COMMANDS_FINISHED_MSG = 'Command finished';
}
