<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('communication_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('encounter_id');
            $table->unsignedBigInteger('assignee_id')->nullable();
            $table->timestamp('followup_date')->nullable();
            $table->unsignedTinyInteger('status')->default(0);
            $table->string('description', 500)->nullable();
            $table->string('updates', 500)->nullable();
            $table->string('needs', 500)->nullable();
            $table->string('additional_notes', 500)->nullable();
            $table->string('attempt_notes', 500)->nullable();
            $table->string('cancellation_notes', 500)->nullable();
            $table->boolean('is_follow_up_due');
            $table->timestamp('follow_up_due_date')->nullable();
            $table->timestamps();

            $table->foreign('encounter_id')->references('id')->on('encounters');
            $table->foreign('assignee_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_requests');
    }
};
