<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hospitalization_tracker_training_types', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hospitalization_tracker_trainings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hospitalization_tracker_id');
            $table->unsignedBigInteger('training_type_id');
            $table->boolean('is_completed')->default(false);
            $table->unsignedBigInteger('completed_by_id')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('hospitalization_tracker_id', 'fk_ht_trainings_tracker_id')->references('id')->on('hospitalization_trackers');
            $table->foreign('training_type_id')->references('id')->on('hospitalization_tracker_training_types');
            $table->foreign('completed_by_id')->references('id')->on('users');
            $table->index('completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospitalization_tracker_trainings');
        Schema::dropIfExists('hospitalization_tracker_training_types');
    }
};
