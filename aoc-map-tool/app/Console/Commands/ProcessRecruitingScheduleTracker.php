<?php

namespace App\Console\Commands;
use App\SyncProcesses\NotificationSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use Mail;

class ProcessRecruitingScheduleTracker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:recruitingscheduletracker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check if patient is latest admit on newly created tracker and schedule';
   
   

    
   

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
       
        echo $today = date("Y-m-d");

        $recruitingSchedules = DB::table("recruiting_schedule")->select("recruiting_schedule.id","recruiting_schedule.aoc_patient_id")
        ->leftJoin("mssql_patients","mssql_patients.id","=","recruiting_schedule.aoc_patient_id")
        ->where(function($q){
            $q->where("mssql_patients.isLatestAdmit","<>",1)->orWhereNull("mssql_patients.isLatestAdmit");
        })
        ->where("recruiting_schedule.aoc_patient_id","<>","0")
        ->whereDate("recruiting_schedule.created_at",$today)->get();
        $recruitingIds = [];
        foreach($recruitingSchedules as $recruitingSchedule) {
            $recruitingIds[] = $recruitingSchedule->id; 
        }


        $trackers = DB::table("mssql_recruitertracker")->select("mssql_recruitertracker.id","mssql_recruitertracker.aoc_patient_id")
        ->leftJoin("mssql_patients","mssql_patients.id","=","mssql_recruitertracker.aoc_patient_id")
        ->where(function($q){
            $q->where("mssql_patients.isLatestAdmit","<>",1)->orWhereNull("mssql_patients.isLatestAdmit");
        })
        ->where("mssql_recruitertracker.aoc_patient_id","<>","0")
        ->whereDate("mssql_recruitertracker.created_at",$today)->get();


        
        $trackerIds = [];
        foreach($trackers as $tracker) {
            $trackerIds[] = $tracker->id; 
        }

        if(!empty($recruitingIds) || !empty($trackerIds)) {
            $body = '<p>We have found below ids to be chcecked for isLatestAdmit</p>';

            if(!empty($recruitingIds)) {
                $body.='<p>Recruiting Schedule : '.implode(",",$recruitingIds).'</p>';
            }

            if(!empty($trackerIds)) {
                $body.='<p>Recruiter Tracker : '.implode(",",$trackerIds).'</p>';
            }
           

            Mail::send([], [], function($message) use($body){
                $message->to('aocappalerts@angelsofcare.com');
                $message->subject('Patient Without IsLatestAdmit Tracker and Schedule');
                $message->html($body);
               
            });
        } 
       
       
        return 0;
    }

    
}
