<?php

namespace App\Console\Commands;

use App\SyncProcesses\syncPaycom as paycomeProcess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;



class SyncPaycom extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:paycom';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Paycom';

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
        
       

        //exit;
        echo "\n";
        $process = new paycomeProcess();
        $process->processImport();
        return 0;
    }
}
