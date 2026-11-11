<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IntakeHideDuplicatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $ids = [132108, 132107, 116966, 126612, 145965];
        DB::table('intake')->whereIn('id', $ids)->update([
            'intake_completed' => 1
        ]);
    }
}
