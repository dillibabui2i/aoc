<?php
namespace App\Helpers;
use App\Enums\FeatureFlagStatusesEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\FeatureFlags;
use Illuminate\Support\Facades\Log;

class LD
{
    /**
     * The function check the given feature flag is enable or disable and if enable then
     * check the validation for passed email,ip,firstname...etc.
     *
     * @param string    $flageNameKey Flag key given by LD
     * @param string    $userEmail  This is unique email for user sagments in the LD (optional)
     * @param array     $validatorArr Validator array with key like email,ip,firstname (optional)
     *
     * @throws \Exception If passing keys are not matched with available in LDUserBuilder in the validatorArr
     * @return bool
     */

    /**
     * The function check the given feature flag is enable or disable and if enable then
     * check the validation for passed email,ip,firstname...etc.
     *
     * @param string    $flagNameKey Flag key given by LD
     * @param string    $userEmail  This is unique email for user sagments in the LD (optional)
     * @param array     $validatorArr Validator array with key like email,ip,firstname (optional)
     *
     * @throws \Exception If passing keys are not matched with available in LDUserBuilder in the validatorArr
     * @return bool
     */
    public static function checkFeatureFlag(string $flagNameKey, string $userEmail = '', array $validatorArr = []): bool
    {
        try {
            if ($flagNameKey === '') {
                return false;
            }

            $flags = Cache::tags(['ff_cache'])->rememberForever('all', function () {
                return FeatureFlags::whereIn('status', [
                    FeatureFlagStatusesEnum::ENABLED->value,
                    FeatureFlagStatusesEnum::TARGETING_OFF->value,
                ])->get()->keyBy('flag')->toArray();
            });

            $flag = $flags[$flagNameKey] ?? null;

            if (!$flag) {
                return false;
            }

            if ($userEmail === '') {
                $loggedInUser = H::getLoggedInUser();
                if ($loggedInUser) {
                    $userEmail = $loggedInUser['email'];
                    $validatorArr['email'] = $loggedInUser['email'];
                    $validatorArr['name']  = $loggedInUser['name'];
                    $validatorArr['ip']    = $_SERVER['REMOTE_ADDR'];
                }
            }

            $domain = explode('@', $userEmail)[1];

            // ---------------------------------------------------------
            // Evaluate flag status
            // ---------------------------------------------------------

            // status = 0 not tracking
            if ($flag['status'] == 0) {
                return true;
            }

            // status = 1 - enabled
            if ($flag['status'] == 1) {

                $users = array_filter(array_map('trim', explode(',', $flag['users'])));

                // direct email or *@domain match
                foreach ($users as $user) {
                    if ($user === $userEmail || ($domain && $user === '*@' . $domain)) {
                        return true;
                    }
                }

                // If IP rules are enabled
                if (!empty($flag['ips']) && !empty($validatorArr['ip'])) {

                    $ipMatch = DB::table('activity_log')
                        ->select('id')
                        ->where('ip_address', $validatorArr['ip'])
                        ->whereIn('log_info', [
                            'Logged in using Microsoft',
                            'Login Credentials Successfull'
                        ])
                        ->whereRaw("instr('" . $flag['users'] . "', user_name) > 0");

                    $result = $ipMatch->first();

                    if (!empty($result->id)) {
                        return true;
                    }
                }

                return false;
            }

            return false;
        } catch (\Exception $e) {
            Log::channel('featureFlags')->error($flagNameKey . ' ERROR');
            Log::channel('featureFlags')->error($e->getMessage());

            return false;
        }
    }

    public static function checkFeatureFlagLoggedOut(string $flagNameKey): bool
    {
        try {
            if ($flagNameKey === '') {
                return false;
            }

            $flags = Cache::tags(['ff_cache'])->rememberForever('all', function () {
                return FeatureFlags::whereIn('status', [
                    FeatureFlagStatusesEnum::ENABLED->value,
                    FeatureFlagStatusesEnum::TARGETING_OFF->value,
                ])->get()->keyBy('flag')->toArray();
            });

            $flag = $flags[$flagNameKey] ?? null;

            if (!$flag) {
                return false;
            }

            if ($flag['status'] === 0) {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::channel('featureFlags')->error($flagNameKey . ' ERROR');
            Log::channel('featureFlags')->error($e->getMessage());

            return false;
        }
    }
}
