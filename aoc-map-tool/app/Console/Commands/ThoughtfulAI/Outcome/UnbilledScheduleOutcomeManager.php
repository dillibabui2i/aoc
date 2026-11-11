<?php

namespace App\Console\Commands\ThoughtfulAI\Outcome;

use App\Services\UnbilledScheduleOutcome\UnbilledScheduleOutcomeService;
use Illuminate\Console\Command;

class UnbilledScheduleOutcomeManager extends Command
{
    private const MSG = 'Unbilled schedules processed successfully.';

    protected $signature = 'unbilled-schedules:process-ai-notes';
    protected $description = 'Processes unbilled schedules and applies outcome notes based on Thoughtful AI notes from Kantime.';

    public function handle(UnbilledScheduleOutcomeService $service): void
    {
        $service->process();
        $this->info(self::MSG);
    }
}
