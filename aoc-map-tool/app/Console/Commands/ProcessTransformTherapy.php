<?php

namespace App\Console\Commands;

use App\SyncProcesses\TransformTherapy;
use Illuminate\Console\Command;

class ProcessTransformTherapy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:therapy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Transform Therapy';

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
        $sync = new TransformTherapy();
        $sync->TransformTherapy();

        return 0;
    }
}
