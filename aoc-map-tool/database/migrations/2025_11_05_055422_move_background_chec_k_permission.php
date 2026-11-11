<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('
            update permissions set `group`= "IPEA", name = "Retry Background Check"
            where route_name = "first-advantage.retry"
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('
            update permissions set `group`= "Background Check", name = "Retry Logic"
            where route_name = "first-advantage.retry"
        ');
    }
};
