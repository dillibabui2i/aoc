<?php

use Illuminate\Database\Seeder;

class DailyActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            [
                'group' => 'Dashboard',
                'name' => 'Daily Activity',
                'route_name' => 'dashboard.activitydata',
                'related_routes' => '',
            ],
        ];

        DB::table('permissions')->insert($permissions);
    }
}
