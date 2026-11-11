<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\H;
use App\Services\CCC\MonthlyCallsBatchGeneratorService;

class ProcessMonthlyFollowupCalls extends Command
{
    protected $signature = 'process:monthlyfollowupcalls';
    protected $description = 'creating CCC client calls (single bucket cadence)';

    protected string $logFile = 'monthlyfollowupcalls.log';

    public function __construct(private MonthlyCallsBatchGeneratorService $generator)
    {
        parent::__construct();
    }

    public function handle()
    {
        H::ExcelLog("ProcessMonthlyFollowupCalls started", $this->logFile);

        $inserted = $this->generator->generateForCurrentCycles(1);

        H::ExcelLog("ProcessMonthlyFollowupCalls finished. Insert attempted: {$inserted}", $this->logFile);

        return 0;
    }
}
