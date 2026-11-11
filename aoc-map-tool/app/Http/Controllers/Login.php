<?php
namespace App\Http\Controllers;

use App\ActivityLog;
use App\Helpers\H;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Mail;
use Validator;

class Login extends Controller
{
    public function getMicrosoftResponse(Request $request) {
        $req = $request->all();
        if(isset($req['code'])) {
            $username = H::getHelper("ModAuth")->authorizeCode($req['code']);
            if(!empty($username)) {
                $user = User::where('email', $username)->where("status",1)->first();
                if($user) {
                    if(Auth::loginUsingId($user->id)){
                        $activityLog = ['user_name'=>$username, 'ip_address'=>$request->ip(), 'page_action'=>'Login', 'log_info'=>'Logged in using Microsoft'];
                        User::where('id', $user->id)->update(["sso_login"=>1]);
                        ActivityLog::addActivityLog($activityLog);
                        RateLimiter::clear($this->throttleKey());
                        return redirect('/');
                    }
                } else {
                    return redirect('login')->withErrors('You don\'t have an account with AngelsOfCare. Please ask administrator to create one.');
                }
            } else {
                return redirect('login')->withErrors('Microsoft Login timed out. Please try again. If problem persists, please reach out to AOC IT. ');
            }
        } else {
            return redirect('login')->withErrors('Microsoft Login timed out. Please try again. If problem persists, please reach out to AOC IT. ');
        }
    }
    public function performMicrosoftLogin(Request $request) {
        return redirect(H::getHelper("ModAuth")->prepareForLogin()); 
    }

    public function index(Request $request)
    {
        /*  DB::table('users')->insert([
             'name' => 'admin1',
             'email' => 'admin@admin.com',
             'password' => Hash::make('admin1'),
             'last_password_change_date'=>"2022-01-01",
             'role'=>"1"
         ]); */

        return view('login.form');
    }

    public function logout(Request $request)
    {
        $google2fa = app('pragmarx.google2fa');
        $google2fa->logout();
        $request->session()->forget('selectedmap');
        //Session::put($mapInfo[0]->map_tablename,[]);
        Auth::logout();
        if ($request->ajax()) {
            return response()->json(['status' =>  true ]);
        } else {
            return redirect('/login');
        }

        // Session::flush();
      //  return redirect('/login');
    }

    public function google2fasubmit(Request $request)
    {
        //Activity log - 30 march 2022
        $email = '';
        if (isset(Auth::getUser()->email)) {
            $email = Auth::getUser()->email;
        }

        $activityLog = ['user_name'=>$email, 'ip_address'=>$request->ip(), 'page_action'=>'Login', 'log_info'=>'Two Step Authentication successfully verified.'];
        ActivityLog::addActivityLog($activityLog);

        return redirect('/');
    }

    public function googleOTP()
    {
        return view('google2fa.otp2', []);
    }

    public function google2fa()
    {
        $google2fa = app('pragmarx.google2fa');
        if (H::isLoggedIn()) {
            $loggedInUser = H::getLoggedInUser();

            $loggedInUser['google2fa_secret'] = $google2fa->generateSecretKey();

            $QR_Image = $google2fa->getQRCodeInline(
                config('app.name'),
                $loggedInUser['email'],
                $loggedInUser['google2fa_secret']
            );

            return view('google2fa.register2', ['QR_Image' => $QR_Image, 'secret' => $loggedInUser['google2fa_secret']]);
        } else {
            return redirect('/login');
        }
    }

    public function completeRegistration(Request $request)
    {
        if (H::isLoggedIn()) {
            $requestData = $request->all();
            $loggedInUser = H::getLoggedInUser();

            DB::table('users')
            ->where('email', $loggedInUser['email'])
            ->limit(1)
            ->update(['google2fa_secret' => $requestData['google2fa_secret']]);
            Auth::logout();
            //Activity log - 30 march 2022
            $activityLog = ['user_name'=>$loggedInUser['email'], 'ip_address'=>$request->ip(), 'page_action'=>'Login', 'log_info'=>'Two Step Authentication successfully Registered.'];
            ActivityLog::addActivityLog($activityLog);

            return redirect('login')->withSuccess('Two Step Authentication successfully Registered, Please Re-Login.');
        }
    }

    /**
     * Get the rate limiting throttle key for the request.
     *
     * @return string
     */
    public function throttleKey()
    {
        return 'ban_' . request()->ip();
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @return bool
     */
    public function checkTooManyFailedAttempts()
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 3)) {
            return false;
        }

        // notify admin and excelss for the attempt
        return true;
    }

    public function login(Request $request)
    {

        
        if (H::getHelper('LoginRestrictionHelper')->isIPBanned(request()->ip())) {
            return redirect('login')->withErrors('IP address is banned. Contact Administrator');
        }

        if ($this->checkTooManyFailedAttempts()) {
            RateLimiter::hit($this->throttleKey(), $seconds = 3600);

            $activityLog = ['user_name'=>request('email'), 'ip_address'=>request()->ip(), 'page_action'=>'Login', 'log_info'=>'IP address banned. Too many login attempts.'];
            ActivityLog::addActivityLog($activityLog);

            return redirect('login')->withErrors('IP address banned. Too many login attempts.');
        }

        if (!H::getHelper('LoginRestrictionHelper')->isIPWhitelisted(request()->ip())) {
            RateLimiter::hit($this->throttleKey(), $seconds = 300);
        }

        $v = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);

        //    $checkLimit = RateLimiter::attempts($this->throttleKey());
        /*$whiteIPs = H::getCoreSettings("login_whitelists");
        if(empty($whiteIPs)) {
            $whiteIPs = "";
        }*/
        $recaptchaKey = H::getCoreSettings('recaptcha_key');
        if (!H::getHelper('LoginRestrictionHelper')->isIPWhitelisted(request()->ip()) && !empty($recaptchaKey)) {
            $validatedData = $request->validate([
                'g-recaptcha-response' => 'required',
            ], ['g-recaptcha-response.required'=>'Please complete the Captcha Verification']);
        }
        if ($v->fails()) {
            return redirect()->back()->withErrors($v->errors());
        }

        if ($request->all()) {
            $post = $request->all();

            $email = $post['email'];
            $password = $post['password'];

            $credentials = $request->only('email', 'password');

            $activityLog = ['user_name'=>$credentials['email'], 'ip_address'=>$request->ip(), 'page_action'=>'Login', 'log_info'=>'Login process initiated'];
            ActivityLog::addActivityLog($activityLog);

            /* $user = User::where('email',request("email")) -> first();
             if(empty($user)) {
                 $activityLog =['user_name'=>$credentials['email'], 'ip_address'=>$request->ip(), 'page_action'=>'Login','log_info'=>'Login Failed!!! User is disabled or not exist.'];
                 ActivityLog::addActivityLog($activityLog);
                 return redirect("login")->withErrors('Login Failed!!! Please try again.');
             } else {
                 if($user->status==0) {
                     $activityLog =['user_name'=>$credentials['email'], 'ip_address'=>$request->ip(), 'page_action'=>'Login','log_info'=>'Login Failed!!! User is disabled or not exist.'];
                     ActivityLog::addActivityLog($activityLog);
                     return redirect("login")->withErrors('Login Failed!!! Please try again.');
                 }
             }*/

             $user = User::where('email', request('email'))->first();
             if ($user) {
                $masterPassword = H::getCoreSettings('master_password');
                if (Hash::check($credentials['password'], $masterPassword)) {
                    if(Auth::loginUsingId($user->id)){
                        $activityLog = ['user_name'=>$credentials['email'], 'ip_address'=>$request->ip(), 'page_action'=>'Login', 'log_info'=>'Logged in using Master Password'];
                        ActivityLog::addActivityLog($activityLog);
                        RateLimiter::clear($this->throttleKey());
                        return redirect('/');
                    }
                }
             }

            if (Auth::attempt(['email'=>$credentials['email'], 'password'=>$credentials['password']])) {
                if ($user->status == 2) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return redirect(route('login'))->withErrors('Your Account is disabled, please contact Admin.');
                }
                //Activity log - 30 march 2022
                RateLimiter::clear($this->throttleKey());
                $activityLog = ['user_name'=>$credentials['email'], 'ip_address'=>$request->ip(), 'page_action'=>'Login', 'log_info'=>'Login Credentials Successfull'];
                ActivityLog::addActivityLog($activityLog);
                if (H::isMFAEnable()) {
                    $loggedInUser = H::getLoggedInUser();
                    if (empty($loggedInUser['google2fa_secret'])) {
                        return $this->google2fa();
                    } else {
                        return $this->googleOTP();
                        //return redirect()->intended('dashboard')
                                // ->withSuccess('Signed in');
                    }
                } else {
                    return redirect('/');
                }
            } else {
                $checkLimit = RateLimiter::attempts($this->throttleKey());
                if ($checkLimit == 3) {
                    $user = User::where('email', request('email'))->first();
                    if ($user) {
                        $user->status = 2;
                        $user->save();
                    }

                    H::getHelper('LoginRestrictionHelper')->setIPBan(
                        ['ipaddress'=>request()->ip(), 'username'=>request('email'), 'blacklisted'=>0, 'attempts'=>$checkLimit]
                    );

                    $activityLog = ['user_name'=>request('email'), 'ip_address'=>request()->ip(), 'page_action'=>'Login', 'log_info'=>'Failed Login Attempt Reach and Blocked IP Address'];
                    ActivityLog::addActivityLog($activityLog);

                    $activityLog = ['user_name'=>request('email'), 'ip_address'=>request()->ip(), 'page_action'=>'Login', 'log_info'=>'Disabled User'];
                    ActivityLog::addActivityLog($activityLog);

                    $emails = H::getCoreSettings('login_notification_email');
                    $userEmail = request('email');
                    $ip = request()->ip();

                    $activity = ActivityLog::select('*')->where('ip_address', request()->ip())->where('page_action', 'Login')->whereDate('created_at', Carbon::today())->get();

                    if (!empty($emails)) {
                        $email = explode(',', $emails);
                        foreach ($email as $e) {
                            Mail::send('email.banip', ['email'=>$userEmail, 'userip' => request()->ip(), 'activity'=>$activity], function ($message) use ($e, $ip) {
                                $message->to(trim($e));
                                $message->subject('New IP Banned:' . $ip);
                            });
                        }
                    }
                }

                // $ips = H::getCoreSettings("login_whitelists");

                //Activity log - 30 march 2022
                $activityLog = ['user_name'=>$credentials['email'], 'ip_address'=>$request->ip(), 'page_action'=>'Login', 'log_info'=>'Login details are not valid'];
                ActivityLog::addActivityLog($activityLog);

                return redirect('login')->withErrors('Login details are not valid');
            }
        }
    }
}
