<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSendNotificationFieldToAfterhoursShiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('afterhours_shifts', function (Blueprint $table) {
            $table->tinyInteger('send_notification')->nullable()->default(0)->after('repeat_on');
            $table->string('send_notification_duration')->nullable()->after('send_notification');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('afterhours_shifts', function (Blueprint $table) {
            $table->dropColumn('send_notification');
            $table->dropColumn('send_notification_duration');
        });
    }
}
