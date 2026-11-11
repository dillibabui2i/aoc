<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('ed_visits');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('procedures');
        Schema::dropIfExists('diagnosis');
        Schema::dropIfExists('diagnosis_codes');
        Schema::dropIfExists('diagnosis_systems');
        Schema::dropIfExists('trainings');
        Schema::dropIfExists('trainings_types');
        Schema::dropIfExists('hospitalizations');
        Schema::dropIfExists('sick_plan_items');
        Schema::dropIfExists('sick_plan_catalog');
        Schema::dropIfExists('encounters');
    }

    public function down(): void
    {
        Schema::create('encounters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedTinyInteger('status');
            $table->unsignedBigInteger('assignee_id');
            $table->unsignedBigInteger('sent_to_review_by');
            $table->unsignedInteger('hospital_id')->nullable();
            $table->unsignedInteger('hospital_transfer_id')->nullable();
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
            $table->foreign('hospital_id')->references('id')->on('referralsource_master');
            $table->foreign('hospital_transfer_id')->references('id')->on('referralsource_master');
            $table->foreign('sick_plan_completed_by')->references('id')->on('users');
        });

        Schema::create('sick_plan_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('domain', 100);
            $table->string('activity_code', 100);
            $table->string('description', 100);
            $table->timestamps();
        });

        Schema::create('sick_plan_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('catalog_id');
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('mssql_patients');
            $table->foreign('catalog_id')->references('id')->on('sick_plan_catalog');
        });

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

        Schema::create('trainings_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active');
            $table->timestamps();
        });

        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('encounter_id');
            $table->unsignedBigInteger('type_id');
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->boolean('is_training_completed');
            $table->string('care_coordination_notes', 500);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('encounter_id')->references('id')->on('encounters');
            $table->foreign('type_id')->references('id')->on('trainings_types');
            $table->foreign('completed_by')->references('id')->on('users');
        });

        Schema::create('diagnosis_systems', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->timestamps();
        });

        Schema::create('diagnosis_codes', function (Blueprint $table) {
            $table->id();
            $table->string('icd_code', 30);
            $table->string('description');
            $table->timestamps();
        });

        Schema::create('diagnosis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('encounter_id');
            $table->unsignedBigInteger('code_id');
            $table->unsignedTinyInteger('rank');
            $table->unsignedBigInteger('system_id');
            $table->unsignedTinyInteger('clinical_status');
            $table->unsignedTinyInteger('verification_status');
            $table->string('diagnosis_notes', 500);
            $table->timestamps();

            $table->foreign('encounter_id')->references('id')->on('encounters');
            $table->foreign('code_id')->references('id')->on('diagnosis_codes');
            $table->foreign('system_id')->references('id')->on('diagnosis_systems');
        });

        Schema::create('procedures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('encounter_id');
            $table->unsignedBigInteger('assignee_id');
            $table->unsignedTinyInteger('coordination_type');
            $table->timestamp('due_date');
            $table->boolean('is_care_coordination_completed');
            $table->string('care_coordination_notes', 500);
            $table->timestamps();

            $table->foreign('encounter_id')->references('id')->on('encounters');
            $table->foreign('assignee_id')->references('id')->on('users');
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('encounter_id');
            $table->string('name', 100);
            $table->unsignedTinyInteger('relationship_type');
            $table->timestamps();

            $table->foreign('encounter_id')->references('id')->on('encounters');
        });

        Schema::create('ed_visits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('encounter_id');
            $table->unsignedTinyInteger('source');
            $table->timestamp('report_filed_at');
            $table->timestamp('occurred_at');
            $table->timestamp('tt_first_outreach_at');
            $table->unsignedTinyInteger('status');
            $table->unsignedTinyInteger('type');
            $table->string('reason', 500);
            $table->boolean('is_2067_form_sent_to_payer');
            $table->boolean('is_tt_contacted_prior');
            $table->boolean('is_tt_directed');
            $table->boolean('has_staffing_concerns');
            $table->boolean('has_dme_concerns');
            $table->string('staffing_percentage_notes', 500);
            $table->boolean('is_incident_report_completed');
            $table->unsignedTinyInteger('annual_flu_vaccine_status');
            $table->timestamp('annual_flu_vaccine_date')->nullable();
            $table->unsignedTinyInteger('covid_vaccine_status');
            $table->timestamp('covid_vaccine_date')->nullable();
            $table->unsignedTinyInteger('rsv_vaccine_status');
            $table->timestamp('rsv_vaccine_date')->nullable();
            $table->boolean('is_pdn_on_shift');
            $table->unsignedTinyInteger('followup_status')->default(0);
            $table->timestamp('followup_at')->nullable();
            $table->unsignedBigInteger('followup_by')->nullable();
            $table->timestamps();

            $table->foreign('encounter_id')->references('id')->on('encounters');
            $table->foreign('followup_by')->references('id')->on('users');
        });
    }
};
