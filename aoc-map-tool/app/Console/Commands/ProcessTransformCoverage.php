<?php

namespace App\Console\Commands;

use App\SyncProcesses\TransformCoverageNeeds;
use Illuminate\Console\Command;

class ProcessTransformCoverage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:coverage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Transform Coverage Needs';

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
        $sync = new TransformCoverageNeeds();
        $sync->TransformCoverageNeeds();

        return 0;
    }
}
