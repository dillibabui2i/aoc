<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\SyncProcesses\SendDueCcrEmails;
class SendDueCcrEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:dueccr';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $process = new SendDueCcrEmails();
        $process->processSendDueCcrEmails();
        return 0;
    }
}
