<?php

use Illuminate\Database\Seeder;

class MapTextBlastSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('map_configurations')->insert([
            'map_tablename' => 'text_blast',
            'map_name' => 'Text Blast',

        ]);
    }
}
