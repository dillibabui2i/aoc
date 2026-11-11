<?php

namespace App\Repositories\RecruitingSchedule;

use App\RecruitingSchedule;

class RecruitingScheduleReadRepository
{
    public function __construct(private RecruitingSchedule $schedule)
    {
    }

    public function findById(int $scheduleId): ?RecruitingSchedule
    {
        return $this->schedule->query()->find($scheduleId);
    }

    public function existsForTrackerAndType(int $trackerId, int $typeId): bool
    {
        return $this->schedule->query()
            ->where('tracker_id', $trackerId)
            ->where('type_id', $typeId)
            ->exists();
    }
}
