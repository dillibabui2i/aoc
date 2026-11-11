<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class TempDeleteDuplicatedLOBSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //need to delete specific duplicated record made by mistake by user
        DB::table('intake')->where('id', 141196)->delete();
    }
}
