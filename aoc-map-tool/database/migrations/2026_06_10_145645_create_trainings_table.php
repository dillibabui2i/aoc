<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};
