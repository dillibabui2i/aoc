<?php

namespace App\Console\Commands;
use App\SyncProcesses\NotificationSchedule;
use Illuminate\Console\Command;

class ProcessJobviteNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:jobvitenotification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notification to the Primary Recruiter 15 minutes before the scheduled follow up call.';

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
        $sync = new NotificationSchedule();
        $sync->sendJobviteNotification();
        return 0;
    }
}
