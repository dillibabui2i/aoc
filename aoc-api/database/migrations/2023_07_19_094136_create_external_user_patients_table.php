<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExternalUserPatientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('external_user_patients', function (Blueprint $table) {
            $table->id();
            $table->integer('external_user_id')->default(0)->comment('External user ID');
            $table->integer('aoc_patient_id')->default(0);
            $table->boolean('status')->comment('1=Active, 0=Inactive')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('external_user_patients');
    }
}
