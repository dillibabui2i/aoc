<?php

namespace App\PrivateAPI\Rules;

use App\RecruitingSchedule;
use Illuminate\Contracts\Validation\Rule;

/**
 * Operates on an already-resolved schedule row (the caller looked it up by scheduleId) - a step
 * that already has a final outcome (anything not in the caller's non-final list) can't be
 * rescheduled.
 */
class ScheduleIsReschedulable implements Rule
{
    public function __construct(
        private readonly ?RecruitingSchedule $schedule,
        private readonly array $nonFinalOutcomes,
    ) {
    }

    public function passes($attribute, $value): bool
    {
        return $this->schedule !== null
            && in_array($this->schedule->outcome, array_merge($this->nonFinalOutcomes, ['', null]), true);
    }

    public function message(): string
    {
        return 'This step already has a final outcome and cannot be rescheduled.';
    }
}
