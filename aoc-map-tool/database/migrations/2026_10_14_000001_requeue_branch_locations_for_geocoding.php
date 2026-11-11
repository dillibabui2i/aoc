<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::table('branch_locations')
            ->where('is_deleted', 0)
            ->update(['needs_geocoding' => 1]);
    }

    public function down()
    {
    }
};
