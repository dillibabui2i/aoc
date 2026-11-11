<?php

namespace App\Console\Commands;
use App\SyncProcesses\AfterhoursScheduleReminder;
use Illuminate\Console\Command;

class ProcessAfterHoursNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:afterhoursnotification {send_time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $send_time = $this->argument('send_time');
        $sync = new AfterhoursScheduleReminder();
        $sync->sendAfterhoursScheduleReminders($send_time);
        return 0;
    }
}
