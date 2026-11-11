<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
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

    public function down(): void
    {
        Schema::dropIfExists('ed_visits');
    }
};
