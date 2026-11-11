<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetActiveCoverage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:activecoverage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Monthly Attempts / Outcome Active Coverage';

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
        $chunkSize = 100;

        $activeCoverage = DB::table("mssql_recruitertracker")
        ->select("mssql_recruitertracker.*")
        ->leftJoin('mssql_providers', 'mssql_recruitertracker.aoc_provider_id', '=', 'mssql_providers.id')
        ->leftJoin('mssql_employmentstatus', 'mssql_employmentstatus.EmploymentStatusID', '=', 'mssql_providers.EmploymentStatusID')
        ->where("mssql_employmentstatus.EmploymentStatus","Active Coverage")
        ->where("mssql_recruitertracker.nurse_status","2")
        ->where(function($q) {
            $q->whereNotIn("mssql_recruitertracker.activecoverage_outcome",["Escalated to CCM","Status Change Request"])->orWhereNull("activecoverage_outcome");
        })->get();

        $data = [];
        $data['live_ipea_status'] = 'Ready to work';
        $data['activecoverage_outcome']='';
        $data['activecoverage_attempt']=0;
        
        $trackerIds = [];
        foreach($activeCoverage as $activeRecords) {
           $trackerIds[] = $activeRecords->id;
        }

        DB::table("mssql_recruitertracker")->whereIn("id",$trackerIds)->update($data);

        var_dump($trackerIds);
        

       return 0;
    }

}
