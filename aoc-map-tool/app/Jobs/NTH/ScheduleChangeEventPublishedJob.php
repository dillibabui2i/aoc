<?php

namespace App\Jobs\NTH;

use App\Events\NTH\NTHScheduleChanged;
use App\Jobs\UniqueJob;
use App\Services\Notification\NTH\NTHScheduleChangeNotificationService;
use Illuminate\Support\Collection;

class ScheduleChangeEventPublishedJob extends UniqueJob
{
    public int $uniqueFor = 120;
    public NTHScheduleChanged $event;
    public Collection $careTeam;
    public int $profileId;

    public function __construct(
        NTHScheduleChanged $event,
        Collection $careTeam,
        int $profileId
    ) {
        $this->event = $event;
        $this->careTeam = $careTeam;
        $this->profileId = $profileId;
    }

    public function handle(NTHScheduleChangeNotificationService $scheduleChangeNotificationService): void
    {
        $scheduleChangeNotificationService->process($this->event, $this->careTeam);
    }

    public function uniqueId(): string
    {
        return sprintf(
            'nthScheduleChange:%s:%s',
            $this->profileId,
            $this->event->getAction()
        );
    }
}

