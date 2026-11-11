<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendLoginOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Services\OTP\OtpService;
use HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\ExternalUsers;
use App\Models\ExternalUserPatients;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Validator;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helper;
use App\Models\Device;
use App\Models\MobileOtpLog;
use App\Models\User;
class LoginController extends Controller
{
    //
    private $maxLoginAttempts = 3;

    public function __construct(protected OtpService $otpService)
    {
    }

    public function login(Request $request)
    {
        $validation = $this->validateLoginRequest($request->all());

        if ($validation->fails()) {
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], $validation->errors()->all());
        }
        if ($request->all()) {
            $requestData = $request->all();
            $email = $requestData['email'];
            $password = $requestData['password'];
            $user = ExternalUsers::where('email', $email)->where('status', 1)->first();
            $userResponse = $patientList=[];
            if ($user) {
                if (Hash::check($password, $user->password)) {
                    Device::doDeviceOperation(['user_id'=>$user->id,'device_id'=>$requestData['device_id']]);
                    $userDetails = ExternalUsers::doPostLogOperation(['id'=>$user->id]);
                    //$user['token'] = Helper::encrypt($deviceDetails->token);
                    $userResponse['id'] = $userDetails->id;
                    $userResponse['name'] = $userDetails->name;
                    $userResponse['email'] = $userDetails->email;
                    $userResponse['user_id'] = $userDetails->user_id;
                    $userResponse['aoc_provider_id'] = $userDetails->aoc_provider_id;
                    $userResponse['status'] = $userDetails->status;
                    $userResponse['token'] = $userDetails->token;
                    if($userDetails->aoc_provider_id == 0) {
                        $patientList = ExternalUserPatients::getPatientList($userDetails->id);
                    }
                    $userResponse['patients'] = $patientList;
                    return Helper::response(config('constants.response_code.OK'), ['user' => $userResponse], __('messages.login_successful'),2);
                } else {
                    return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.login_invalid'));
                }
            } else {
                return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.login_invalid'));
            }
        }
    }

    private function validateLoginRequest($request)
    {
        return Validator::make($request,
                [
                    'email' => 'required',
                    'password' => 'required',
                ],
                [
                    'email' => 'Email is required',
                    'password' => 'Password is required',
                ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            ExternalUsers::doLogoutOperation($request->user());
            return response()->json(['message' => __('messages.logout_successful')]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'message' => __('messages.logout_failed')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function sendLoginOtp(SendLoginOtpRequest $request)
    {
        try {
            //--------New OTP functionality--------------
            if (config('otp.refactor_enabled')) {
                $dto = $request->toDTO();
               return $this->otpService->requestOtp($dto);
            }
            //----------------------------

            if(isset($request->mobile_number) && isset($request->email)) {
                $formattedMobileNumber = MobileOtpLog::getFormatPhone($request->all());
                $request->request->add(['mobile_number'=> $formattedMobileNumber]);
                $user = ExternalUsers::where('email', $request->email)->where('mobile_number', $request->mobile_number)->first();
                if ($user) {
                    if ($user->status == 1) {
                        $smsResult = ['success' => true, 'error' => null];
                        $emailResult = ['success' => true, 'error' => null];

                        if(isset($request->mobile_number)) {
                            $otpPin = MobileOtpLog::generateMobileOtp($request->mobile_number);
                            $mobileRequest = ['otp'=>$otpPin,'mobile_number' => $request->mobile_number];
                            $smsRequest = ['msg'=> "Angels of Care: Here's your AOC Mobile App one-time passcode: ".$otpPin,'to' => $request->mobile_number];
                            $smsResult = MobileOtpLog::sendSMS($smsRequest);
                            if ($smsResult['success']) {
                                MobileOtpLog::saveOtp($mobileRequest);
                            }
                        }
                        if(isset($request->email)) {
                            $otpPin = MobileOtpLog::generateOtp();
                            $firstName = !empty($user->name) ? explode(' ', $user->name)[0] : 'User';
                            $sendEmailRequest = ['otp'=> $otpPin,'email' => $request->email, 'first_name' => $firstName];
                            $emailResult = MobileOtpLog::sendEmailOtp($sendEmailRequest);
                            if ($emailResult['success']) {
                                $emailRequest = ['otp'=>$otpPin,'email' => $request->email];
                                MobileOtpLog::saveOtp($emailRequest);
                            }
                        }

                        $error = Helper::formatOtpErrorResponse($smsResult, $emailResult);
                        if ($error) {
                            return Helper::response($error['code'], [], $error['message']);
                        }

                        return Helper::response(config('constants.response_code.OK'), [], __('messages.otp_sent_email_mobile'));
                    } else {
                        Helper::ExcelLog('Your account is disabled for '.$user->id, 'sendLoginOtp.log');
                        return Helper::response(config('constants.response_code.ACCOUNT_DISABLED'), [], __('messages.account_disabled'));
                    }
                } else {
                    return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.login_invalid'));
                }
            } else {
                //Resend OTP
                if(isset($request->mobile_number)) {
                    $formattedMobileNumber = MobileOtpLog::getFormatPhone($request->all());
                    $request->request->add(['mobile_number'=> $formattedMobileNumber]);
                    $user = ExternalUsers::where('mobile_number', $request->mobile_number)->first();
                    if ($user) {
                        if ($user->status == 1) {
                            $otpPin = MobileOtpLog::generateMobileOtp($request->mobile_number);
                            $mobileRequest = ['otp'=>$otpPin,'mobile_number' => $request->mobile_number];
                            $smsRequest = ['msg'=> "Angels of Care: Here's your AOC Mobile App one-time passcode: ".$otpPin,'to' => $request->mobile_number];
                            $smsResult = MobileOtpLog::sendSMS($smsRequest);

                            if (!$smsResult['success']) {
                                return Helper::response(
                                    config('constants.response_code.SOMETHING_WENT_WRONG'),
                                    [],
                                );
                            }

                            MobileOtpLog::saveOtp($mobileRequest);
                            return Helper::response(config('constants.response_code.OK'), [], __('messages.otp_sent_mobile'));
                        } else {
                            Helper::ExcelLog('Your account is disabled for '.$user->id, 'sendLoginOtp.log');
                            return Helper::response(config('constants.response_code.ACCOUNT_DISABLED'), [], __('messages.account_disabled'));
                        }
                    } else {
                        return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.login_invalid'));
                    }
                }
                if(isset($request->email)) {
                    $user = ExternalUsers::where('email', $request->email)->first();
                    if ($user) {
                        if ($user->status == 1) {
                            $otpPin = MobileOtpLog::generateEmailOtp($request->email);
                            $firstName = !empty($user->name) ? explode(' ', $user->name)[0] : 'User';
                            $sendEmailRequest = ['otp'=> $otpPin,'email' => $request->email, 'first_name' => $firstName];
                            $emailResult = MobileOtpLog::sendEmailOtp($sendEmailRequest);

                            if (!$emailResult['success']) {
                                return Helper::response(
                                    config('constants.response_code.SOMETHING_WENT_WRONG'),
                                    [],
                                    sprintf('Email Error: %s', $emailResult['error'])
                                );
                            }

                            $emailRequest = ['otp'=>$otpPin,'email' => $request->email];
                            MobileOtpLog::saveOtp($emailRequest);
                            return Helper::response(config('constants.response_code.OK'), [], __('messages.otp_sent_email'));
                        } else {
                            Helper::ExcelLog('Your account is disabled for '.$user->id, 'sendLoginOtp.log');
                            return Helper::response(config('constants.response_code.ACCOUNT_DISABLED'), [], __('messages.account_disabled'));
                        }
                    } else {
                        return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.login_invalid'));
                    }
                }
            }
        } catch (\Throwable $th) {
            Log::error('sendLoginOtp Error: ' . $th->getMessage());
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }

    public function verifyLoginOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            //--------New OTP functionality--------------
            if (config('otp.refactor_enabled')) {
                $dto = $request->toDTO();
                return $this->otpService->verifyOtp($dto);
            }
            //-------------------------------------------

            if(isset($request->email)) {
                $user = ExternalUsers::where('email', $request->email)->where('status', 1)->first();
                $patientList=[];
                if ($user) {
                    $checkTime = date('Y-m-d H:i:s', (time() - 60 * 5));
                    $verifyEmailOtp = MobileOtpLog::where('email', $request->email)->where('otp', $request->email_otp)->whereNull('verified_at')->where('send_at', '>=',$checkTime)->orderBy('send_at', 'desc')->first();
                    if(empty($verifyEmailOtp)) {
                        MobileOtpLog::where('email', $request->email)
                        ->whereNull('verified_at')
                        ->update(['attempt_count' => \DB::raw('attempt_count + 1')]);
                        $verifyEmailDetails = MobileOtpLog::where('email', $request->email)->whereNull('verified_at')->orderBy('send_at', 'desc')->first();
                        if ($verifyEmailDetails->attempt_count >= $this->maxLoginAttempts) {
                            ExternalUsers::where('email', $request->email)->where('status', 1)->update(['status' => 0]);
                            return Helper::response(config('constants.response_code.MAX_LOGIN_ATTEMPT'), [], __('messages.max_login_attempts'));
                        }else{
                            return Helper::response(config('constants.response_code.OK'), [], __('messages.otp_invalid_email'));
                        }
                    }
                    MobileOtpLog::where('email', $request->email)->where('otp', $request->email_otp)->whereNull('verified_at')->orderBy('send_at', 'desc')->update(['verified_at' => now()]);
                    return Helper::response(config('constants.response_code.OK'),[], __('messages.otp_verified'));
                } else {
                    return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.details_invalid'));
                }
            } elseif(isset($request->mobile_number)) {
                $formattedMobileNumber = MobileOtpLog::getFormatPhone($request->all());
                $request->request->add(['mobile_number'=> $formattedMobileNumber]);
                $user = ExternalUsers::where('mobile_number', $request->mobile_number)->where('status', 1)->first();
                $patientList=[];
                if ($user) {
                    $checkTime = date('Y-m-d H:i:s', (time() - 60 * 5));
                    $verifyMobileOtp = MobileOtpLog::where('mobile_number', $request->mobile_number)
                        ->where('otp', $request->mobile_otp)
                        ->whereNull('verified_at')
                        ->where('send_at', '>=',$checkTime)
                        ->orderBy('send_at', 'desc')
                        ->first();

                    if(empty($verifyMobileOtp)) {
                        MobileOtpLog::where('mobile_number', $request->mobile_number)
                        ->whereNull('verified_at')->orderBy('send_at', 'desc')
                        ->update(['attempt_count' => \DB::raw('attempt_count+1')]);
                        $verifyMobileDetails = MobileOtpLog::where('mobile_number', $request->mobile_number)->whereNull('verified_at')->orderBy('send_at', 'desc')->first();
                        if ($verifyMobileDetails->attempt_count >= $this->maxLoginAttempts) {
                            ExternalUsers::where('mobile_number', $request->mobile_number)->where('status', 1)->update(['status' => 0]);
                            return Helper::response(config('constants.response_code.MAX_LOGIN_ATTEMPT'), [], __('messages.max_login_attempts'));
                        }else{
                            return Helper::response(config('constants.response_code.OK'), [], __('messages.otp_invalid_mobile'));
                        }
                    }
                    MobileOtpLog::where('mobile_number', $request->mobile_number)->where('otp', $request->mobile_otp)->whereNull('verified_at')->orderBy('send_at', 'desc')->update(['verified_at' => now()]);
                    $userDetails = ExternalUsers::doPostLogOperation(['id'=>$user->id]);
                    //$user['token'] = Helper::encrypt($deviceDetails->token);
                    $userResponse['id'] = $userDetails->id;
                    $userResponse['name'] = $userDetails->name;
                    $userResponse['email'] = $userDetails->email;
                    $userResponse['user_id'] = $userDetails->user_id;
                    $userResponse['aoc_provider_id'] = $userDetails->aoc_provider_id;
                    $userResponse['status'] = $userDetails->status;
                    $userResponse['token'] = $userDetails->token;
                    if($userDetails->aoc_provider_id == 0) {
                        $patientList = ExternalUserPatients::getPatientList($userDetails->id);
                        $careTeamIds = ['rdod_id'=>'Regional Director of Operations',
                        'dbo_id'=>'Director of Branch Operations',
                        'ccc_id'=>'Client Care Coordinator',
                        'ic_id'=>'Intake Coordinator',
                        'r_id'=>'Recruiter',
                        'ccm_id'=>'Clinical Care Manager'];
                        if(!empty($patientList)) {
                            $pcnt = 0;
                            foreach($patientList as $patientData) {
                                $careTeam = [];
                                $i=0;
                                foreach($careTeamIds as $k => $v){
                                    if($patientData[$k] != '' && $patientData[$k] != 1){
                                        $user = User::select('users.*')
                                        ->where('id',$patientData[$k])->first();
                                        if(!empty($user)){
                                            $careTeam[$i]['designation'] = $v;
                                            $careTeam[$i]['profilePic'] = $user->profile_picture;
                                            $careTeam[$i]['name'] = $user->name;
                                            $i++;
                                        }
                                    }
                                }
                                $patientList[$pcnt]['care_team'] = $careTeam;
                                $pcnt++;
                            }
                        }
                    }
                    $userResponse['patients'] = $patientList;
                    $userResponse['valid_attemp_counts'] = $this->maxLoginAttempts;
                    return Helper::response(config('constants.response_code.OK'), $userResponse, __('messages.otp_verified'));
                } else {
                    return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.details_invalid'));
                }
            } else {
                Log::error('verifyLoginOtp ELSE2');
                return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
            }
        } catch (\Throwable $th) {
            Log::error('verifyLoginOtp Error: ' . $th->getMessage());
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }
}
