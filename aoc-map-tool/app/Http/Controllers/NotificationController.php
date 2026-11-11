<?php

namespace App\Http\Controllers;

use App\Helpers\H;
use App\Helpers\Notifications;
use App\Helpers\UserPermissionResolver;
use App\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Session;
class NotificationController extends Controller
{
    public function index(Request $request, UserPermissionResolver $permissionResolver)
    {
        $notifications = [];
        $loggedInUser = H::getLoggedInUser();
        $userId = $loggedInUser['id'];
        $permissionID = $permissionResolver->getPermissionIdByPermissionName('notification.list');
        $hasNTHScheduleChangePermission = $permissionResolver->isUserHasPermission(Auth::id(), $permissionID);
        $hasNTHScheduleChangePermission = !is_null($hasNTHScheduleChangePermission);
        $currentRoleHasNTHScheduleChangePermission = $permissionResolver->isRoleHasPermissionNew('notification.list');

//         Notifications::addNotification(['user_id'=>Auth::id(),'title'=>'Testing Notification to add','notes'=>"Test Notes", 'type' => 'nthScheduleChange', 'related_item'=>225480]);
        $notifications = Notification::where('user_id', $userId)
            ->where('cleared', 0)
            ->where(
                function ($query) {
                    $query->where('snooze_until', '<', date('Y-m-d H:i:s'))
                    ->orWhereNull('snooze_until');
                }
            )->orderBy('created_at','desc')->limit(100)->get();

        return view('notification.list', [
            'notifications' => $notifications,
            'hasNTHScheduleChangePermission' => ($hasNTHScheduleChangePermission || $currentRoleHasNTHScheduleChangePermission),
        ]);
    }

    public function clearedList(Request $request)
    {
        $notifications = [];
        $loggedInUser = H::getLoggedInUser();
        $userId = $loggedInUser['id'];
        $notifications = Notification::where('user_id', $userId)
            ->where('cleared', 1)
            ->where(
                function ($query) {
                    $query->where('snooze_until', '<', date('Y-m-d H:i:s'))
                    ->orWhereNull('snooze_until');
                }
            )->orderBy('cleared_at','desc')->take(25)->get();

        return view('notification.list', ['notifications'=>$notifications]);
    }

    public function markAsRead(Request $request)
    {
        $id = $request->input('id');
        if (isset($id) && $id != '') {
            if (DB::table('notifications')->where('id', $id)->update(['cleared'=>1,'cleared_at'=>date('Y-m-d H:i:s')])) {
                return response()->json(['status'=>true]);
            } else {
                return response()->json(['status'=>false]);
            }
        } else {
            return response()->json(['status'=>false]);
        }
    }
    public function undoNotification(Request $request)
    {
        $id = $request->input('id');
        if (isset($id) && $id != '') {
            if (DB::table('notifications')->where('id', $id)->update(['cleared'=>0])) {
                return response()->json(['status'=>true]);
            } else {
                return response()->json(['status'=>false]);
            }
        } else {
            return response()->json(['status'=>false]);
        }
    }

    public function addSnooze(Request $request)
    {
        $reqData = $request->all();
        if (isset($reqData['id']) && $reqData['id'] != '' && isset($reqData['datetime']) && $reqData['datetime'] != '' && isset($reqData['isCustomDatetime']) && $reqData['isCustomDatetime'] != '') {
            $datetime = $reqData['datetime'];
            if ($reqData['isCustomDatetime'] == 'false') {
                $datetime = date('Y-m-d H:i:s', strtotime('+ ' . $datetime));
            }
            if (DB::table('notifications')->where('id', $reqData['id'])->update(['snooze_until'=>$datetime])) {
                return response()->json(['status'=>true]);
            } else {
                return response()->json(['status'=>false]);
            }
        } else {
            return response()->json(['status'=>false]);
        }
    }
    // public function pushNotification(){

    //     return response()->stream(function () {
    //         $loggedInUser = H::getLoggedInUser();
    //         $userId = $loggedInUser['id'];
    //         while (true) {
    //             if (connection_aborted()) {
    //                 break;
    //             }
    //             $messages = Notification::where('user_id', $userId)
    //             ->where('cleared', 0)
    //             ->where(
    //                 function ($query) {
    //                     $query->where('snooze_until', '<', date('Y-m-d H:i:s'))
    //                     ->orWhereNull('snooze_until');
    //                 }
    //             )->where('created_at', '>=', Carbon::now()->subSeconds(5))->get();
    //             if (!$messages->isEmpty()) {
    //                 echo "event: ping\n", "data: {$messages}", "\n\n";
    //             }else{
    //                 echo "event: ping\n", "data:[]", "\n\n";
    //             }
    //             flush();
    //             sleep(5);
    //         }
    //     }, 200, [
    //         'Cache-Control' => 'no-cache',
    //         'Content-Type' => 'text/event-stream',
    //     ]);
    // }
    public function pushNotification(){

        $loggedInUser = H::getLoggedInUser();
        $userId = $loggedInUser['id'];
        $msg['count'] = Notifications::getNotificationCount();
            $messages = Notification::where('user_id', $userId)
            ->where('cleared', 0)
            ->where(
                function ($query) {
                    $query->where('snooze_until', '<', date('Y-m-d H:i:s'))
                    ->orWhereNull('snooze_until');
                }
            )->where('created_at', '>=', Carbon::now()->subSeconds(60))->get()->toArray();
            return response()->json(['status'=>true,'messages'=>$messages,'count'=>$msg['count']]);
    }
    // public function notificationCount(){
    //     return response()->stream(function () {
    //         while (true) {
    //             if (connection_aborted()) {
    //                 break;
    //             }
    //             $messages['count'] = Notifications::getNotificationCount();
    //             if (isset($messages['count'])) {
    //                 echo "event: count\n", "data:".$messages['count'], "\n\n";
    //             }else{
    //                 echo "event: count\n", "data:0", "\n\n";
    //             }
    //             flush();
    //             sleep(5);
    //         }
    //     }, 200, [
    //         'Cache-Control' => 'no-cache',
    //         'Content-Type' => 'text/event-stream',
    //     ]);
    // }
    // public function notificationCount(){
    //     $messages['count'] = Notifications::getNotificationCount();
    //     if (isset($messages['count'])) {
    //         return response()->json(['status'=>true,'messages'=>$messages]);
    //     }else{
    //         return response()->json(['status'=>false]);
    //     }
    // }

    public function sentList(Request $request)
    {
        $notifications = [];
        $loggedInUser = H::getLoggedInUser();
        $userId = $loggedInUser['id'];
        $notifications = Notification::select('notifications.*','users.name as receiver_name')->where('notifications.created_by', $userId)->leftJoin("users","users.id","=","notifications.user_id")->where('notifications.notes','LIKE', '%@%')->orderBy('notifications.created_at','desc')->take(100)->get();

        return view('notification.list', ['notifications'=>$notifications]);
    }

    public function markMultipleAsRead(Request $request) {
        try {
            $params = $request->all();
            if($params['selectType'] == 'few') {
                $checklistArray = explode(",",$params['ids']);
            }else{
                $notifications = [];
                $loggedInUser = H::getLoggedInUser();
                $userId = $loggedInUser['id'];
                $notifications = Notification::where('user_id', $userId)->where('cleared', 0)
                    ->where(
                        function ($query) {
                            $query->where('snooze_until', '<', date('Y-m-d H:i:s'))
                            ->orWhereNull('snooze_until');
                        })->orderBy('created_at','desc');

                if($params['selectType'] == 'currentpage') {
                    $notifications->limit(100);
                }
                $notifications= $notifications->get()->toArray();
                $checklistArray = array_values(array_column($notifications,'id'));
            }
            foreach($checklistArray as $checklistId){
                DB::table('notifications')->where('id', $checklistId)->update(['cleared'=>1,'cleared_at'=>date('Y-m-d H:i:s')]);
            }
            $message = count($checklistArray).' notifications are marked as clear!';
            if(count($checklistArray) ==1 ) {
                $message = count($checklistArray).' notification is marked as clear!';
            }
            Session::flash('message', $message);
            return response()->json(['success' => 1,'message' => $message]);
        } catch (\Exception $ex) {
            H::ExcelLog('markMultipleAsRead Exception: ' . $ex->getMessage(),'markMultipleAsRead.log');
            return response()->json(['success' => 0, 'message' => 'Something went wrong, Please try again.']);
        }
    }
}
