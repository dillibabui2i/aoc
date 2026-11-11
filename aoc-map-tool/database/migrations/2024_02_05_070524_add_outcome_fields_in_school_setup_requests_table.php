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
        Schema::table('school_setup_requests', function (Blueprint $table) {
            //
            $table->string('live_status', 255)->nullable()->after('status');
            $table->string('clearance_packet', 255)->nullable()->after('live_status');
            $table->string('clearance_packet_pending_nurse_reason', 255)->nullable()->after('clearance_packet');
            $table->string('finger_printing', 255)->nullable()->after('clearance_packet_pending_nurse_reason');
            $table->string('school_setup_status', 255)->nullable()->after('finger_printing');
            $table->string('status_cancelled_reason', 255)->nullable()->after('school_setup_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('school_setup_requests', function (Blueprint $table) {
            //
            $table->dropColumn(['live_status', 'clearance_packet',
            'clearance_packet_pending_nurse_reason', 'finger_printing',
            'school_setup_status', 'status_cancelled_reason']);
        });
    }
};
