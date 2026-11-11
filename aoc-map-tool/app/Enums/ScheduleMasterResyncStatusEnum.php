<?php

namespace App\Enums;

/**
 * Per-schedule outcome of `php artisan kantime:resync-schedulemaster`.
 */
enum ScheduleMasterResyncStatusEnum: string
{
    case UPDATED = 'UPDATED';
    case WOULD_UPDATE = 'WOULD_UPDATE';
    case UNCHANGED = 'UNCHANGED';
    case DELETED = 'DELETED';
    case WOULD_DELETE = 'WOULD_DELETE';
    case NOT_FOUND = 'NOT_FOUND';
    case NOT_FOUND_LOCAL = 'NOT_FOUND_LOCAL';
    case SKIPPED_DELETED = 'SKIPPED_DELETED';
    case FAILED = 'FAILED';
}
