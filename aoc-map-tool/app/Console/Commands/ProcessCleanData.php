<?php

namespace App\Console\Commands;

use App\SyncProcesses\GeocodeData;
use Illuminate\Console\Command;

class ProcessCleanData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:cleandata';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete duplicate records from the specified file';

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
        $sync = new GeocodeData();
        $sync->cleanData();

        return 0;
    }
}
