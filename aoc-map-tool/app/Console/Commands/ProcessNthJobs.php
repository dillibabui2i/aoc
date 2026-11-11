<?php

namespace App\Console\Commands;

use App\SyncProcesses\syncJobViteJobs;
use Illuminate\Console\Command;

class ProcessNthJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:nthjobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process NTH JobVite jobs';

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
        $sync = new syncJobViteJobs();
        $sync->ProcessOpenJobs();

        return 0;
    }
}
