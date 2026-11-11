<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('procedures');
    }
};
