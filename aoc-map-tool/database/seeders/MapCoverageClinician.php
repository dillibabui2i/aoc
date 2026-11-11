<?php

use Illuminate\Database\Seeder;

class MapCoverageClinician extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('map_configurations')->insert([
            'map_tablename' => 'coverage_clinician',
            'map_name' => 'Coverage Clinician',
        ]);
    }
}
