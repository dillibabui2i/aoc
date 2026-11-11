<?php

return [
    'groups_url' => env(
        'CDC_VACCINE_GROUPS_URL',
        'https://www2.cdc.gov/vaccines/iis/iisstandards/XML.asp?rpt=vax2vg'
    ),
    'vaccines_url' => env(
        'CDC_VACCINE_VACCINES_URL',
        'https://www2a.cdc.gov/vaccines/iis/iisstandards/XML2.asp?rpt=cvx'
    ),
    'http_timeout' => (int) env('CDC_VACCINE_HTTP_TIMEOUT', 60),
    'http_retries' => (int) env('CDC_VACCINE_HTTP_RETRIES', 3),

    // Immunization Team mailbox for the "group needs an AOC name" notification. Empty = no email is sent.
    'notification_recipient' => env('CDC_VACCINE_NOTIFY_EMAIL'),

    'schedule_enabled' => (bool) env('CDC_VACCINE_SCHEDULE_ENABLED', true),
    'schedule_cron' => env('CDC_VACCINE_SCHEDULE_CRON', '0 3 1 * *'),
];
