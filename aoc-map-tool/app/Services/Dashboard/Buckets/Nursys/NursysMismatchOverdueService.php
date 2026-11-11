<?php

namespace App\Services\Dashboard\Buckets\Nursys;

use App\Services\BusinessDaysService;
use Illuminate\Support\Carbon;

class NursysMismatchOverdueService
{
    private const OVERDUE_BUSINESS_DAYS = 1;

    private ?array $holidays = null;

    public function __construct(private BusinessDaysService $businessDaysService)
    {
    }

    public function isOverdue(?string $createdAt): bool
    {
        if ($createdAt === null) {
            return false;
        }

        return Carbon::now()->greaterThan(
            $this->businessDaysService->addBusinessDays($createdAt, self::OVERDUE_BUSINESS_DAYS, $this->getHolidaySchedule())
        );
    }

    private function getHolidaySchedule(): array
    {
        return $this->holidays ??= $this->businessDaysService->getHolidaySchedule();
    }
}
