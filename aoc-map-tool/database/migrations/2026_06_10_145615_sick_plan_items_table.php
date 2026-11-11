<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sick_plan_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('catalog_id');
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('mssql_patients');
            $table->foreign('catalog_id')->references('id')->on('sick_plan_catalog');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sick_plan_items');
    }
};
