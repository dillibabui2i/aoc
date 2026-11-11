<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMiscFieldsInAfterhoursScheduleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('afterhours_schedule', function (Blueprint $table) {
            //
            $table->string('misc1', 255)->nullable()->default('');
            $table->string('misc2', 255)->nullable()->default('');
            $table->string('misc3', 255)->nullable()->default('');
            $table->tinyInteger('is_notification_sent')->default(0)->comment('0-Not sent, 1-72 hours notification sent,2-24 hours notification sent');
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
            //
            $table->dropColumn('misc1');
            $table->dropColumn('misc2');
            $table->dropColumn('misc3');
            $table->dropColumn('is_notification_sent');
        });
    }
}
