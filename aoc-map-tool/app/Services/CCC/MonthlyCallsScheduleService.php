<?php

namespace App\Services\CCC;

use App\Enums\MonthlyCallsEnum;
use Carbon\Carbon;

class MonthlyCallsScheduleService
{
    public function now(): Carbon
    {
        return Carbon::now();
    }

    public function today(): Carbon
    {
        return $this->now()->startOfDay();
    }

    public function currentWeekStart(): Carbon
    {
        return $this->today()->startOfWeek(Carbon::MONDAY);
    }

    public function currentWeekEnd(): Carbon
    {
        return $this->currentWeekStart()->addDays(4); // Friday
    }

    public function currentMonthStart(): Carbon
    {
        return $this->today()->startOfMonth();
    }

    public function currentMonthEnd(): Carbon
    {
        return $this->today()->endOfMonth();
    }

    public function toMonthStart(Carbon $date): Carbon
    {
        return $date->startOfMonth();
    }

    public function monthIndex(Carbon $monthStart): int
    {
        return ($monthStart->year * 12) + $monthStart->month;
    }

    public function weekIndex(Carbon $weekStartMonday): int
    {
        $isoYear = (int)$weekStartMonday->isoWeekYear;
        $isoWeek = (int)$weekStartMonday->isoWeek;

        return $isoYear * 100 + $isoWeek;
    }

    public function shouldGenerateForCurrentCycleWithHistory(
        MonthlyCallsEnum $freq,
        Carbon $currentWeekStart,
        Carbon $currentMonthStart,
        ?Carbon $anchorCycleStart
    ): bool {
        if ($freq->isInactive()) {
            return false;
        }

        if ($anchorCycleStart === null) {
            return true;
        };

        return $this->shouldGenerateForCurrentCycle(
            $freq,
            $currentWeekStart,
            $currentMonthStart,
            $anchorCycleStart
        );
    }

    public function shouldGenerateForCurrentCycle(
        MonthlyCallsEnum $freq,
        Carbon $currentWeekStart,
        Carbon $currentMonthStart,
        Carbon $anchorCycleStart
    ): bool {
        if ($freq->isInactive()) {
            return false;
        }

        if ($freq->isWeeklyCadence() || $freq->isBiWeeklyCadence()) {
            $anchorWeekStart = $anchorCycleStart->startOfWeek(Carbon::MONDAY);

            return $this->shouldGenerateForWeek($freq, $currentWeekStart, $anchorWeekStart);
        }

        if ($freq->isMonthlyCadence() || $freq->isEveryOtherMonthCadence() || $freq->isQuarterlyCadence()) {
            $anchorMonthStart = $anchorCycleStart->startOfMonth();

            return $this->shouldGenerateForMonth($freq, $currentMonthStart, $anchorMonthStart);
        }

        return false;
    }

    public function shouldGenerateForWeek(
        MonthlyCallsEnum $freq,
        Carbon $currentWeekStart,
        Carbon $anchorWeekStart
    ): bool {
        if ($freq === MonthlyCallsEnum::WEEKLY) {
            return true;
        }

        $diff = $this->weekIndex($currentWeekStart) - $this->weekIndex($anchorWeekStart);
        if ($diff < 0) {
            $diff = 0;
        }

        return ($diff % 2) === 0;
    }

    public function shouldGenerateForMonth(
        MonthlyCallsEnum $freq,
        Carbon $currentMonthStart,
        Carbon $anchorMonthStart
    ): bool {
        if ($freq === MonthlyCallsEnum::MONTHLY) {
            return true;
        }

        $diff = $this->monthIndex($currentMonthStart) - $this->monthIndex($anchorMonthStart);

        if ($diff < 0) {
            $diff = 0;
        }

        return match ($freq) {
            MonthlyCallsEnum::EVERY_OTHER_MONTH => ($diff % 2) === 0,
            MonthlyCallsEnum::QUARTERLY => ($diff % 3) === 0,
            default => false,
        };
    }

    public function currentCycleStart(
        MonthlyCallsEnum $freq,
        Carbon $currentWeekStart,
        Carbon $currentMonthStart
    ): Carbon {
        return ($freq->isWeeklyCadence() || $freq->isBiWeeklyCadence()) ? $currentWeekStart : $currentMonthStart;
    }

    public function currentDueDate(
        MonthlyCallsEnum $freq,
        Carbon $currentWeekEnd,
        Carbon $currentMonthEnd
    ): Carbon {
        if ($freq->isWeeklyCadence()) {
            return $currentWeekEnd;
        }

        if ($freq->isBiWeeklyCadence()) {
            return $currentWeekEnd->addWeek();
        }

        if ($freq->isMonthlyCadence()) {
            return $currentMonthEnd;
        }

        if ($freq->isEveryOtherMonthCadence()) {
            return $currentMonthEnd->addMonthNoOverflow();
        }

        // Quarterly cadence
        return $currentMonthEnd->addMonthsNoOverflow(2);
    }
}
