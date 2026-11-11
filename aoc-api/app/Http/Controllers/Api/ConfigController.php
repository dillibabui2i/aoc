<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Models\MobileAppMenuItems;
class ConfigController extends Controller
{
    //
    public function getConfigSetting(Request $request)
    {
        $response = [];
        //nth schedule settings
        $buildScheduleMode= [1,3,5];
        $reasonCategoryVal = [];
        $reasonCategory = ['AOC','Caregiver','Clinician'];
        $reason = config('constants')['reasonCategory'];
        foreach($reason as $rkey => $rglobalvals) {
            foreach($reason[$rkey] as $key => $globalvals) {
                if(in_array($key, $buildScheduleMode)) {
                    foreach($globalvals as $globaltemps) {
                        $reasonCategoryVal[$rkey][] = $globaltemps;
                    }
                }
            }
        }
        $response['nthschedule']['reason_category'] = $reasonCategory;
        $response['nthschedule']['reason'] = $reasonCategoryVal;
        return Helper::response(config('constants.response_code.OK'),$response);
    }

    public function getAppMenuItems(Request $request)
    {
        $response = [];
        $response["parent"] = MobileAppMenuItems::getAppMenuItemList(0,1);
        $response["clinician"] = MobileAppMenuItems::getAppMenuItemList(0,2);
        return Helper::response(config('constants.response_code.OK'),$response);
    }
}
