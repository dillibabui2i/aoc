<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('maps_covclin', function (Blueprint $table) {
            $table->string('PatientID')->nullable();
            $table->string('Patient_FirstName')->nullable();
            $table->string('Patient_LastName')->nullable();
            $table->string('Shift_Type')->nullable(false)->default('');
            $table->string('Days_Available')->nullable(false)->default('');
            $table->integer('aoc_patient_id')->nullable(false);
            $table->integer('aoc_provider_id')->nullable(false);
            $table->tinyInteger('custom_sort_prn')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mapcovclin', function (Blueprint $table) {
            //
        });
    }
};
