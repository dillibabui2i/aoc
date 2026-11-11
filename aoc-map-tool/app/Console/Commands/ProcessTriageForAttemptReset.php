<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Triage;
use DB;

class ProcessTriageForAttemptReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:process_triage_for_attempt_reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resetting the Attempt count when the record shift from one to next bucket';

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
        //Checking for records which are moved from the one to next bucket
        $arr = [
            '48H'=>['prev'=>'-2 day,-3 day','cur'=>'-2 day,-7 day'],
            '7D'=>['prev'=>'-3 day,-8 day','cur'=>'-7 day,-30 day'],
            '30D'=>['prev'=>'-8 day,-31 day','cur'=>'-30 day,-31 day']
        ];
        $updateTriageIds = [];
        foreach ($arr as $key => $value) {
            $exPrev = explode(',',$value['prev']);
            $start = $exPrev[0];
            $end = $exPrev[1];
            $triagePrevData = Triage::Select("triageCalls.*");
            $twentyFourHrToDate =  date(config('constant')['longdatetimeformat'],strtotime($start));
            $twentyFourHrFromDate = date(config('constant')['longdatetimeformat'],strtotime($end));
            $triagePrevData->whereRaw('CONCAT(triageCalls.callDate," ",triageCalls.callStartTime) < "'. $twentyFourHrToDate.'" and CONCAT(triageCalls.callDate," ",triageCalls.callStartTime) >= "'. $twentyFourHrFromDate.'"');
            $triagePrevData = $triagePrevData->get()->toArray();

            $exCur = explode(',',$value['cur']);
            $start = $exCur[0];
            $end = $exCur[1];
            $triageData = Triage::Select("triageCalls.*");
            $twentyFourHrToDate =  date(config('constant')['longdatetimeformat'],strtotime($start));
            $twentyFourHrFromDate = date(config('constant')['longdatetimeformat'],strtotime($end));
            $triageData->whereRaw('CONCAT(triageCalls.callDate," ",triageCalls.callStartTime) < "'. $twentyFourHrToDate.'" and CONCAT(triageCalls.callDate," ",triageCalls.callStartTime) >= "'. $twentyFourHrFromDate.'"');
            $triageData = $triageData->get()->toArray();
            $triagePrevData = array_column($triagePrevData,'id');
            $triageData = array_column($triageData,'id');
            $updateTriageIds = array_merge($updateTriageIds,@array_intersect($triagePrevData,$triageData));
        }
        DB::table('triageCalls')->whereIn('id',$updateTriageIds)->update(['outcome_attempt'=>0]);
        return 0;
    }
}
