<?php

namespace App\SyncProcesses;

use App\AfterhoursSchedule;
use App\Helpers\H;
use App\Helpers\Notifications;
use App\User;
use Mail;

class AfterhoursScheduleReminder
{
    
    
    /**
     * The function to to send Reminders for Upcoming Shifts 
     *
     * @author Nayan - 24 Nov 2022
     * 
     */
    public function sendAfterhoursScheduleReminders($send_time)
    {
        $diff = 0;
        if($send_time == '3days' || $send_time == '72') {
            $diff = 72;
            //get all schedule - where start date and time -72 hours > now and flag = 0
        }
        if (empty($send_time)) {
            $diff = 24;
        } else {
            $diff = $send_time;
        }
        $notificationQuery = AfterhoursSchedule::select('afterhours_schedule.*','users.id as shift_user_id')->leftJoin('users', 'users.id', '=', 'afterhours_schedule.user_id')
            ->whereRaw("date_add(date_add(CONCAT(afterhours_schedule.start_date,' ',afterhours_schedule.start_time),interval -".$diff." hour), interval IFNULL(SUBSTRING_INDEX(user_timezone, '#', -1),0) hour) <= now()")
            ->where('afterhours_schedule.user_id','>',0)
            ->whereRaw("afterhours_schedule.start_date > now()");
        if($diff == 72) {
            $notificationQuery = $notificationQuery->where('notification2',1);
        } elseif($diff == 24) {
            $notificationQuery = $notificationQuery->where('notification1',1);
         }elseif($diff == 48) {
            $notificationQuery = $notificationQuery->where('notification3',1);
        } elseif($diff == 6) {
            $notificationQuery = $notificationQuery->where('notification4',1);
        } elseif($diff == 1) {
            $notificationQuery = $notificationQuery->where('notification5',1);
        } else {
            echo "this is not a valid process parameter" . "\n";
            exit;
        }

        $notificationAfterHourList = $notificationQuery->get()->toArray();
        $emailTextURL = env('APP_URL').'/afterhours/showcalendarpopup';
        if(count($notificationAfterHourList) > 0) {
            foreach($notificationAfterHourList as $notificationAfterHourData ) {
                var_dump($notificationAfterHourData);
                echo "\n\n";
                //Notification - start
                $assigneeUserDetail = User::find($notificationAfterHourData['user_id']);
                $notificationText = "Your After Hours Shift Is at, " . H::getFormatedDate($notificationAfterHourData['start_date']) . " at " . H::getFormatedTime($notificationAfterHourData['start_date'] . ' ' . $notificationAfterHourData['start_time']);
                Notifications::addNotification($data = ['user_id' => $notificationAfterHourData['user_id'], 'title' => $notificationText, 'type' => 'AfterHours', 'related_item' => $notificationAfterHourData['id']]);
                //Notification - end
                //Email - start
                $emailNotificationText = "You've been scheduled for an After Hours shift " . H::getFormatedDate($notificationAfterHourData['start_date']) . " at " . H::getFormatedTime($notificationAfterHourData['start_date'] . ' ' . $notificationAfterHourData['start_time']) . " - " . H::getFormatedTime($notificationAfterHourData['end_date'] . ' ' . $notificationAfterHourData['end_time']).'. Click <a href="'.$emailTextURL. '/' . $notificationAfterHourData['id'].'">here</a> to learn more.';
                $subject = 'Your after hours shift begins at '.H::getFormatedTime($notificationAfterHourData['start_date'] . ' ' . $notificationAfterHourData['start_time']).' on '.H::getFormatedDate($notificationAfterHourData['start_date']);
                $data = [];
                $data['body']  = $emailNotificationText;
                $data['subject']  = $subject;
                $data['to_email']  = $assigneeUserDetail->email;
                echo "sending email: " . "\n";
                var_dump($data);
                echo "\n\n\n";
                $this->sendEmailNotification($data);
                echo "updating records for " . $diff . "\n";
                $updateArray = [];
                if($diff == 72) {
                    $updateArray = ['notification2' => 2];
                } elseif($diff == 24) {
                    $updateArray =['notification1' => 2];
                 }elseif($diff == 48) {
                    $updateArray =['notification3' => 2];
                } elseif($diff == 6) {
                    $updateArray =['notification4' => 2];
                } elseif($diff == 1) {
                    $updateArray =['notification5' => 2];
                }
                var_dump($updateArray);
                echo "\n\n";
                if (count($updateArray) > 0) {
                    AfterhoursSchedule::where('id',$notificationAfterHourData['id'])->update($updateArray);
                }
            }
        }
    }

    public function sendEmailNotification($data)
    {
        $data['from_name']  =  'Angels Of Care';
        $data['from_email']  =  env('MAIL_FROM_ADDRESS');
        Mail::send([], [], function ($message) use ($data) {
            $message->from($data['from_email'], $data['from_name']);
            $message->subject($data['subject']);
            $message->to($data['to_email']);
            $message->html($data['body']);
        });
    }
}
