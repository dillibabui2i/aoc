<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hospitalization_trackers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('encounter_id');
            $table->string('status', 50);
            $table->string('icm_level')->nullable();
            $table->string('source', 50)->nullable();
            $table->string('closing_reason', 50)->nullable();
            $table->text('closing_reason_details')->nullable();
            $table->unsignedBigInteger('assigned_to_id')->nullable();
            $table->unsignedBigInteger('sent_to_review_by_id')->nullable();
            $table->timestamps();

            $table->foreign('encounter_id')->references('id')->on('encounters');
            $table->foreign('assigned_to_id')->references('id')->on('users');
            $table->foreign('sent_to_review_by_id')->references('id')->on('users');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospitalization_trackers');
    }
};
