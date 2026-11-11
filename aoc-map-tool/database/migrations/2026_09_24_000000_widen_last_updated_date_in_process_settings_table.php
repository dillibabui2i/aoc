<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Widen from CHAR(19) ("Y-m-d H:i:s") to fit microsecond precision
        // ("Y-m-d H:i:s.u", 26 chars) needed for daas_schedules cursor pagination.
        Schema::table('process_settings', function (Blueprint $table) {
            $table->char('last_updated_date', 26)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('process_settings', function (Blueprint $table) {
            $table->char('last_updated_date', 19)->change();
        });
    }
};
