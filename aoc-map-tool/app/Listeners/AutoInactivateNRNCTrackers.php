<?php

namespace App\Listeners;

use App\Events\InactivateNRNCTrackers;
use App\Services\MssqlRecruiterTracker\MssqlRecruiterTrackerService;

class AutoInactivateNRNCTrackers
{
    public function __construct(private MssqlRecruiterTrackerService $service)
    {}

    public function handle(InactivateNRNCTrackers $event): void
    {
        $newTrackerId = $event->trackerId;
        $providerId = $event->providerId;
        $this->service->inactivateNRNCTrackers($newTrackerId, $providerId);
    }
}
