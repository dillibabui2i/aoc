<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TempInactivateUnwantedNTHSchedulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //need to update IsActive for specific legacy records
        Db::table('maps_nth')->where('id', 391)->update(['IsActive' => 0]);
        Db::table('NeedToHire_Accounts')->where('id', 9)->update(['IsActive' => 0]);
    }
}
