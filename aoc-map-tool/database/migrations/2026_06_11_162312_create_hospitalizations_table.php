<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hospitalizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('encounter_id');
            $table->unsignedTinyInteger('level');
            $table->unsignedTinyInteger('type');
            $table->unsignedTinyInteger('status');
            $table->unsignedTinyInteger('declined_program_contact');
            $table->timestamp('admission_date');
            $table->unsignedTinyInteger('admission_type');
            $table->string('reason');
            $table->unsignedTinyInteger('source');
            $table->boolean('is_triage_team_contacted_prior_to_admit_transfer');
            $table->timestamp('family_contacted_after_admit_notification_at');
            $table->unsignedTinyInteger('care_requirements');
            $table->timestamp('resumption_of_care_at');
            $table->unsignedTinyInteger('thirty_day_outcome');
            $table->timestamp('expected_discharge_date')->nullable();
            $table->timestamp('discharge_date')->nullable();
            $table->timestamp('first_billable_shift_after_hospital_discharge_at')->nullable();
            $table->boolean('is_first_billable_shift_completed_within_48_hours');
            $table->unsignedTinyInteger('closing_reason')->nullable();
            $table->timestamps();

            $table->foreign('encounter_id')->references('id')->on('encounters');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('hospitalizations');
    }
};
