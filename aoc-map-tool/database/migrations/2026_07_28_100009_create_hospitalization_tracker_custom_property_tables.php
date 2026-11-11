<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hospitalization_tracker_custom_property_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('default_value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hospitalization_tracker_properties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hospitalization_tracker_id');
            $table->unsignedBigInteger('definition_id');
            $table->json('value');
            $table->timestamps();

            $table->foreign('hospitalization_tracker_id', 'fk_ht_properties_tracker_id')
                ->references('id')
                ->on('hospitalization_trackers');
            $table->foreign('definition_id', 'fk_ht_properties_definition_id')
                ->references('id')
                ->on('hospitalization_tracker_custom_property_definitions');
            $table->unique(['hospitalization_tracker_id', 'definition_id'], 'ht_properties_tracker_definition_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospitalization_tracker_properties');
        Schema::dropIfExists('hospitalization_tracker_custom_property_definitions');
    }
};
