<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScheduleTypes extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('schedule_types')->truncate();
        DB::table('schedule_types')->insert([
            ['type' => 'IPEA', 'sort_order'=>1],
            ['type' => 'CCR', 'sort_order'=>2],
            ['type' => 'PEPI', 'sort_order'=>3],
            ['type' => 'WEPI', 'sort_order'=>4],
            ['type' => 'Training Shift', 'sort_order'=>6],
            ['type' => 'Candidate Start', 'sort_order'=>5],
            ['type' => 'School Set Up', 'sort_order'=>7],
            ['type' => 'Needs to be Scheduled by RA', 'sort_order'=>8],
        ]);
    }
}
