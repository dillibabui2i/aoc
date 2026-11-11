<?php

namespace App\Console\Commands;

use App\Jobs\PayerRates\PayerRateExpiringNotificationJob;
use Illuminate\Console\Command;

class GeneratePayerRateExpiringNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payerrateexpiringnotification:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate notification for payer rate expiring (1 and 3 days before)';

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
        dispatch(new PayerRateExpiringNotificationJob());

        return 0;
    }
}
