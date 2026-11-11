<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\H;
use Illuminate\Support\Facades\DB;

class DailyWindDownNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:dailywinddownnotification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        H::ExcelLog('---Daily Wind Down Notification START---', 'DailyWindDownNotification.log');
        try {
            $currentDayOfWeek = date('N');
            // Check if it's a weekday (Monday to Friday)
            if ($currentDayOfWeek >= 1 && $currentDayOfWeek <= 5) {
                $userWithPermission = H::getHelper('UserPermissionResolver')
                ->getUserByPermission('notification.daily-wind-down');
                $userIds = array_column($userWithPermission, 'id');
                H::ExcelLog(print_r($userIds, true), 'DailyWindDownNotification.log');
                $allowedUsers = DB::table('users')
                        ->select('users.name','users.id','users.user_timezone')
                        ->whereIn('users.id',$userIds)
                        ->get();
                // Get the current time
                $currentTime = new \DateTime('now');
                $title = 'Wind-Down Hour';
                $notes= '<ul><li>Any promised calls should be completed</li>
                <li>All emails should be read and responded to appropriately</li>
                <li>All voicemails returned</li>
                <li>Update families who have unmet coverage needs over the next 2-3 days</li>
                <li>Update your coverage tracker</li>
                <li>Add urgent coverage needs to the on-call coverage needs list</li>
                <li>Review each client on your caseload and ask:</li>
                <li>Did I talk to this client today? About what?
                <ul><li>Were there coverage needs? Do I need to update them?</li>
                <li>Were NTH calls made?</li>
                <li>Any schedule changes I need to update or notify clients about?</li>
                <li>Does anything require follow up?</li>
                <li>Are there any patients or nurses I said I would call back?</li></ul></li></ul>';
                foreach ($allowedUsers as $userDetail) {
                    if($userDetail->user_timezone != '') {
                        $timeZone = $userDetail->user_timezone;
                        if(strstr($userDetail->user_timezone,"#")) {
                            $timeZoneTemp = explode("#",$userDetail->user_timezone);
                            $timeZone = $timeZoneTemp[0];
                        }
                        $currentTime->setTimezone(new \DateTimeZone( $timeZone ));
                        echo "It's " . htmlspecialchars($currentTime->format('H'), ENT_QUOTES, 'UTF-8') . " in " . htmlspecialchars($timeZone, ENT_QUOTES, 'UTF-8') . ".\n";
                        H::ExcelLog("It's ".$currentTime->format('H')." in $timeZone for user=".$userDetail->id,
                        'DailyWindDownNotification.log');
                        // Check if the current time is 4 PM
                        if ($currentTime->format('H') == '16') {
                            $arr= ['user_id'=>$userDetail->id,
                            'title'=>$title,
                            'type'=>'wind-down-hour',
                            'related_item'=>'',
                            'notes'=>$notes];
                            $arr['created_at'] = $arr['updated_at'] = date('Y-m-d H:i:s');
                            $arr['created_by'] = $arr['updated_by'] = $userDetail->id;
                            DB::table('notifications')->insert($arr);
                        } else {
                            echo "It's not 4 PM in " . htmlspecialchars($timeZone, ENT_QUOTES, 'UTF-8') . ".\n";
                        }
                    }
                }
            }
        } catch (\Throwable $th) {
            H::ExcelLog( "----Throwable----".$th->getMessage(), 'DailyWindDownNotification.log');
        }
        H::ExcelLog('---Daily Wind Down Notification END---', 'DailyWindDownNotification.log');
        return Command::SUCCESS;
    }
}
