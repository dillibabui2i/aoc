<?php
namespace App\Helpers;

use App\Notification;
use Illuminate\Support\Facades\DB;

class Notifications
{

    public static function addNotification($arr, $returnId = false){
        $loggedInUser = H::getLoggedInUser();
        $loggedInUserId = 1;
        $username = 'App Bot';
        if(isset($loggedInUser['id']) && $loggedInUser['id'] != ''){
            $loggedInUserId = $loggedInUser['id'];
            $username = $loggedInUser['name'];
        }
        if(isset($arr['user_id']) && $arr['user_id'] != '' && isset($arr['title']) && $arr['title'] != '' && $loggedInUserId != ''){
            if($arr['type'] == 'invoice') {
                $invoiceDetails = DB::table('claims_master')->select('claims_master.invoice_number')
                ->where("claims_master.id",$arr['related_item'])->first();
                $arr['title'] = 'Note added to invoice # '.$invoiceDetails->invoice_number .' by '.$username;
            } elseif($arr['type'] == 'billing') {
                if(LD::checkFeatureFlag('change-notification-unbilled-schedules-billing-dashboard')) {
                    $visitDateColumn = 'mssql_schedulemaster.ScheduleDate';
                    $visitDateformat = config('constant')['mysqlsearchdateformat'];
                    if(LD::checkFeatureFlag('use-child-rows-unbilled-buckets-billing-dashboard')) {
                        $visitDateColumn = 'mssql_schedulemaster.PlannedStart';
                        $visitDateformat = config('constant')['mysqlsearchdateformat'];
                    }
                    $scheduleId = $arr['related_item'];
                    if(LD::checkFeatureFlag('send-one-notifications-per-week-row-unbilled-schedules')) {
                        $scheduleId = explode(",",$arr['misc1'])[0];
                    }
                    $billingDetails = DB::table('mssql_schedulemaster')->select(
                        DB::raw('DATE_FORMAT('.$visitDateColumn.',"'.$visitDateformat.'")
                        as ScheduleDate'),
                        DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',
                        IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),
                        mssql_patients.Patient_LastName) AS clientName"),
                        DB::raw("CONCAT(mssql_providers.FirstName,' ',
                        IF(mssql_providers.MiddleName != '',CONCAT(mssql_providers.MiddleName, ' '),''),
                        mssql_providers.LastName) AS clinicianName")
                    )
                    ->leftJoin('mssql_patients', 'mssql_schedulemaster.PatientID', '=', 'mssql_patients.PatientID')
                    ->leftJoin('mssql_providers', 'mssql_schedulemaster.ProviderID', '=', 'mssql_providers.ProviderID')
                    ->where("mssql_schedulemaster.id",$scheduleId)->first();
                    $arr['title'] = 'Note added to unbilled schedule for '.$billingDetails->clientName .' with '.$billingDetails->clinicianName .' on '.$billingDetails->ScheduleDate;
                }else {
                    $arr['title'] = str_replace('Notes Added','Notes Added by '.$username,$arr['title']);
                }
            }else {
                $arr['title'] = str_replace('Notes Added','Notes Added by '.$username,$arr['title']);
            }
            $arr['created_at'] = $arr['updated_at'] = date('Y-m-d H:i:s');
            $arr['created_by'] = $arr['updated_by'] = $loggedInUserId;

            if($returnId){
                return DB::table('notifications')->insertGetId($arr);
            }

            return DB::table('notifications')->insert($arr);
        }else{
            return false;
        }
    }

    public static function addNotificationFromConsole($arr){
        $loggedInUser = H::getLoggedInUser();
        $loggedInUserId = 1;
        if(isset($loggedInUser['id']) && $loggedInUser['id'] != ''){
            $loggedInUserId = $loggedInUser['id'];
        }
        if(isset($arr['user_id']) && $arr['user_id'] != '' && isset($arr['title']) && $arr['title'] != '' && $loggedInUserId != ''){
            $arr['created_at'] = $arr['updated_at'] = date('Y-m-d H:i:s');
            $arr['created_by'] = $arr['updated_by'] = $loggedInUserId;
            return DB::table('notifications')->insert($arr);
        }else{
            return false;
        }
    }
    public static function editableNotification($tableName,$id,$fieldName,$value){
        if($tableName != '' && $id != '' && $fieldName != '' && $value != ''){
            $data = DB::table($tableName)->select('*')->where('id',$id)->first();

            switch ($tableName) {
                case 'recruiting_schedule':
                    $trackerData = DB::table('mssql_recruitertracker')->select('*')->where('id',$data->tracker_id)->first();
                    if(isset($trackerData->user_id) && $trackerData->user_id != ''){
                        switch ($fieldName) {
                            case 'IPEA_interview':
                                if($value == 'Good Fit'){
                                    $candidate = isset($trackerData->Candidate) ? $trackerData->Candidate : '';
                                    $title = 'Branch determined client is a good fit for '.$candidate;
                                    self::addNotification(['user_id'=>$trackerData->user_id,'title'=>$title,'type'=>'trackerDetails','related_item'=>$data->tracker_id,'notes'=>$title]);
                                }elseif($value == 'Suggest Other Clients'){
                                    $candidate = isset($trackerData->Candidate) ? $trackerData->Candidate : '';
                                    $title = 'Branch suggested other clients for '.$candidate;
                                    self::addNotification(['user_id'=>$trackerData->user_id,'title'=>$title,'type'=>'trackerDetails','related_item'=>$data->tracker_id,'notes'=>$title]);
                                }
                                break;

                            default:
                                # code...
                                break;
                        }
                    }
                    break;

                default:
                    # code...
                    break;
            }
        }
    }
    public static function getNotificationCount()
    {
        $ignoreSchedule = true;

        if (H::getHelper('UserPermissionResolver')->isRoleHasPermissionNew('notification.list')){
            $ignoreSchedule = false;
        }


        $loggedInUser = H::getLoggedInUser();
        $userId = $loggedInUser['id'];
        $notificationCount = 0;
        $notificationCount = Notification::where('user_id', $userId)
            ->where('cleared', 0)
            ->where(
                function ($query) {
                    $query->where('snooze_until', '<', date('Y-m-d H:i:s'))
                    ->orWhereNull('snooze_until');
                }
            );

        if ($ignoreSchedule) {
            $notificationCount = $notificationCount->where('title', '!=', 'NTH Schedule changed');
        }

        $notificationCount = $notificationCount->count();

        return $notificationCount;
    }
}
