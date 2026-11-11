<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('mobile_otp_log', function (Blueprint $table) {
            $table->unsignedInteger('resend_count')->default(0)->after('attempt_count');
            $table->unsignedInteger('limit_events_count')->default(0)->after('resend_count');
            $table->timestamp('cooldown_until')->nullable()->after('limit_events_count');
            $table->timestamp('expires_at')->nullable()->after('send_at');
            $table->timestamp('invalidated_at')->nullable()->after('verified_at');
            $table->string('invalidated_reason')->nullable()->after('invalidated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('mobile_otp_log', function (Blueprint $table) {
            $table->dropColumn('resend_count');
            $table->dropColumn('limit_events_count');
            $table->dropColumn('cooldown_until');
            $table->dropColumn('expires_at');
            $table->dropColumn('invalidated_at');
            $table->dropColumn('invalidated_reason');
        });
    }
};
