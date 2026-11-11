<?php

namespace App\Console\Commands;
use App\SyncProcesses\NotificationSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;

class ProcessWeeklyNTHFollowupCalls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:weeklynthfollowupcalls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'creating weeklynth followup calls';
   
    protected $logFile = 'weeklynthfollowupcalls.log';

    protected $frequency = null;

    protected $currentDate = null;

    protected $currentWeek = null;

    protected $currentWeekEnd = null;

    protected $defaultFrequency = 2;

    protected $followupType = "nth";

    
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->currentDate = date('Y-m-d');
        $this->currentWeek = date('Y-m-d',$this->last_monday($this->currentDate));
        $this->currentWeekEnd = date('Y-m-d', strtotime($this->currentWeek . ' + 4 days'));;
        $this->frequency = config('constant.nthCallFrequency');
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        echo "\n Preparing Data to process : \n";
        H::ExcelLog("Preparing Data to process",$this->logFile);

        echo 'today: ' . $this->currentDate . "\n";
        echo 'current week: ' . $this->currentWeek . "\n";
        echo 'current week end: ' . $this->currentWeekEnd . "\n";
        echo 'day of week: ' . date('w',strtotime($this->currentDate)) . "\n";
        if(date('w',strtotime($this->currentDate)) == 0 || date('w',strtotime($this->currentDate)) == 6){
            echo 'exit because it is weekend' . "\n";
            exit;
        }

        $currentWeek = $this->currentWeek;
        $weeklyVisit = DB::table("NeedToHire_Accounts")
        ->select('NeedToHire_Accounts.*','mssql_patients.ccc_id','mssql_patients.ccc_nth_call_frequency')

        ->leftJoin("mssql_patients",function($query){
            $query->on("mssql_patients.id","=","NeedToHire_Accounts.aoc_patient_id");
        })->leftJoin("users",function($query){
            $query->on("users.id","=","mssql_patients.ccc_id");
        })->leftJoin("mssql_facilities",function($query){
            $query->on("mssql_patients.aoc_facility_id","=","mssql_facilities.id");
        })
        ->where("NeedToHire_Accounts.isActive",'1')
        ->where("NeedToHire_Accounts.created_at","<",$currentWeek.' 00:00:00')
        ->whereIn("mssql_patients.Patient_CurrentStatusID",[1,6,7,8])
        ->whereRaw("(mssql_facilities.State <> 'CO' OR mssql_patients.LOBID = 1023)")->get();
        

        $followupCalls = [];


        foreach($weeklyVisit as $visit) {
            echo "\n  Processing NTH :" . htmlspecialchars($visit->id, ENT_QUOTES, 'UTF-8') . " \n";
            H::ExcelLog("Processing NTH :".$visit->id,$this->logFile);
            
            $lastFollowup = $this->getLastCallDetails($visit->id);
            $lastDueDate = '';
            if(!empty($lastFollowup->due_date)) $lastDueDate = $lastFollowup->due_date;
            if($this->currentDate > $lastDueDate){
                //only process if the last call is past due 
                list($startOftheWeek, $nextDueDate) = $this->calculateNextDueDate($lastFollowup,$visit);
                echo 'nextDueDate: ' . $nextDueDate . "\n";
                echo 'current week: ' . $this->currentWeek . "\n";
                echo 'current week end: ' . $this->currentWeekEnd . "\n";
                echo 'last followup due date: ' . htmlspecialchars($lastDueDate, ENT_QUOTES, 'UTF-8') . "\n";
                if(strtotime($nextDueDate) >= strtotime($this->currentWeek) && strtotime($nextDueDate) <= strtotime($this->currentWeekEnd)) {
                    echo "\n  Insert new record : \n";
                    H::ExcelLog("  Insert new record:",$this->logFile);

                    $followupCalls[] = [
                        'aoc_patient_id'=>$visit->aoc_patient_id,
                        'aoc_nthaccount_id'=>$visit->id,
                        'start_date'=>$startOftheWeek,
                        'ccc_id'=>$visit->ccc_id,
                        'created_at'=>date("Y-m-d H:i:s"),
                        'created_by'=>1,
                        'updated_at'=>date("Y-m-d H:i:s"),
                        'updated_by'=>1,
                        'due_date' => $nextDueDate,
                        'type' => $this->followupType,
                        'IsPriority'=>$visit->IsPriority,
                        'week_month_start' => $startOftheWeek
                    ];
                } else {
                    echo "\n  Skipping Records : \n";
                    H::ExcelLog("  Skipping Record:",$this->logFile);
                }
            }else{
                echo 'Skip record because the latest call record is not past due yet' . "\n";
            }
        }

        if(!empty($followupCalls)) {
            echo "\n Processing Insertion of " . count($followupCalls) . ' records' . "\n";
            H::ExcelLog("Processing Insertion of " . count($followupCalls) . ' records',$this->logFile);
            $insertChunks = array_chunk($followupCalls, 500);
            foreach($insertChunks as $chunk) {
                DB::table("ccc_followup_calls")->insert($chunk);
                echo "\n Inserting.... \n";
                H::ExcelLog("Inserting....",$this->logFile);
                sleep(1); 
            }
        }else{
            echo "\n There are no records to insert" . "\n";
            H::ExcelLog("There are no records to insert",$this->logFile);
        }

        $this->updateExistingPastDueFollowups();
        return 0;
    }

    private function getNTHFrequency($dbFrequency){
        if($dbFrequency){
            echo "\n User Frequency Set in Table: " . htmlspecialchars($dbFrequency, ENT_QUOTES, 'UTF-8') . " \n";
            H::ExcelLog(" User  Frequency Set in Table:".$dbFrequency,$this->logFile);
            return $dbFrequency;
        } else {
            echo "\n User  Frequency not Set in Table, pick the default frequency :".$this->defaultFrequency." \n";
            H::ExcelLog(" User  Frequency not Set in Table  pick the default frequency:".$this->defaultFrequency,$this->logFile);
            return $this->defaultFrequency;
        }
    }

    private function updateExistingPastDueFollowups(){
            $data = ["outcome"=>"Not Complete",'updated_at'=>date("Y-m-d H:i:s"),
            'updated_by'=>1];
            DB::table("ccc_followup_calls")
                ->whereRaw("ifnull(outcome,'') = '' and due_date < '" . $this->currentDate . "' and type = 'nth'")
                ->update($data);
    }

    private function calculateNextDueDate($lastFollowup,$visit) {
        $userFrequency = $this->getNTHFrequency($visit->ccc_nth_call_frequency);
        $baseDate = $this->currentWeek;

        echo "\n  User Frequency :".$userFrequency." \n";
        H::ExcelLog(" User Frequency  :".$userFrequency,$this->logFile);

        $daysAdded = [1=>1,2=>7,3=>14,4=>21,5=>28];
        if($userFrequency == 1) {
            echo 'userFrequency = 1' . "\n";
            $nextDueDate = $this->currentDate;
            $startoftheWeek = H::getMondayFromDate($nextDueDate);
            echo 'final nextduedate: ' . $nextDueDate . "\n";
        }else{
            if($lastFollowup) {
                echo "set first" . "\n";
                $dueWeekDate = date("Y-m-d", strtotime($lastFollowup->due_date. " + ".$daysAdded[$userFrequency]." days"));
            } else {
                echo "set second" . "\n";
                $dueWeekDate = date("Y-m-d", strtotime($visit->created_at. " + ".$daysAdded[$userFrequency]." days"));
            }
            if (strtotime($dueWeekDate) < strtotime($this->currentWeek)) {
                echo 'set third' . "\n";
                $dueWeekDate = $this->currentWeek;
            }
            echo 'dueWeekDate: ' . $dueWeekDate . "\n";
            $startoftheWeek = H::getMondayFromDate($dueWeekDate);
            $nextDueDate = date("Y-m-d", strtotime($startoftheWeek. " +4 days"));
            echo 'nextDueDate: ' . $nextDueDate . "\n";
        }
        
        echo "\n  Start Of the week :".$startoftheWeek." \n";
        H::ExcelLog(" Start Of the week:".$startoftheWeek,$this->logFile);
        echo "\n  Next Due Date :".$nextDueDate." \n";
        H::ExcelLog(" Next Due Date :".$nextDueDate,$this->logFile);
        return [$startoftheWeek,$nextDueDate];
    }

    private function getLastCallDetails($nthAccountId){
        $lastCall = DB::table("ccc_followup_calls")
        ->select("id","aoc_nthaccount_id","start_date","due_date","outcome","created_at")
        ->where("aoc_nthaccount_id",$nthAccountId)
        ->orderBy("created_at","DESC")->first();

        H::ExcelLog(" Last Call Details :".print_r($lastCall,true),$this->logFile);

        return $lastCall;
    }

    private function last_monday($date) {
        if (!is_numeric($date))
            $date = strtotime($date);
        if (date('w', $date) == 1)
            return $date;
        else
            return strtotime(
                'last monday',
                 $date
            );
    }
}
