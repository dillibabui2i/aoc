<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up()
    {
        // Remove Excelss domain-level access from existing feature flags.
        // The `*@excelss.com` token allowed feature flag access for users from the Excelss email domain.
        DB::statement("
            UPDATE feature_flags
            SET users = TRIM(BOTH ',' FROM REPLACE(CONCAT(',', users, ','), ',*@excelss.com,', ','))
            WHERE CONCAT(',', users, ',') LIKE '%,*@excelss.com,%'
        ");

        DB::table('users')
            ->where('email', 'lynn@excelss.com')
            ->update([
                'email' => 'aocappalerts@angelsofcare.com',
            ]);

        DB::table('users')
            ->where('email', 'sutariyapiyush.v@excelss.com')
            ->delete();


    }

    public function down()
    {
        // No rollback needed.
    }

};
