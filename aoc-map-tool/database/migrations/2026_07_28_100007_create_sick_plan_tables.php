<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sick_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->string('status', 50);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('mssql_patients');
            $table->index('status');
        });

        Schema::create('sick_plan_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('domain', 100);
            $table->string('activity_code', 100);
            $table->string('description');

            $table->unique(['domain', 'activity_code']);
        });

        Schema::create('sick_plan_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sick_plan_id');
            $table->unsignedBigInteger('catalog_id');

            $table->foreign('sick_plan_id')->references('id')->on('sick_plans');
            $table->foreign('catalog_id')->references('id')->on('sick_plan_catalog');
            $table->unique(['sick_plan_id', 'catalog_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sick_plan_items');
        Schema::dropIfExists('sick_plan_catalog');
        Schema::dropIfExists('sick_plans');
    }
};
