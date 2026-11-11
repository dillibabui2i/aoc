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
            $table->unsignedBigInteger('encounter_parent_id')->nullable();
            $table->string('type', 50);
            $table->string('status', 50)->nullable();
            $table->string('facility_name')->nullable();
            $table->timestamp('admission_at')->nullable();
            $table->string('admission_type', 50)->nullable();
            $table->text('admission_reason')->nullable();
            $table->text('transfer_reason')->nullable();
            $table->timestamp('expected_discharge_at')->nullable();
            $table->timestamp('discharged_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('mssql_patients');
            $table->foreign('encounter_parent_id')->references('id')->on('encounters');
            $table->index('type');
            $table->index('status');
            $table->index('admission_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encounters');
    }
};
