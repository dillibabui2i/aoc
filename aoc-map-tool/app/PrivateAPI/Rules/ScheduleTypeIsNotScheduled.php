<?php

namespace App\PrivateAPI\Rules;

use App\Repositories\RecruitingSchedule\RecruitingScheduleReadRepository;
use Illuminate\Contracts\Validation\Rule;

/**
 * Mode 1 ("schedule next step", no scheduleId): a fresh schedule of this type may only be
 * created while the tracker doesn't already have one - otherwise the caller needs to reschedule
 * the existing row via scheduleId instead.
 */
class ScheduleTypeIsNotScheduled implements Rule
{
    public function __construct(
        private readonly RecruitingScheduleReadRepository $schedules,
        private readonly mixed $trackerId,
        private readonly int $typeId,
    ) {
    }

    public function passes($attribute, $value): bool
    {
        return !$this->schedules->existsForTrackerAndType((int) $this->trackerId, $this->typeId);
    }

    public function message(): string
    {
        return 'A schedule already exists for this scheduleType. Rescheduling without scheduleId is not allowed.';
    }
}
