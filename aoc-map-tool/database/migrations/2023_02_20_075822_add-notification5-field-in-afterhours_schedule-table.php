<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotification5FieldInAfterhoursScheduleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('afterhours_schedule', function (Blueprint $table) {
            $table->tinyInteger('notification5')->default(0)->nullable()->comment('Before 1 hour');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('afterhours_schedule', function (Blueprint $table) {
            $table->dropColumn('notification5');
        });
    }
}
