<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\MssqlRecruitertracker;
use Carbon\Carbon;
use App\Notes;
use Illuminate\Support\Facades\DB;

class SetBonusStatusToPaidOut extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:BonusStatus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set Bonus status to paid out';

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
        $date = date("Y-m-d 12:00:00");
        $records = MssqlRecruitertracker::where('Bonus_Paid_Date','<=',$date)->where('Bonus_Status', 'Submitted to Payroll')->get();
        
        foreach($records as $data){
            $record = MssqlRecruitertracker::where('id',$data['id'])->update(['Bonus_Status' => 'Paid Out']);
            $noteData = [];
            $noteData['entity'] = 'tracker-activity';
            $noteData['entity_id'] = $data['id'];
            $noteData['notes'] = "Bonus Paid Out.";
            $noteData['created_by'] = '1';
            $noteData['created_at'] = Carbon::now();
            $noteData['updated_at'] = Carbon::now();
            $insert = Notes::insert($noteData);
        }

        //Update latest_schedule into mssql_providers table for Nurse Churn report
        DB::statement("update mssql_providers
            set latest_schedule = (select ScheduleDate from mssql_schedulemaster
                where mssql_schedulemaster.ProviderID = mssql_providers.ProviderID
                and ScheduleStatusID in (1,2,3)
                order by ScheduleDate desc limit 1)
            ,has_billable_visit = 1
            WHERE
            (select ScheduleDate from mssql_schedulemaster
            where mssql_schedulemaster.ProviderID = mssql_providers.ProviderID
            and ScheduleStatusID in (1,2,3)
            order by ScheduleDate desc limit 1) is not null");

        return 0;
    }
}
