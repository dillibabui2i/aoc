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
        Schema::table('twilio_error_logs', function (Blueprint $table) {
            $table->integer("map_id")->after("status")->nullable();
            $table->string('mobile_field_name', 255)->after("map_id")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('twilio_error_logs', function (Blueprint $table) {
            //
        });
    }
};
