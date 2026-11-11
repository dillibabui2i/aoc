<?php

use Illuminate\Database\Seeder;

class MapColdCallingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('map_configurations')->insert([
            'map_tablename' => 'cold_calling',
            'map_name' => 'Cold Calling',

        ]);
    }
}
