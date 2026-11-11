<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Twilio\Jwt\AccessToken;
use Twilio\Rest\Client;
use Twilio\Jwt\Grants\ChatGrant;
use App\Helpers\Helper;
use Throwable;
class MobileOtpLog extends Model
{
    use HasFactory;
    protected $table = 'mobile_otp_log';
    public $timestamps = false;
    protected $fillable = [
        'mobile_number',
        'email',
        'otp',
        'send_at',
        'verified_at',
        'attempt_count',
        'cooldown_until',
        'resend_count',
        'invalidated_at',
        'invalidated_reason',
        'expires_at',
    ];

    static function generateOtp(){
        return rand(1000, 9999);
    }

    static function saveOtp($request){
        $otpLog = new MobileOtpLog();
        if(isset($request['mobile_number'])) {
            $mobileDetail = MobileOtpLog::where('mobile_number', $request['mobile_number'])->first();
            if(!empty($mobileDetail)) {
                $otpLog= MobileOtpLog::find($mobileDetail->id);
            }
            $otpLog->mobile_number=$request['mobile_number']??'';
        }

        if(isset($request['email'])) {
            $emailDetail = MobileOtpLog::where('email', $request['email'])->first();
            if(!empty($emailDetail)) {
                $otpLog= MobileOtpLog::find($emailDetail->id);
            }
            $otpLog->email=$request['email']??'';
        }
        $otpLog->attempt_count = 0;
        $otpLog->otp=$request['otp'];
        $otpLog->send_at=now();
        $otpLog->verified_at=null;
        $otpLog->save();
    }

    static function sendSMS($data)
    {
        try {
            $sid = env("TWILIO_ACCOUNT_SID");
            $token = env("TWILIO_AUTH_TOKEN");
            $twilio = new Client($sid, $token);

            $messageParams = [
                'body' => $data['msg']
            ];

            // Use messaging service if available, otherwise use direct phone number
            $campaignSid = env("TWILIO_CAMPAIGN_SID");
            if (!empty($campaignSid)) {
                $messageParams['messagingServiceSid'] = $campaignSid;
            } else {
                $messageParams['from'] = env("TWILIO_TOLL_FREE");
            }

            $message = $twilio->messages->create(
                $data['to'],
                $messageParams
            );

            if ($message && $message->sid) {
                return ['success' => true, 'error' => null];
            }

            $errorMsg = 'No message SID returned from Twilio';
            Log::error('sendSMS Failed: '.$errorMsg);
            return ['success' => false, 'error' => $errorMsg];
        } catch (Throwable $th) {
            $errorMsg = $th->getMessage();
            Log::error('sendSMS Throwable='.$errorMsg);
            return ['success' => false, 'error' => $errorMsg];
        }
    }

    static function sendEmailOtp($data)
    {
        try {
            $data['email'] = $data['email'];
            $data['from_email'] = env('MAIL_FROM_ADDRESS');
            $data['subject'] = 'Your Angels of Care Mobile App Login Code';
            $data['otp_code'] = $data['otp'];
            $data['first_name'] = $data['first_name'] ?? 'User';

            Mail::send(['html' => 'email.login_otp', 'text' => 'email.login_otp_plain'], $data, function ($message) use ($data) {
                $message->from($data['from_email'], env('MAIL_FROM_NAME'));
                $message->subject($data['subject']);
                $message->to($data['email']);
            });

            return ['success' => true, 'error' => null];
        } catch (TransportExceptionInterface $e) {
            Log::error('sendEmailOtp Failed: '. $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        } catch (Throwable $th) {
            $errorMsg = $th->getMessage();
            Log::error('sendEmailOtp Throwable='.$errorMsg);
            return ['success' => false, 'error' => $errorMsg];
        }
    }
    static function getFormatPhoneForUS($phone_number)
    {
        $formatedPhoneNumber = preg_replace("/[^0-9]/", '', $phone_number);
        if ($formatedPhoneNumber == '0000000000' || $formatedPhoneNumber == '10000000000') {
            return '';
        }
        if (strlen($formatedPhoneNumber) == 10 && $formatedPhoneNumber != '0000000000') {
            $formatedPhoneNumber = "+1".$formatedPhoneNumber;
        } elseif (strlen($formatedPhoneNumber) == 11 && $formatedPhoneNumber != '10000000000') {
            $formatedPhoneNumber = "+".$formatedPhoneNumber;
        }
        return $formatedPhoneNumber;
    }

    static function getFormatPhone($request)
    {
        $localMobileNumber = ['9913614006','9228646121','9714111577','7623015882','9998771268','9429963976','9173820935'];
        $thirdPartyNumber = ['34603651190','48452620731'];
        if(in_array($request['mobile_number'], $localMobileNumber)) {
            $mobile_number='+91'.$request['mobile_number'];
        } elseif(in_array($request['mobile_number'], $thirdPartyNumber)) {
            $mobile_number='+'.$request['mobile_number'];
        } else {
            $formattedMobileNumber = MobileOtpLog::getFormatPhoneForUS($request['mobile_number']);
            $mobile_number=$formattedMobileNumber;
        }
        return  $mobile_number;
    }
    static function generateMobileOtp($mobile_number){
        $localMobileNumber = ['+919913614006','+919228646121','+919714111577','+917623015882','+919998771268','+919429963976','+919173820935',
        '+15551233456',
        '+15550123456',
        '+34603651190',
        '+48452620731'
        ];
        if(in_array($mobile_number, $localMobileNumber)) {
            return '1234';
        }else{
            return rand(1000, 9999);
        }
    }

    static function generateEmailOtp($email){
        $fixEmails = ['testuser1@excelss.com','testuser2@excelss.com','nik+dev@excelss.com','sagar.v@eheuristic.com','chernyshov.stas@gmail.com','tayadorog@gmail.com'];
        if(in_array($email, $fixEmails)) {
            return '5678';
        }else{
            return rand(1000, 9999);
        }
    }
}
