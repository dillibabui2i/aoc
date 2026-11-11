<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class removeGPCalculator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:gpcalcrecords';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove GP calculator records everyday at 5PM CST';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
		DB::statement("truncate table gp_calculator");
		echo "All GP calculator records have been removed successfully";
        return Command::SUCCESS;
    }
}
