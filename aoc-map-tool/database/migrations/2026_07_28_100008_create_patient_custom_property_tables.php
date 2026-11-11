<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('patient_custom_property_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('default_value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('patient_custom_properties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('definition_id');
            $table->json('value');
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('mssql_patients');
            $table->foreign('definition_id')->references('id')->on('patient_custom_property_definitions');
            $table->unique(['patient_id', 'definition_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_custom_properties');
        Schema::dropIfExists('patient_custom_property_definitions');
    }
};
