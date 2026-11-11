<?php

namespace App\Console\Commands;

use App\SyncProcesses\GeocodeData;
use Illuminate\Console\Command;

class ProcessTestGeoCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:testgeo {address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Test Geocoding';

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
        $address = $this->argument('address');
        $sync = new GeocodeData();
        $sync->testLocation($address);

        return 0;
    }
}
