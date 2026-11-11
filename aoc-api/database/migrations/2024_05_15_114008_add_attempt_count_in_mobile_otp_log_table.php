<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAttemptCountInMobileOtpLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mobile_otp_log', function (Blueprint $table) {
            $table->tinyInteger('attempt_count')->default(0)->after('otp');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mobile_otp_log', function (Blueprint $table) {
            $table->dropColumn('attempt_count');
        });
    }
}
