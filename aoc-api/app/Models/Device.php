<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Device extends Model
{
    use HasFactory;
    protected $table = 'devices';
    protected $fillable = ['platform_id', 'app_version', 'user_id', 'name', 'os', 'model', 'udid', 'fcm_token', 'resolution', 'status'];
    protected static function createOrUpdate($request)
    {
        $device = self::where('udid', $request['udid'])->first();

        if (!empty($device)) {
            $device->id = $device->id;
            $device->platform_id = $request['pf']??'';
            $device->name = $request['name']??'';
            $device->model = $request['model']??'';
            $device->resolution = $request['resolution']??'';
            $device->fcm_token = isset($request['fcm_token']) ? $request['fcm_token'] : '';
        } else {
            $device = new Device();
            $device->user_id = 0;
            $device->status = 0;
            $device->udid = $request['udid'];
            $device->platform_id = $request['pf']??'';
            $device->name = $request['name']??'';
            $device->model = $request['model']??'';
            $device->resolution = $request['resolution']??'';
            $device->fcm_token = isset($request['fcm_token']) ? $request['fcm_token'] : '';
        }
        $device->app_version = $request['app_ver']??'';
        $device->os = $request['os']??'';
        $device->save();
        return $device->id;
    }
    protected static function doDeviceOperation($request)
    {
        $device = self::find($request['device_id']);
        self::where([['id', '=', $request['device_id']]])->update(['status' => 1, 'user_id' => $request['user_id']]);
        return $device;
    }
}
