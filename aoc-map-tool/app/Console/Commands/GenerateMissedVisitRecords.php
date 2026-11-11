<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateMissedVisitRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'missedvisit:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set missed misit flag';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $date = new \Carbon\Carbon();
        $today = $date->format('Y-m-d');
        echo $today . "\n";
        $fromDate = $date->subDays(7)->startOfWeek()->format('Y-m-d');
        $toDate = $date->endOfWeek()->format('Y-m-d');
        echo $fromDate . ' - ' . $toDate;
        //die();
        DB::table('mssql_schedulemaster')
        ->where('ScheduleStatusID', 5)
        ->whereBetween('ScheduleDate', [$fromDate, $toDate])
        ->update(['is_missed_visit' => 1]);
        return Command::SUCCESS;
    }
}
