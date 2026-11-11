<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    //
    protected $table = 'activity_log';
    public $timestamps = false;

    protected $fillable = [
        'user_name', 'ip_address', 'page_action', 'log_info',
    ];

    public static function addActivityLog($activityLog)
    {
        try {
            $activityLog = [
                'user_name'=>$activityLog['user_name'],
                'ip_address'=>$activityLog['ip_address'],
                'page_action'=>$activityLog['page_action'],
                'log_info'=>$activityLog['log_info']
            ];
            self::create($activityLog);
        } catch (\Exception $e) {
            logger('===addActivityLog-Exception=' . $e->getMessage());
        }
    }
}
