<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CoreSettingController extends Controller
{
    public function index()
    {    //var_dump($_SERVER['REMOTE_ADDR']);
        return view('coresetting.settings');
    }

    public function save(Request $request)
    {
        $reqData = $request->all();


        if (isset($reqData['mfa']) && $reqData['mfa'] == 'on') {
            DB::table('core_setting')
              ->where('opt', 'mfa')
              ->update(['val' => 'true']);
        } else {
            DB::table('core_setting')
              ->where('opt', 'mfa')
              ->update(['val' => 'false']);
        }

        if(isset($reqData['change_password'])) {
            DB::table('core_setting')
            ->where('opt', 'master_password')
            ->update(['val' => Hash::make($reqData['master_password'])]);
        }

        // if(isset($reqData['login_notification_email'])){
        DB::table('core_setting')
            ->where('opt', 'login_notification_email')
            ->update(['val' => $reqData['login_notification_email']]);
        //  }

        // if(isset($reqData['recaptcha_key'])){
        DB::table('core_setting')
            ->where('opt', 'recaptcha_key')
            ->update(['val' => $reqData['recaptcha_key']]);
        //  }

        //   if(isset($reqData['recaptcha_secret'])){
        DB::table('core_setting')
            ->where('opt', 'recaptcha_secret')
            ->update(['val' => $reqData['recaptcha_secret']]);
        //  }

        if (isset($reqData['mfa_otp_title']) && $reqData['mfa_otp_title'] != '') {
            DB::table('core_setting')
              ->where('opt', 'mfa_otp_title')
              ->update(['val' => $reqData['mfa_otp_title']]);
        }
        if (isset($reqData['mfa_otp_content']) && $reqData['mfa_otp_content'] != '') {
            DB::table('core_setting')
              ->where('opt', 'mfa_otp_content')
              ->update(['val' => $reqData['mfa_otp_content']]);
        }
        if (isset($reqData['mfa_setup_title']) && $reqData['mfa_setup_title'] != '') {
            DB::table('core_setting')
              ->where('opt', 'mfa_setup_title')
              ->update(['val' => $reqData['mfa_setup_title']]);
        }
        if (isset($reqData['mfa_setup_content']) && $reqData['mfa_setup_content'] != '') {
            DB::table('core_setting')
              ->where('opt', 'mfa_setup_content')
              ->update(['val' => $reqData['mfa_setup_content']]);
        }
        if (isset($reqData['login_title']) && $reqData['login_title'] != '') {
            DB::table('core_setting')
              ->where('opt', 'login_title')
              ->update(['val' => $reqData['login_title']]);
        }
        if (isset($reqData['login_content']) && $reqData['login_content'] != '') {
            DB::table('core_setting')
              ->where('opt', 'login_content')
              ->update(['val' => $reqData['login_content']]);
        }
        if (isset($reqData['jobvite_follow_up_date']) && $reqData['jobvite_follow_up_date'] != '') {
            DB::table('core_setting')
              ->where('opt', 'jobvite_follow_up_date')
              ->update(['val' => $reqData['jobvite_follow_up_date']]);
        }

        if (isset($reqData['holiday_schedule']) && $reqData['holiday_schedule'] != '') {
            DB::table('core_setting')
              ->where('opt', 'holiday_schedule')
              ->update(['val' => $reqData['holiday_schedule']]);
        }

        return redirect('/core-setting/settings')->with('success', 'Core Setting Changed!');
    }
}
