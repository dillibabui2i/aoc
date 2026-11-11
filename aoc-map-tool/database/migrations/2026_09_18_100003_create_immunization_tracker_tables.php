<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('immunization_trackers', function (Blueprint $table) {
            $table->id();
            $table->string('status', 50);
            $table->unsignedBigInteger('patient_id');
            $table->unsignedSmallInteger('vaccine_cvx_code');
            $table->unsignedBigInteger('assignee_id')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable(); // NULL if auto-created based on immunization recommendations
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('patient_id', 'fk_imm_trackers_patient_id')->references('id')->on('mssql_patients');
            $table->foreign('vaccine_cvx_code', 'fk_imm_trackers_vaccine_cvx_code')->references('cvx_code')->on('cdc_vaccines');
            $table->foreign('assignee_id', 'fk_imm_trackers_assignee_id')->references('id')->on('users');
            $table->foreign('created_by_id', 'fk_imm_trackers_created_by_id')->references('id')->on('users');
            $table->index('status');
        });

        Schema::create('vaccine_doses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('immunization_tracker_id');
            $table->unsignedTinyInteger('sequence_number')->nullable();
            $table->dateTime('due_date');
            $table->dateTime('earliest_date');
            $table->dateTime('administered_date')->nullable();
            $table->string('status', 50);
            $table->string('status_reason', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('immunization_tracker_id', 'fk_vaccine_doses_tracker_id')
                ->references('id')->on('immunization_trackers');
            $table->index('status');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vaccine_doses');
        Schema::dropIfExists('immunization_trackers');
    }
};
