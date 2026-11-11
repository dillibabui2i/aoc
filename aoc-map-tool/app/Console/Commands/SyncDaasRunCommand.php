<?php

namespace App\Console\Commands;

use App\Kantime\Jobs\AbstractSyncJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class SyncDaasRunCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kantime:sync:run {jobName : The job name key from config/kantime.php}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Dispatch a job from kantime.schedule.jobs configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jobName = $this->argument('jobName');

        // Load the kantime configuration
        $jobs = config('kantime.schedule.jobs');
        $defaults = config('kantime.schedule.defaults');

        if (!$jobs) {
            $this->error('No jobs found in config/kantime.php under kantime.schedule.jobs');

            return Command::FAILURE;
        }

        // Find the job by exact key match
        if (!array_key_exists($jobName, $jobs)) {
            $this->error("Job '{$jobName}' not found in kantime.schedule.jobs configuration");

            return Command::FAILURE;
        }

        $jobConfig = $jobs[$jobName];

        // Verify the job configuration has a 'job' key with FQCN
        if (!isset($jobConfig['job'])) {
            $this->error("Job '{$jobName}' configuration doesn't exist");

            return Command::FAILURE;
        }

        $jobClass = $jobConfig['job'];

        // Verify the class exists
        if (!class_exists($jobClass)) {
            $this->error("Job class '{$jobClass}' does not exist");

            return Command::FAILURE;
        }

        try {
            $job = new $jobClass();

            if (!$job instanceof AbstractSyncJob) {
                throw new \Exception("Job class '{$jobClass}' is not a Kantime Sync Job");
            }

            Bus::dispatchNow($job->onConnection($defaults['connection'] ?? 'database')->onQueue($defaults['queue'] ?? 'daas-sync'));
            $this->info("Job '{$jobName}' ({$jobClass}) dispatched  and queued successfully");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to dispatch Kantime Sync Job: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
