<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;

class UpdateClientPaymentSources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:clientpaymentsources {clientId?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update client payment sources based based on new date (since they expire)';

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
        $clientId = $this->argument('clientId')??'';
        H::getHelper("Sync")->updateClientPaymentSources($clientId);
        return 0;
    }
}
