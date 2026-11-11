<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TwilioErrorLogs extends Model
{
    const UNSUBSCRIBED_PHONE_CODE = 21610;
    const BAD_PHONE_CODES = [21211, 21408, 30003, 30005, 30006, 30008];
    const UNSUCCESS_CODE = 0;

    protected $table = 'twilio_error_logs';
}
