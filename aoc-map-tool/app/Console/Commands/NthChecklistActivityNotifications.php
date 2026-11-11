<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\Helpers\Notifications;
class NthChecklistActivityNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:nthChecklistActivityNotifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'NTH Checklist Activity Notifications to the Recruiter and Recruiting Team Lead';

    protected $notAllowedRecruitingScheduleType = [1,2,3,4,6];
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        H::ExcelLog('--NTH Checklist Activity Notification START--', 'nthChecklistActivityNotifications.log');
        $checklistData = DB::table("NeedToHire_Checklist")
        ->select('NeedToHire_Checklist.id AS nthcheckid',
        'NeedToHire_Accounts.id AS nthaccounts_id',
        'NeedToHire_Accounts.aoc_patient_id',
        'NeedToHire_Accounts.user_id AS recruiter_id',
        "recruiting_schedule.id AS recruiting_schedule_id")
        ->leftJoin("NeedToHire_Accounts",function($query){
            $query->on("NeedToHire_Accounts.id","=","NeedToHire_Checklist.aoc_nthaccounts_id");
        })
        ->leftJoin("mssql_patients",function($query){
            $query->on("mssql_patients.id","=","NeedToHire_Accounts.aoc_patient_id");
        })
        ->leftJoin("recruiting_schedule",function($query){
            $query->on("mssql_patients.id","=","recruiting_schedule.aoc_patient_id");
        })
        ->where("NeedToHire_Accounts.IsActive","=",1)
        ->whereNotIN("recruiting_schedule.type_id",$this->notAllowedRecruitingScheduleType)
        ->whereRaw("mssql_patients.CF_MeetingNurses like 'Yes%'")
        ->groupBy('NeedToHire_Accounts.aoc_patient_id')->get();
        H::ExcelLog( "--Check Activity Count--".count($checklistData),
        'nthChecklistActivityNotifications.log');
        foreach($checklistData as $checklist) {
            try {
                //code...
                H::ExcelLog( "--Check Activity for NeedToHire_Checklist ID--".$checklist->nthcheckid,
                'nthChecklistActivityNotifications.log');
                $firstDate = date('Y-m-d',strtotime("-5 days", strtotime(date('Y-m-d'))));
                H::ExcelLog( "--Current minus Five days date--".$firstDate,
                'nthChecklistActivityNotifications.log');
                $lastFiveWorkingDayDate = $this->getWorkingdays($firstDate,5);
                H::ExcelLog( "--Last 5 working days date--".$lastFiveWorkingDayDate,
                'nthChecklistActivityNotifications.log');
                $lastFiveDayActiity = $this->getLastActivity($lastFiveWorkingDayDate,$checklist->nthcheckid);
                $clientDetail = DB::table("mssql_patients")
                ->select(DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName)
                AS patient_name"),'PatientAccount')
                ->where('id',$checklist->aoc_patient_id)->first();
                if(empty($lastFiveDayActiity)) {
                    H::ExcelLog( "----No Acitivity for 5 Days for NeedToHire_Checklist ID----".$checklist->nthcheckid,
                    'nthChecklistActivityNotifications.log');
                    $recruiterTeamLead = DB::table("users")->select('recruiting_teamleader')
                    ->where('id',$checklist->recruiter_id)->first();
                    //Send Notification
                    $title  = $clientDetail->patient_name.' has not had any NTH activity in 5 business days.
                    Please review.';
                    Notifications::addNotification(['user_id'=>$checklist->recruiter_id,
                    'title'=>$title,'type'=>'NeedToHire','related_item'=>$checklist->nthaccounts_id]);
                    $rDetails = DB::table('users')->select('users.name','users.email')->where('id',$checklist->recruiter_id)->first();
                    H::ExcelLog( $rDetails->name.','.$rDetails->email.','.$clientDetail->PatientAccount,'UsernthChecklistActivityNotifications.log');
                    if(!empty($recruiterTeamLead)) {
                        Notifications::addNotification(['user_id'=>$recruiterTeamLead->recruiting_teamleader,
                        'title'=>$title,'type'=>'NeedToHire','related_item'=>$checklist->nthaccounts_id]);
                        $rTeamDetails = DB::table('users')->select('users.name','users.email')->where('id',$recruiterTeamLead->recruiting_teamleader)->first();
                        H::ExcelLog( $rTeamDetails->name.','.$rTeamDetails->email.','.$clientDetail->PatientAccount,'UsernthChecklistActivityNotifications.log');
                    }
                } else {
                    H::ExcelLog( "----There are some acitivities for last 5 Days
                    for NeedToHire_Checklist ID----".$checklist->nthcheckid.'
                    so checking last 3 Days acitivities',
                    'nthChecklistActivityNotifications.log');
                    $lastThreeWorkingDayDate = $this->getWorkingdays($firstDate,3);
                    H::ExcelLog( "--Last 3 working days date--".$lastThreeWorkingDayDate .'
                    and last activity date--'.$lastFiveDayActiity->created_at ."
                    for NeedToHire_Checklist ID--".$checklist->nthcheckid,
                    'nthChecklistActivityNotifications.log');
                    //Send notification if no activity in last 3 days
                    if(strtotime($lastFiveDayActiity->created_at) < strtotime($lastThreeWorkingDayDate)) {
                        H::ExcelLog("-No Acitivity for 3 Days for NeedToHire_Checklist ID--".$checklist->nthcheckid,
                        'nthChecklistActivityNotifications.log');
                        //Send Notification
                        $title  = $clientDetail->patient_name.' has not had any NTH activity in 3 business days.
                        Please review.';
                        Notifications::addNotification(['user_id'=>$checklist->recruiter_id,
                        'title'=>$title,'type'=>'NeedToHire','related_item'=>$checklist->nthaccounts_id]);
                        $rDetails = DB::table('users')->select('users.name','users.email')->where('id',$checklist->recruiter_id)->first();
						H::ExcelLog( $rDetails->name.','.$rDetails->email.','.$clientDetail->PatientAccount,'UsernthChecklistActivityNotifications.log');
                    } else {
                        H::ExcelLog("-There is some activity in last 3 Days for
                        NeedToHire_Checklist ID--".$checklist->nthcheckid,'nthChecklistActivityNotifications.log');
                    }
                }
            } catch (\Throwable $th) {
                H::ExcelLog( "----Throwable----".$th->getMessage(), 'nthChecklistActivityNotifications.log');
            }
        }

        return Command::SUCCESS;
    }

    public function getLastActivity($lastFiveWorkingDayDate,$checklistId){
        return DB::table("notes")
        ->select('id','misc2','created_at')
        ->where('entity','NeedToHire')
        ->where('type','NTH')
        ->where('entity_id',$checklistId)
        ->whereDate('created_at','>=',date('Y-m-d H:i:s',strtotime($lastFiveWorkingDayDate)))
        ->orderBy('id','DESC')->first();
    }

    public function getWorkingdays($firstDate,$days) {
        $lastDate = date('Y-m-d');
        $holidays = array_map('trim', explode(",", H::getCoreSettings('holiday_schedule')));
        H::ExcelLog("Holiday===",'nthChecklistActivityNotifications.log');
        H::ExcelLog(print_r($holidays, true),'nthChecklistActivityNotifications.log');
        $start = strtotime($firstDate);
        $end   = strtotime($lastDate);
        $workingDays = 0;
        for ($i = $start; $i < $end; $i = strtotime("+1 day", $i)) {
            $day = date("w", $i);  // 0=sun, 1=mon, ..., 6=sat
            $mmgg = date('Y-m-d', $i);
            if ($day != 0 && $day != 6 && !in_array($mmgg, $holidays)) {
                $workingDays++;
            }
        }
        if($workingDays < $days) {
            $firstDate = date('Y-m-d',strtotime("-1 days", strtotime($firstDate)));
            return $this->getWorkingdays($firstDate,$days);
        }
        return $firstDate;
    }
}
