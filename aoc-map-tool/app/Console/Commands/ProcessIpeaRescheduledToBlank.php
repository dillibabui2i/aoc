<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\SyncProcesses\MssqlRecruiterTracker;
class ProcessIpeaRescheduledToBlank extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:ipearescheduledtoblank';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update IPEA record live_ipea_status to blank when it rescheduled and the appointment date is today or greater then today';

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
        $sync->updateIpeaRescheduledToBlank();
        return 0;
    }
}
