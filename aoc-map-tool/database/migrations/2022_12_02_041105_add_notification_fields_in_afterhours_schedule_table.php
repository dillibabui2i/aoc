<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotificationFieldsInAfterhoursScheduleTable extends Migration
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
            $table->renameColumn('is_notification_sent', 'notification1');
            $table->tinyInteger('notification2')->default(0)->comment('72 hours notification sent');
            $table->tinyInteger('notification3')->default(0);
            $table->tinyInteger('notification4')->default(0);
        });
        Schema::table('afterhours_schedule', function (Blueprint $table) {
            //
            DB::statement("ALTER TABLE `afterhours_schedule` CHANGE `notification1` `notification1` TINYINT(4) NOT NULL DEFAULT '0' COMMENT '24 hours notification sent';");
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
            $table->renameColumn('notification1', 'is_notification_sent');
            $table->dropColumn('notification2');
            $table->dropColumn('notification3');
            $table->dropColumn('notification4');
        });
        Schema::table('afterhours_schedule', function (Blueprint $table) {
            //
            DB::statement("ALTER TABLE `afterhours_schedule` CHANGE `is_notification_sent` `is_notification_sent` TINYINT(4) NOT NULL DEFAULT '0' COMMENT '0-Not sent, 1-72 hours notification sent,2-24 hours notification sent';");
        });
    }
}
