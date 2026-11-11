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
        Schema::create('call_logs_8x8', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->string('callId')->nullable();
            $table->integer('legId')->nullable();
            $table->bigInteger('startTimeUTC')->nullable();
            $table->dateTime('startTime')->nullable();
            $table->bigInteger('connectTimeUTC')->nullable();
            $table->dateTime('connectTime')->nullable();
            $table->bigInteger('disconnectedTimeUTC')->nullable();
            $table->dateTime('disconnectedTime')->nullable();
            $table->bigInteger('talkTimeMS')->nullable();
            $table->string('talkTime')->nullable();
            $table->string('caller')->nullable();
            $table->string('callerName')->nullable();
            $table->string('callee')->nullable();
            $table->string('calleeName')->nullable();
            $table->string('lra')->nullable();
            $table->string('direction')->nullable();
            $table->string('parentCallId')->nullable();
            $table->string('transferToCallId')->nullable();
            $table->string('dnis')->nullable();
            $table->string('status')->nullable();
            $table->string('callerDeviceId')->nullable();
            $table->string('calleeDeviceId')->nullable();
            $table->string('callerDeviceModel')->nullable();
            $table->string('calleeDeviceModel')->nullable();
            $table->string('callerId')->nullable();
            $table->string('missed')->nullable();
            $table->string('abandoned')->nullable();
            $table->string('answered')->nullable();
            $table->string('cause')->nullable();
            $table->string('callerSvcName')->nullable();
            $table->string('callerSvcType')->nullable();
            $table->string('calleeSvcName')->nullable();
            $table->string('calleeSvcType')->nullable();
            $table->integer('lraType')->nullable();
            $table->bigInteger('calleeHoldDurationMS')->nullable();
            $table->string('calleeHoldDuration')->nullable();
            $table->string('calleeDisconnectOnHold')->nullable();
            $table->string('callerDisconnectOnHold')->nullable();
            $table->string('pbxId')->nullable();
            $table->string('sipCallId')->nullable();
            $table->text('departments')->nullable();
            $table->text('branches')->nullable();
            $table->dateTime('recordServiceOn')->nullable();
            $table->dateTime('bargeServiceOn')->nullable();
            $table->string('masterSlaveExts')->nullable();
            $table->string('propsLastPartyDisp')->nullable();
            $table->string('accountCode')->nullable();
            $table->string('aaPath')->nullable();
            $table->integer('callTime')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('call_logs_8x8');
    }
};
