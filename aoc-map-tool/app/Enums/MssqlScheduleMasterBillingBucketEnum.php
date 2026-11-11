<?php

namespace App\Enums;

/**
 * mssql_schedulemaster.billing_bucket_type, set by `php artisan set:billingbucketgroup`.
 */
enum MssqlScheduleMasterBillingBucketEnum: int
{
    case NOT_CLASSIFIED = 0;
    case BILL_READY = 1;
    case NOT_BILL_READY = 2;
}
