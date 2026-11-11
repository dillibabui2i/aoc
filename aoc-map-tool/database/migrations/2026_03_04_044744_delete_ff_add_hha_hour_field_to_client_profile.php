<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {

    public function up()
    {
        DB::table('feature_flags')
            ->where('flag', 'add-hha-hour-field-to-client-profile')
            ->delete();
    }

    public function down()
    {
        DB::table('feature_flags')->insert([
            'flag' => 'add-hha-hour-field-to-client-profile',
            'flag_description' => 'add hha hour field to client profile',
            'users' => [],
            'ips' => null,
            'created_by' => 'Nihar',
        ]);
    }

};
