<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MssqlProviders extends Model
{
    const UNSUBSCRIBED_SMS_STATUS = 'Inactive - Unsubscribed';
    const BAD_PHONE_NUMBER_SMS_STATUS = 'Inactive - Bad phone number';

    protected $guarded = [];
}
