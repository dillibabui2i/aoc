<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

class CacheHelper
{
    public function forgetUserPermissionsCache(int $userId): void
    {
        Cache::tags(['{roles_permissions_cache}'])
            ->forget('user_' . $userId . '_roles_permissions');

        Cache::tags(['{user_permissions_cache}'])
            ->forget('user_' . $userId . '_permissions');

        Cache::tags(['{permissions_cache}'])
            ->forget('user_' . $userId . 'assigned_permissions');

        Cache::tags(['{roles_cache}'])
            ->forget('user_' . $userId . '_roles');
    }
}
