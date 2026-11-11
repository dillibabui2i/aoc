<?php

namespace App\PrivateAPI\Rules;

use App\RecruitingSchedule;
use Illuminate\Contracts\Validation\Rule;

/**
 * Operates on an already-resolved schedule row (the caller looked it up by scheduleId) - checks
 * the row's own type_id matches the scheduleType submitted for the reschedule.
 */
class ScheduleMatchesType implements Rule
{
    public function __construct(
        private readonly ?RecruitingSchedule $schedule,
        private readonly int $typeId,
    ) {
    }

    public function passes($attribute, $value): bool
    {
        return $this->schedule !== null && (int) $this->schedule->type_id === $this->typeId;
    }

    public function message(): string
    {
        return 'scheduleType is not scheduled.';
    }
}
