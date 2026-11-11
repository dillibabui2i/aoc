<?php

namespace Database\Seeders;

use App\Permissions;
use Illuminate\Database\Seeder;

class TherapyWhiteboardPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        foreach ($this->permissionsList() as $permission) {
            Permissions::updateOrCreate(['name' => $permission['name']], $permission);
        }
    }

    private function permissionsList(): array
    {
        return [
            [
                'group' => 'Reporting',
                'name' => 'Therapy Whiteboard',
                'route_name' => 'therapy.whiteboard.index',
                'related_routes' => 'therapy.whiteboard.index',
            ]
        ];
    }
}
