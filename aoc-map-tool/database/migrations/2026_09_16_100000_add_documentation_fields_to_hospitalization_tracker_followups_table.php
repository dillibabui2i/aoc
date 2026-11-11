<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hospitalization_tracker_followups', function (Blueprint $table) {
            $table->unsignedBigInteger('attempted_by_id')->nullable()->after('attempted_at');
            $table->text('status_note')->nullable()->after('notes');
            $table->text('follow_ups_updates')->nullable()->after('status_note');
            $table->text('needs')->nullable()->after('follow_ups_updates');

            $table->foreign('attempted_by_id', 'fk_ht_followups_attempted_by_id')
                ->references('id')
                ->on('users');
        });
    }

    public function down(): void
    {
        Schema::table('hospitalization_tracker_followups', function (Blueprint $table) {
            $table->dropForeign('fk_ht_followups_attempted_by_id');
            $table->dropColumn(['attempted_by_id', 'status_note', 'follow_ups_updates', 'needs']);
        });
    }
};
