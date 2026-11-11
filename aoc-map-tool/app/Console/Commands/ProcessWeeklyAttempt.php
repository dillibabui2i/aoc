<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessWeeklyAttempt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:weeklyoutcome';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Weekly Attempts / Outcome Client Care';

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
        $this->takeWeeklyDataSnapshot($chunkSize);
        DB::table("NeedToHire_Accounts")->orderBy('id')->chunk($chunkSize, function($records) {
			foreach($records as $account) {
				DB::table("NeedToHire_Accounts")->where('NeedToHire_Accounts.id',"=",$account->id)
				->update(
					[
						"attempt_count"=>0,
						"outcome"=>'',
						"weekly_follow_up_attempts"=>NULL,
						"result"=>'',
					]);

				//echo ($account->id)."here";exit;
			}
		});
        
        


       return 0;
    }

    private function takeWeeklyDataSnapshot($chunkSize)
    {
        $bucket = DB::table("NeedToHire_Accounts");
        $bucket->select('NeedToHire_Accounts.*', DB::raw("users.id AS Client_Coordinator"));
        $bucket->leftJoin("mssql_patients",function($query){
            $query->on("mssql_patients.id","=","NeedToHire_Accounts.aoc_patient_id");
        });
        $bucket->leftJoin("users",function($query){
            $query->on("users.id","=","mssql_patients.ccc_id");
        });
        $bucket->leftJoin("mssql_facilities",function($query){
            $query->on("mssql_patients.aoc_facility_id","=","mssql_facilities.id");
        });
        $bucket->where("NeedToHire_Accounts.isActive",'1');

        $fday = date('Y-m-d', strtotime("this week"));
        $bucket->where("NeedToHire_Accounts.created_at","<",$fday.' 00:00:00');
        $bucket->where(function($query) use($fday){
            $query->where('last_weekly_followup', '<', $fday.' 00:00:00')
                ->orWhere('last_weekly_followup', '=', '0000-00-00 00:00:00')
                ->orWhereNull("last_weekly_followup");
        });
        $bucket->whereIn("mssql_patients.Patient_CurrentStatusID",[1,6,7,8]);
        $bucket->whereRaw("(mssql_facilities.State <> 'CO' OR mssql_patients.LOBID = 1023)");

        $bucket->orderBy('id')->chunk($chunkSize, function($records) {
			foreach($records as $raw) {
                $currentDate = \Carbon\Carbon::now();
                DB::table("ncc_report_weekly_calls")
				->insert(
					[
                        "aoc_nthaccount_id" => $raw->id,
                        "week_start" => $currentDate->startOfWeek()->subWeek(),
                        "ccc_id" => $raw->Client_Coordinator,
                        "last_weekly_followup" => $raw->last_weekly_followup,
						"attempt_count" => $raw->attempt_count,
						"outcome" => $raw->outcome,
						"result" => $raw->result,
                        "weekly_follow_up_attempts" => $raw->weekly_follow_up_attempts,
					]);
			}
		});
    }
}
