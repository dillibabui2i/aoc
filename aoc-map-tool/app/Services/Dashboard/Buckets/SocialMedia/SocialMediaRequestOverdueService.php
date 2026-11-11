<?php

namespace App\Services\Dashboard\Buckets\SocialMedia;

use App\Services\BusinessDaysService;
use Illuminate\Support\Carbon;

class SocialMediaRequestOverdueService
{
    private const REQUEST_OVERDUE_BUSINESS_DAYS = 1;
    private const EMPTY_DATETIME = '0000-00-00 00:00:00';

    public function __construct(private BusinessDaysService $businessDaysService)
    {
    }

    public function isOverdue(?string $createdAt, ?array $holidays = null): bool
    {
        if ($createdAt === null || $createdAt === '' || $createdAt === self::EMPTY_DATETIME) {
            return false;
        }

        $holidays = $holidays ?? $this->businessDaysService->getHolidaySchedule();

        return Carbon::now()->greaterThan(
            $this->businessDaysService->addBusinessDays($createdAt, self::REQUEST_OVERDUE_BUSINESS_DAYS, $holidays)
        );
    }
}
