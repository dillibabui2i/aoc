<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'first_advantage' => [
            'channel' => 'first_advantage',
            'driver' => 'daily',
            'path' => storage_path('logs/first_advantage/error.log'),
            'level' => 'error',
            'days' => 90,
        ],

        'wepis_error' => [
            'driver' => 'single',
            'path' => storage_path('logs/wepis_error.log'),
            'level' => 'error',
        ],

        'nrrErrors' => [
            'driver' => 'single',
            'path' => storage_path('logs/nrr.log'),
            'level' => 'error',
        ],

        'client' => [
            'driver' => 'single',
            'path' => storage_path('logs/client.log'),
            'level' => 'error',
        ],
        'therapy_candidates' => [
            'driver' => 'daily',
            'path' => storage_path('logs/therapy_candidates/error.log'),
            'level' => 'info',
            'days' => 90,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
        ],
        'sqllog' => [
            'driver' => 'single',
            'path'   => storage_path('logs/sql.log'),
            'level'  => 'debug',
        ],

        'resync_schedulemaster' => [
            'driver' => 'single',
            'path' => storage_path('logs/resync_schedulemaster.log'),
        ],

        'commands' => [
            'driver' => 'single',
            'path'   => storage_path('logs/commands.log'),
            'level'  => 'debug',
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
            'days' => 14,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => 'critical',
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => 'debug',
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => 'debug',
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => 'debug',
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        'map' => [
            'driver' => 'single',
            'path' => storage_path('logs/map.log'),
            'level' => 'error',
        ],

        'daasSupVisit' => [
            'driver' => 'single',
            'path' => storage_path('logs/daas/supVisit.log'),
            'level' => 'debug',
        ],

        'permissions' => [
            'driver' => 'single',
            'path' => storage_path('logs/permissions.log'),
            'level' => 'debug',
        ],

        'featureFlags' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ff/featureFlags.log'),
            'level' => 'debug',
        ],

        'assignCoverageCoordinatorToProvider' => [
            'driver' => 'single',
            'path' => storage_path('logs/assignCoverageCoordinator.log'),
            'level' => 'warning',
        ],
        'clinician' => [
            'driver' => 'daily',
            'path' => storage_path('logs/clinician.log'),
            'level' => 'debug',
        ],
    ],

];
