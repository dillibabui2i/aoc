<?php

namespace App\Console\Commands;
use App\SyncProcesses\AfterHoursUpcomingSchedule;
use Illuminate\Console\Command;

class ProcessAfterHoursUpcomingShifts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:afterhoursupcomingshifts';

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
        $sync = new AfterHoursUpcomingSchedule();
        $sync->setAfterHoursUpcomingSchedules();
        return 0;
    }
}
