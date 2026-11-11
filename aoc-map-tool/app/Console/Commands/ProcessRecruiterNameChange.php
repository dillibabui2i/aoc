<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
class ProcessRecruiterNameChange extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:recruitername';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recruiter Name Change';
    protected $logFile = 'process_recruitername.log';

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
        $start_time = microtime(true);
        echo "Processing\n";
		
        //maps_nth
        $nthIds = DB::table("maps_nth")->where("Recruiter","Tera Glass")->update(["Recruiter"=>"Tera Thomas"]);
        
        //need_account
        $needtoHire = DB::table("NeedToHire_Accounts")->where("Recruiter","Tera Glass")->update(["Recruiter"=>"Tera Thomas"]);

         //recruiter
         $recruiter = DB::table("mssql_recruitertracker")->where("Recruiter","Tera Glass")->update(["Recruiter"=>"Tera Thomas"]);

        // End time
        $end_time = microtime(true);

        // Calculate execution time
        $execution_time = $end_time - $start_time;

        echo "Execution Time: " . number_format($execution_time, 6) . " seconds";
            
       return 0;
    }

    
}
