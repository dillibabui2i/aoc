<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Renewables;

class SendAutomatedReminderEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:automatedrenewablesreminderemails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Renewables Send automated email reminders and notes';

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
        Renewables::sentRenewablesMail();
        return 0;
    }
}
