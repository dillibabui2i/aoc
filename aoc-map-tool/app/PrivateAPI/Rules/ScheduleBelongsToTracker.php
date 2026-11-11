<?php

namespace App\PrivateAPI\Rules;

use App\RecruitingSchedule;
use Illuminate\Contracts\Validation\Rule;

/**
 * Operates on an already-resolved schedule row (the caller looked it up by scheduleId) - no
 * repository access here, just the ownership check.
 */
class ScheduleBelongsToTracker implements Rule
{
    public function __construct(
        private readonly ?RecruitingSchedule $schedule,
        private readonly mixed $trackerId,
    ) {
    }

    public function passes($attribute, $value): bool
    {
        return $this->schedule !== null && (int) $this->schedule->tracker_id === (int) $this->trackerId;
    }

    public function message(): string
    {
        return 'The schedule does not belong to this tracker.';
    }
}
