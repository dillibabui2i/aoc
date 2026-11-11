<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosis');
    }
};
