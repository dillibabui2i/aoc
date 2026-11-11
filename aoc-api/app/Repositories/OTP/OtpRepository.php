<?php

namespace App\Repositories\OTP;

use App\DTO\CreateOtpLogDTO;
use App\Models\MobileOtpLog;

class OtpRepository
{
    /**
     * @param CreateOtpLogDTO $otp
     * @return MobileOtpLog
     */
    public function saveOtpLog(CreateOtpLogDTO $otp): MobileOtpLog
    {
        return MobileOtpLog::create([
            'mobile_number' => $otp->mobileNumber,
            'email' => $otp->email,
            'otp' => $otp->otp,
            'send_at' => $otp->sendAt,
            'expires_at' => $otp->expiresAt,
            'attempt_count' => $otp->attemptCount,
            'resend_count' => $otp->resendCount,
            'limit_events_count' => $otp->limitEventsCount,
            'invalidated_at' => null,
            'invalidated_reason' => null,
        ]);
    }
}
