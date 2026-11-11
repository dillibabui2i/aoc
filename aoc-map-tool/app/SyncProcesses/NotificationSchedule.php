<?php

namespace App\SyncProcesses;

use App\Helpers\H;
use App\Helpers\Notifications;
use DB;
class NotificationSchedule
{
    public function sendJobviteNotification()
    {
        // Fetching the Jobvite which has "Recruiter Phone Screen" outcome with followup datetime
        $jobvites = DB::table('candidates')->select('id','fullName','FollowUpDateTime','user_id')->where('appWorkflowState','Recruiter Phone Screen')->whereNotNull('FollowUpDateTime')->whereRaw('TIMESTAMPDIFF(MINUTE, NOW(), FollowUpDateTime) = 60')->get();
        $jobvitesIds = array_column($jobvites->toArray(),'id');
        if(!empty($jobvitesIds)){
            $notifications = DB::table('notifications')->select('related_item')->where('type','candidate')->where('title','like','%Recruiter Phone Screen scheduled for%')->whereIn('related_item',$jobvitesIds)->get();
            $notificationsIds = array_column($notifications->toArray(),'related_item');
            foreach ($jobvites as $key => $value) {
                if(!in_array($value->id,$notificationsIds)){
                    Notifications::addNotificationFromConsole(['user_id' => $value->user_id, 'title' => 'Recruiter Phone Screen with '.$value->fullName.' at '.H::getFormatedDateTime($value->FollowUpDateTime).'.', 'type' => 'candidate', 'related_item' => $value->id]);
                }
            }
        }
    }
}
