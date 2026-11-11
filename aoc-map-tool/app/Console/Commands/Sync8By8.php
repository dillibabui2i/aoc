<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\SyncProcesses\Sync8By8Interation;

class Sync8By8 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:8by8 {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '
        Sync 8 X 8 integration data \n
        calllegs : to sync calllegs data
    ';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $type = $this->argument('type');
        $process = new Sync8By8Interation();
        switch ($type) {
            case 'calllegs':
                $process->callLegs();
                break;
            default:
        }
        return Command::SUCCESS;
    }
}
