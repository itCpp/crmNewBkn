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

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 60,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        'second_calls' => [
            'driver' => 'daily',
            'path' => storage_path('logs/calls/second_calls.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 60,
        ],

        'check_queues' => [
            'driver' => 'daily',
            'path' => storage_path('logs/requests/check_queue_site.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 60,
        ],

        'comings' => [
            'driver' => 'daily',
            'path' => storage_path('logs/requests/comings.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 60,
        ],

        'cron_sms' => [
            'driver' => 'daily',
            'path' => storage_path('logs/cron/sms/sms.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 60,
        ],

        'cron_users' => [
            'driver' => 'daily',
            'path' => storage_path('logs/cron/users/stop_session.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 60,
        ],

        'cron_events_recrypt' => [
            'driver' => 'daily',
            'path' => storage_path('logs/cron/events/recrypt.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 60,
        ],

        'webhoock_access' => [
            'driver' => 'daily',
            'path' => storage_path('logs/webhoock/request.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 60,
        ],

        'webhoock_response' => [
            'driver' => 'daily',
            'path' => storage_path('logs/webhoock/response.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 60,
        ],
    ],

];
