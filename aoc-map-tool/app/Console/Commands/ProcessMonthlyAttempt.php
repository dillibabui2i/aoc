<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessMonthlyAttempt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:monthlyoutcome';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Monthly Attempts / Outcome Client Care';

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
        $this->takeMonthlyDataSnapshot($chunkSize);
		DB::table("mssql_patients")->orderBy('id')->chunk($chunkSize, function($records) {
			foreach($records as $account) {
				DB::table("mssql_patients")->where('mssql_patients.id',"=",$account->id)
				->update(
					[
						"attempt_count"=>0,
						"outcome"=>'',
						"monthly_follow_up_attempts"=>NULL,
						"result"=>'',
					]);

				//echo ($account->id)."here";exit;
			}
		});
       return 0;
    }

    private function takeMonthlyDataSnapshot($chunkSize)
    {
        $exceptLineOfBusiness = ['PT', 'OT', 'ST', 'Attendant Care'];
        $bucket = DB::table("mssql_patients");
        $bucket->select('mssql_patients.*');
        $bucket->leftJoin("mssql_facilities",function($query){
            $query->on("mssql_patients.aoc_facility_id","=","mssql_facilities.id");
        });
        $bucket->leftJoin("lob",function($query){
            $query->on("lob.LOBID","=","mssql_patients.LOBID");
        });
        $bucket->whereNotIn("lob.LOBName",$exceptLineOfBusiness);
        $bucket->whereNotIn("mssql_patients.aoc_facility_id",['14']);
        $bucket->whereNotIn("mssql_patients.aoc_facility_id",['43','44']);
        $bucket->where("isPRN",0);
        $bucket->whereRaw("mssql_patients.Search_Name not like '%Test%'");
        $bucket->whereRaw("mssql_patients.Search_Name not like '%KanTime%'");
        $bucket->whereRaw("mssql_patients.Search_Name not like '%PRN%'");
        $bucket->whereRaw("(mssql_facilities.State <> 'CO' OR mssql_patients.LOBID = 1023)");
        $fday = date("Y-m-01 00:00:00");
        $bucket->where("mssql_patients.created_at","<",$fday);
        $bucket->where(function($q) use($fday) {
            $q->where('mssql_patients.last_monthly_followup', '<', $fday)
            ->orWhere('mssql_patients.last_monthly_followup', '=', '0000-00-00 00:00:00')
            ->orWhereNull("last_monthly_followup");

        });
        $bucket->whereIn("mssql_patients.Patient_CurrentStatusID",[1,6,7,8]);
        $bucket->where("mssql_patients.IsNonAdmit",'<>',1);
		$bucket->where("mssql_patients.IsLatestAdmit",'=',1);
        $bucket->orderBy('id')->chunk($chunkSize, function($records) {
			foreach($records as $raw) {
                $currentDate = \Carbon\Carbon::now();
                DB::table("ncc_report_monthly_calls")
				->insert(
					[
                        "aoc_patient_id" => $raw->id,
                        "month_start" => $currentDate->startOfMonth()->subMonth(),
                        "ccc_id" => $raw->ccc_id,
                        "last_monthly_followup" => $raw->last_monthly_followup,
						"attempt_count" => $raw->attempt_count,
                        "requested_callback" => $raw->requested_callback,
						"outcome" => $raw->outcome,
						"result" => $raw->result,
                        "monthly_follow_up_attempts" => $raw->monthly_follow_up_attempts,
					]);
			}
		});
    }
}
