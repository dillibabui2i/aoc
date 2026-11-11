<?php

namespace App\Console\Commands;

use App\SyncProcesses\GeocodeData;
use Illuminate\Console\Command;

class ProcessCheckGeo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:checkgeo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Check Geocoding';

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
        $sync->getGeocodingStatus();

        return 0;
    }
}
