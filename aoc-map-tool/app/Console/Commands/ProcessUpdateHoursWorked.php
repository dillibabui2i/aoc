<?php

namespace App\Console\Commands;

use App\Helpers\RecruiterTrackerHelper;
use Illuminate\Console\Command;

class ProcessUpdateHoursWorked extends Command
{
    protected $signature = 'process:hoursworked';
    protected $description = 'Update Hours Worked From ScheduleMaster';

    public function __construct(private RecruiterTrackerHelper $trackerHelper)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->trackerHelper->markCrossRecruiterDuplicatesIneligible();
        $this->trackerHelper->updateHoursWorkedToTrackers();
        $this->trackerHelper->completeTrackersReachedMinHours();

        return self::SUCCESS;
    }
}
