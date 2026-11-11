<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\SyncProcesses\CleanTimeData;
class ProcessCleanTimeData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:cleantimedata';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update wrong time format with blank';

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
        $sync = new CleanTimeData();
        $sync->cleanTimeData();

        return 0;
    }
}
