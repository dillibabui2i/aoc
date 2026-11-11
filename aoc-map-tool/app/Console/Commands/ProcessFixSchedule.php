<?php

namespace App\Console\Commands;

use App\SyncProcesses\MssqlRecruiterTracker;
use Illuminate\Console\Command;

class ProcessFixSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:fixschedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Fix Schedules';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sync = new MssqlRecruiterTracker();
        $sync->FixRecruitingSchedules();

        return 0;
    }
}
