<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableCoverageClinician extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coverage_clinician', function (Blueprint $table) {
            $table->id();
            $table->string('Clinician_First_Name', 255)->nullable();
            $table->string('Clinician_Last_Name', 255)->nullable();
            $table->string('Address_1', 255)->nullable();
            $table->string('Address_2', 255)->nullable();
            $table->string('City', 255)->nullable();
            $table->string('State', 255)->nullable();
            $table->string('Zip', 255)->nullable();
            $table->string('Cell', 255)->nullable();
            $table->string('Phone', 255)->nullable();
            $table->string('Email', 255)->nullable();
            $table->string('Gender', 255)->nullable();
            $table->string('Location', 255)->nullable();
            $table->string('Discipline', 255)->nullable();
            $table->string('Job_Title', 255)->nullable();
            $table->string('Status', 255)->nullable();
            $table->string('Language', 255)->nullable();
            $table->string('lat', 255)->nullable();
            $table->string('lng', 255)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('table_coverage_clinician');
    }
}
