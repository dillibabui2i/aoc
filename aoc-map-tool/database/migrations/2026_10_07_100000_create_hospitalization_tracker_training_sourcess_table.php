<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hospitalization_tracker_training_sourcess', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('hospitalization_tracker_trainings', function (Blueprint $table) {
            $table->unsignedBigInteger('training_source_id')->after('training_type_id');

            $table->foreign('training_source_id', 'fk_ht_trainings_source_id')
                ->references('id')->on('hospitalization_tracker_training_sourcess');
        });
    }

    public function down(): void
    {
        Schema::table('hospitalization_tracker_trainings', function (Blueprint $table) {
            $table->dropForeign('fk_ht_trainings_source_id');
            $table->dropColumn('training_source_id');
        });

        Schema::dropIfExists('hospitalization_tracker_training_sourcess');
    }
};
