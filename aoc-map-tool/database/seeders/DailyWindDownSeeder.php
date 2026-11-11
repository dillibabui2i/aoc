<?php

namespace Database\Seeders;

use App\Helpers\H;
use App\Permissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DailyWindDownSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = Permissions::create([
            'group' => 'Notification',
            'name' => 'Receive NTH Schedule Change Notifications',
            'route_name' => 'notification.list',
        ]);
        $userPermissionResolver = H::getHelper('UserPermissionResolver');
        $usersToMigrate = collect($userPermissionResolver->getUserByPermission('notification.daily-wind-down'))->pluck('id');
        $newPermissions = [];

        foreach ($usersToMigrate as $item) {
            $newPermissions[] = [
                'permissions_id' => $permissions->id,
                'user_id' => $item,
            ];
        }

        DB::table('user_has_permission')->insert($newPermissions);
    }
}
