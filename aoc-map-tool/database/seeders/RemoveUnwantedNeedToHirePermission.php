<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RemoveUnwantedNeedToHirePermission extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('permissions')->where('name', 'Need To Hire Report')->where('route_name', 'nth-report')->delete();
    }
}
