<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hospitalization_tracker_followups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hospitalization_tracker_id')->nullable();
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('assigned_to_id')->nullable();
            $table->unsignedBigInteger('requested_by_id')->nullable();
            $table->string('status', 50);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('hospitalization_tracker_id', 'fk_ht_followups_tracker_id')
                ->references('id')
                ->on('hospitalization_trackers');
            $table->foreign('patient_id', 'fk_ht_followups_patient_id')
                ->references('id')
                ->on('mssql_patients');
            $table->foreign('assigned_to_id', 'fk_ht_followups_assigned_to_id')
                ->references('id')
                ->on('users');
            $table->foreign('requested_by_id', 'fk_ht_followups_requested_by_id')
                ->references('id')
                ->on('users');
            $table->foreign('completed_by_id', 'fk_ht_followups_completed_by_id')
                ->references('id')
                ->on('users');
            $table->index('status');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospitalization_tracker_followups');
    }
};
