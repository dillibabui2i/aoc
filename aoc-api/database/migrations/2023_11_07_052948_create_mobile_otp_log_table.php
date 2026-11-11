<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMobileOtpLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mobile_otp_log', function (Blueprint $table) {
            $table->id();
            $table->string('mobile_number');
            $table->string('email');
            $table->string('otp',6);
            $table->dateTime('send_at')->nullable();
            $table->dateTime('verified_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mobile_otp_log');
    }
}
