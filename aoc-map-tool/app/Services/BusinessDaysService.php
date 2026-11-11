<?php

namespace App\Services;

use App\Helpers\H;
use Illuminate\Support\Carbon;

class BusinessDaysService
{
    public function addBusinessDays(string $date, int $days = 1, ?array $holidays = null): Carbon
    {
        $holidays = $holidays ?? $this->getHolidaySchedule();
        $result = Carbon::parse($date);

        while ($days > 0) {
            $result->addDay();

            if (!$result->isWeekend() && !in_array($result->format('Y-m-d'), $holidays, true)) {
                $days--;
            }
        }

        return $result;
    }

    public function getHolidaySchedule(): array
    {
        $schedule = H::getCoreSettings('holiday_schedule');

        if (!is_string($schedule) || $schedule === '') {
            return [];
        }

        return array_map('trim', explode(',', $schedule));
    }
}
