<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        DB::table('renewables')
            ->where('is_deleted', '1')
            ->delete();
    }

    public function down()
    {
    }
};
