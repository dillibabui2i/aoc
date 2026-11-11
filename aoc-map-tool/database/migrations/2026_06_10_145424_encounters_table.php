<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('encounters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedTinyInteger('status');
            $table->unsignedBigInteger('assignee_id');
            $table->unsignedBigInteger('sent_to_review_by');
            $table->unsignedBigInteger('facility_id');
            $table->unsignedBigInteger('facility_transfer_id')->nullable();
            $table->boolean('is_diagnosis_made');
            $table->boolean('is_sick_plan_completed');
            $table->unsignedBigInteger('sick_plan_completed_by')->nullable();
            $table->string('sick_plan_notes', 500)->nullable();
            $table->timestamp('sick_plan_completed_at')->nullable();
            $table->boolean('is_care_coordination_effort_needed');
            $table->string('care_coordination_effort_notes', 500)->nullable();
            $table->boolean('is_training_education_required');
            $table->string('training_education_notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('mssql_patients');
            $table->foreign('assignee_id')->references('id')->on('users');
            $table->foreign('sent_to_review_by')->references('id')->on('users');
            $table->foreign('facility_id')->references('id')->on('mssql_facilities');
            $table->foreign('facility_transfer_id')->references('id')->on('mssql_facilities');
            $table->foreign('sick_plan_completed_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encounters');
    }
};
