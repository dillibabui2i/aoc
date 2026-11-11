<?php

namespace App\Console\Commands;

use App\SyncProcesses\syncJobViteCandidates;
use Illuminate\Console\Command;

class SyncCandidates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:candidates {eid?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Candidates';

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
        $process = new syncJobViteCandidates();
        if ($eid != '') {
            $process->getCandidate($eid);
        } else {
            $process->syncCandidates();
        }

        return 0;
    }
}
