<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MapCoverageClinicianUpdateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('map_configurations')->where('map_id', 43)->update([
            'description' => 'All candidates in a status of Active, Active Coverage, and Standby are included in the Coverage Clinicians Map that have less than 32 hours on average over a 4 week period (the last 2 weeks, the current week, and next week) including billable shifts that are planned, in progress, completed, or approved.',
        ]);
    }
}
