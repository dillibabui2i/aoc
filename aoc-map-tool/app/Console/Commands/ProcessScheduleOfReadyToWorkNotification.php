<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;

class ProcessScheduleOfReadyToWorkNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:ProcessScheduleOfReadyToWorkNotification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notification to recruiter,
    Recruiting Admin and Recruiting Team Lead if Candidate is scheduled
    but "ready to work" is not Marked within 1 business day of schedule date.';

    protected $logFileName;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->logFileName = 'ProcessScheduleOfReadyToWorkNotification.log';
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $recruitingSchedules = DB::table('recruiting_schedule')
        ->leftJoin('mssql_recruitertracker as rt', 'tracker_id', '=', 'rt.id')
        ->where(function ($q) {
            $q->where('IPEA_onboarding_status','!=','Ready To Work')->orWhereNull('IPEA_onboarding_status');
        })
        ->where(DB::raw('DATEDIFF(rt.Candidate_Start_Date, NOW())'), '<', 5)
        ->whereRaw('rt.Candidate_Start_Date > NOW()')
        ->where('recruiting_schedule.is_deleted',0)
        ->whereIn('type_id',[1])->get();

        foreach ($recruitingSchedules as $value) {
            if(H::getWorkingdays(date('Y-m-d'),$value->Candidate_Start_Date) == 2)
            {
                H::ExcelLog('Send Notification: ' . $value->Candidate_Start_Date . ' - ' .
                    H::getWorkingdays(date('Y-m-d'),$value->Candidate_Start_Date),
                    'processScheduleOfReadyToWorkNotification.log');
                H::getHelper('RecruiterTrackerHelper')->
                    sendNotificationForReadyToWork($value->tracker_id,$value->appointment_date,true);
            }else{
                H::ExcelLog("Don't Send Notification: " . $value->Candidate_Start_Date . ' - ' .
                    H::getWorkingdays(date('Y-m-d'),$value->Candidate_Start_Date),
                    'processScheduleOfReadyToWorkNotification.log');
            }
        }
        return 0;
    }
}
