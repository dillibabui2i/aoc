<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoreSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('core_setting')->updateOrInsert([
            'opt' => 'mfa',
            'val' => 'false',
        ]);
        DB::table('core_setting')->updateOrInsert([
            'opt' => 'mfa_otp_title',
            'val' => 'Multi-factor Authentication',
        ]);
        DB::table('core_setting')->updateOrInsert([
            'opt' => 'mfa_otp_content',
            'val' => '<p>Please enter the <strong>One Time Code</strong> generated on your Authenticator application.  Make sure you click on Submit before the code refreshes.</p>',
        ]);
        DB::table('core_setting')->updateOrInsert([
            'opt' => 'mfa_setup_title',
            'val' => 'Setup Google Authenticator',
        ]);
        DB::table('core_setting')->updateOrInsert([
            'opt' => 'mfa_setup_content',
            'val' => '<p>Set up your Multi-factor authentication by scanning the QR code in your preferred Authenticator application.</p><p>Alternatively, you can manually add the code below. Once you have added the account in your Authenticator, click on the Complete Registration button so that you can log in.</p>',
        ]);
        DB::table('core_setting')->updateOrInsert([
            'opt' => 'login_title',
            'val' => 'ANGELS OF CARE',
        ]);
        DB::table('core_setting')->updateOrInsert([
            'opt' => 'login_content',
            'val' => '<p style="text-align: left;"><span style="font-size: 1.5em;">H</span>eart: Our clients are the <strong>HEART</strong> of everything we do.</p>
            <p style="text-align: left;"><span style="font-size: 1.5em;">A</span>dvocacy: We will <strong>ADVOCATE</strong> for our clients, industry, providers, and compliance.</p>
            <p style="text-align: left;"><span style="font-size: 1.5em;">L</span>ove: We <strong>LOVE</strong> our employees and will go above and beyond for them.</p>
            <p style="text-align: left;"><span style="font-size: 1.5em;">O</span>utreach: We will have <strong>OUTREACH</strong> into the special needs community</p>
            <p style="text-align: left;"><span style="font-size: 1.5em;">S</span>peed: We will act with <strong>SPEED</strong> to ensure our employees, clients and families are taken care of with the upmost priority</p>',
        ]);
    }
}
