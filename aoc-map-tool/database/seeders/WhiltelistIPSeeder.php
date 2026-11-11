<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WhiltelistIPSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('core_setting')->updateOrInsert([
            'opt' => 'login_whitelists',
            'val' => '',
        ]);

        DB::table('core_setting')->updateOrInsert([
            'opt' => 'recaptcha_key',
            'val' => '',
        ]);

        DB::table('core_setting')->updateOrInsert([
            'opt' => 'recaptcha_secret',
            'val' => '',
        ]);

        DB::table('core_setting')->updateOrInsert([
            'opt' => 'login_notification_email',
            'val' => 'aocappalerts@angelsofcare.com',
        ]);
    }
}
