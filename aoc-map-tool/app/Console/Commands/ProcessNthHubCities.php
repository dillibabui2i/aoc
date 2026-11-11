<?php

namespace App\Console\Commands;

use App\SyncProcesses\MssqlNeedToHireChecklist;
use Illuminate\Console\Command;

class ProcessNthHubCities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:nthrequisitions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process NTH JobVite Requisitions';

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
        $sync = new MssqlNeedToHireChecklist();
        $sync->ProcessNthRequisitions();

        return 0;
    }
}
