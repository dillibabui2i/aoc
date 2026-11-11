<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            [
                'name' => 'Super Admin',
            ],
            [
                'name' => 'Admin',
            ],
            [
                'name' => 'Map User',
            ],
        ];
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('roles')->truncate();
        DB::table('roles')->insert($roles);

        $finalRoleIds = [];
        $roles = DB::table('roles')->get();
        foreach ($roles as $key => $value) {
            $finalRoleIds[$value->name] = $value->id;
        }

        $permissions = [
            [
                'group' => 'Manage Users',
                'name' => 'Users Grid',
                'route_name' => 'manageusers',
                'related_routes' => 'getuserlistajax',
            ],
            [
                'group' => 'Manage Users',
                'name' => 'Add New User',
                'route_name' => 'manageusers.add',
                'related_routes' => 'manageusers.saveuser',
            ],
            [
                'group' => 'Manage Users',
                'name' => 'Edit User',
                'route_name' => 'manageusers.edit',
                'related_routes' => 'manageusers.updateuser',
            ],
            [
                'group' => 'Manage Users',
                'name' => 'Remove User',
                'route_name' => 'manageusers.destroy',
                'related_routes' => '',
            ],
            [
                'group' => 'Manage Users',
                'name' => 'Disable User',
                'route_name' => 'manageusers.disable',
                'related_routes' => '',
            ],
            [
                'group' => 'Manage Users',
                'name' => 'Send User Password Email',
                'route_name' => 'manageusers.sendpasswordemail',
                'related_routes' => '',
            ],
            [
                'group' => 'Core Settings',
                'name' => 'Core Settings',
                'route_name' => 'coresetting.setting',
                'related_routes' => 'coresetting.save',
            ],
            [
                'group' => 'Manage Maps',
                'name' => 'Maps Grid',
                'route_name' => 'managemap',
                'related_routes' => 'getmaplistajax',
            ],
            [
                'group' => 'Manage Maps',
                'name' => 'Add New Map',
                'route_name' => 'managemap.add',
                'related_routes' => 'managemap.savemap',
            ],
            [
                'group' => 'Manage Maps',
                'name' => 'Map Status',
                'route_name' => 'managemap.status',
                'related_routes' => '',
            ],
            [
                'group' => 'Manage Maps',
                'name' => 'Edit Map',
                'route_name' => 'managemap.editmap',
                'related_routes' => 'managemap.savemap',
            ],
            [
                'group' => 'Map',
                'name' => 'Choose Map',
                'route_name' => 'map.choosemap',
                'related_routes' => 'searchlocationdata,map.resetfilters,map.getmainlocations,map.getlocationdata,map.json,map.table,map.downloaddata,map.map,map.mapIntro',
            ],
            [
                'group' => 'Map',
                'name' => 'Map Import',
                'route_name' => 'map.import',
                'related_routes' => 'map.importdata',
            ],
            [
                'group' => 'Map',
                'name' => 'Map Configuration',
                'route_name' => 'map.configurations',
                'related_routes' => 'map.configuredata',
            ],
            [
                'group' => 'Manage Roles',
                'name' => 'Roles Grid',
                'route_name' => 'manageroles',
                'related_routes' => 'getroleslistajax',
            ],
            [
                'group' => 'Manage Roles',
                'name' => 'Add Role',
                'route_name' => 'manageroles.add',
                'related_routes' => 'manageroles.saverole',
            ],
            [
                'group' => 'Manage Roles',
                'name' => 'Edit Role',
                'route_name' => 'manageroles.editrole',
                'related_routes' => 'manageroles.saverole',
            ],

        ];
        DB::table('permissions')->truncate();
        DB::table('permissions')->insert($permissions);

        $finalPermissionsIds = [];
        $permissions = DB::table('permissions')->get();
        foreach ($permissions as $key => $value) {
            $finalPermissionsIds[$value->route_name] = $value->id;
        }
        DB::table('role_has_permissions')->truncate();
        // Admin Roles
        $data = [];
        foreach ($finalPermissionsIds as $key => $value) {
            if (in_array($key, ['coresetting.setting', 'manageroles', 'manageroles.add', 'manageroles.editrole'])) {
                continue;
            } else {
                $data[] = ['permissions_id' => $value, 'roles_id' => $finalRoleIds['Admin']];
            }
        }
        DB::table('role_has_permissions')->insert($data);

        // User Roles
        $data = [];
        foreach ($finalPermissionsIds as $key => $value) {
            if (in_array($key, ['manageusers', 'manageusers.add', 'manageusers.edit', 'manageusers.destroy', 'manageusers.disable', 'manageusers.sendpasswordemail', 'managemap', 'managemap.add', 'managemap.status', 'managemap.editmap', 'coresetting.setting', 'map.import', 'map.configurations', 'manageroles', 'manageroles.add', 'manageroles.editrole'])) {
                continue;
            } else {
                $data[] = ['permissions_id' => $value, 'roles_id' => $finalRoleIds['Map User']];
            }
        }
        DB::table('role_has_permissions')->insert($data);
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
