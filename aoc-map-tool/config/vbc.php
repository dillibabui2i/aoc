<?php

return [
    'is_available' => env('APP_ENV', false) !== 'production',
    'date_format' => 'm/d/Y',
    'date_time_format' => 'm/d/Y h:i A',
    'activity_log_date_time_format' => 'M jS, Y \a\t h:i A',  // e.g. "Mar 3rd, 2026 at 04:00 PM".
];
