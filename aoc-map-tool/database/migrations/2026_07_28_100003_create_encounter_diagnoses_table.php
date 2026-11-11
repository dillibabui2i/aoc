<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('encounter_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('encounter_id');
            $table->string('icd_10_code', 30);
            $table->string('description')->nullable();
            $table->string('system')->nullable();
            $table->string('rank', 50);
            $table->string('clinical_status', 50);
            $table->string('verification_status', 50);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('encounter_id')->references('id')->on('encounters');
            $table->index('icd_10_code');
            $table->unique(['encounter_id', 'icd_10_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encounter_diagnoses');
    }
};
