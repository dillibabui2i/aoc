<?php

namespace App\Helpers;

use App\LoginRestriction;
use Carbon\Carbon;

class LoginRestrictionHelper
{
    public function setIPBan($data)
    {
        try {
            $mytime = Carbon::now();
            $currentDateTime = $mytime->toDateTimeString();
            $loginRestriction = LoginRestriction::where('ipaddress', $data['ipaddress'])->first();

            if ($loginRestriction) {
                $loginRestriction->updated_at = $currentDateTime;
                $loginRestriction->save();
            } else {
                $data['created_at'] = $currentDateTime;
                $data['updated_at'] = $currentDateTime;
                $insert = LoginRestriction::insert($data);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function isIPBanned($ip)
    {
        if ($this->isIPWhitelisted($ip)) {
            return false;
        } else {
            $loginRestriction = LoginRestriction::where('ipaddress', $ip)->first();
            if ($loginRestriction) {
                if ($loginRestriction->blacklisted) {
                    return 1;
                } else {
                    $mytime = Carbon::now();
                    $currentDateTime = $mytime->toDateTimeString();

                    $dateTimeObject1 = date_create($currentDateTime);
                    $dateTimeObject2 = date_create($loginRestriction->updated_at);

                    $interval = date_diff($dateTimeObject1, $dateTimeObject2);

                    $interval->format('%R%a days');

                    $min = $interval->days * 24 * 60;
                    $min += $interval->h * 60;
                    $min += $interval->i;

                    if ($min < 60) {
                        return 1;
                    }
                }
            } else {
                return 0;
            }
        }
    }

    public function isIPWhitelisted($ip)
    {
        $loginRestriction = LoginRestriction::where('ipaddress', $ip)->first();
        if ($loginRestriction) {
            if ($loginRestriction->whitelisted) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
