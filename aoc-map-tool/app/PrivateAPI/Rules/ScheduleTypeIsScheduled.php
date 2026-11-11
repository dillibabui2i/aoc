<?php

namespace App\PrivateAPI\Rules;

use App\Repositories\RecruitingSchedule\RecruitingScheduleReadRepository;
use Illuminate\Contracts\Validation\Rule;

/**
 * Mode 3 (submit an outcome): the tracker must already have a schedule row of this type.
 */
class ScheduleTypeIsScheduled implements Rule
{
    public function __construct(
        private readonly RecruitingScheduleReadRepository $schedules,
        private readonly mixed $trackerId,
        private readonly int $typeId,
    ) {
    }

    public function passes($attribute, $value): bool
    {
        return $this->schedules->existsForTrackerAndType((int) $this->trackerId, $this->typeId);
    }

    public function message(): string
    {
        return 'No schedule exists yet for this scheduleType.';
    }
}
