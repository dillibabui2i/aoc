<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('immunization_recommendations', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('vaccine_cvx_code');
            $table->string('frequency', 50);
            // NULL if federal. No FK: the states dictionary table does not exist yet.
            $table->unsignedInteger('originating_state_id')->nullable();
            $table->string('status', 50);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('vaccine_cvx_code', 'fk_imm_recommendations_vaccine_cvx_code')
                ->references('cvx_code')->on('cdc_vaccines');
            $table->index('originating_state_id');
            $table->index('status');
        });

        Schema::create('immunization_dose_ages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recommendation_id');
            $table->unsignedSmallInteger('from_age')->nullable(); // 0 = Birth
            $table->string('from_age_unit', 50)->nullable();
            $table->unsignedSmallInteger('to_age')->nullable();
            $table->string('to_age_unit', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('recommendation_id', 'fk_imm_dose_ages_recommendation_id')
                ->references('id')->on('immunization_recommendations');
        });

        Schema::create('immunization_recommended_for', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recommendation_id');
            $table->string('display_name');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('recommendation_id', 'fk_imm_recommended_for_recommendation_id')
                ->references('id')->on('immunization_recommendations');
        });

        Schema::create('immunization_recommendations_mssql_patients', function (Blueprint $table) {
            $table->id('junction_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('recommendation_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('patient_id', 'fk_imm_rec_patients_patient_id')
                ->references('id')->on('mssql_patients');
            $table->foreign('recommendation_id', 'fk_imm_rec_patients_recommendation_id')
                ->references('id')->on('immunization_recommendations');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('immunization_recommendations_mssql_patients');
        Schema::dropIfExists('immunization_recommended_for');
        Schema::dropIfExists('immunization_dose_ages');
        Schema::dropIfExists('immunization_recommendations');
    }
};
