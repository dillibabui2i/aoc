<?php

namespace Database\Seeders;

use App\Helpers\H;
use App\Permissions;
use App\Roles;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecruiterSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permissions::where('name', 'Receive NTH Schedule Change Notifications')->first();
        $role = Roles::where('name', 'Recruiter')->first();

        DB::table('role_has_permissions')->insert([
            'permissions_id' => $permission->id,
            'roles_id' => $role->id,
        ]);
    }
}
