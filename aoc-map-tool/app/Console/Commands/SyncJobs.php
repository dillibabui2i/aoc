<?php

namespace App\Console\Commands;

use App\SyncProcesses\syncJobViteJobs;
use Illuminate\Console\Command;

class SyncJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:jobs {eid?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Jobs';

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
        $eid = $this->argument('eid');
        var_dump($eid);
       
        echo "\n";
        $process = new syncJobViteJobs();
        if ($eid != '') {
            $process->getCandidate($eid);
        } else {
           
            $process->syncJobs();
        }

        return 0;
    }
}
