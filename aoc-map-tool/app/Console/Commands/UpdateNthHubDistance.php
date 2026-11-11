<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateNthHubDistance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:updateNthHubDistance {start} {limit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update MilesFromHub field in NeedToHire Hub Table';

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
        $start = $this->argument('start');
        $limit = $this->argument('limit');
        $geocoding = new \App\Helpers\Geocoding;
        $geocoding->getNthHub($start,$limit);
        return 0;
    }
}
