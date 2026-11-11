<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableColdCalling extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cold_calling', function (Blueprint $table) {
            $table->id();
            $table->string('Clinician_ID', 255)->nullable();
            $table->string('Provider_Name', 255)->nullable();
            $table->string('Period_Hours', 255)->nullable();
            $table->string('_of_Weeks_in_Period', 255)->nullable();
            $table->string('Weekly_Average', 255)->nullable();
            $table->string('Period_Start_Date', 255)->nullable();
            $table->string('Period_End_Date', 255)->nullable();
            $table->string('Provider_Status', 255)->nullable();
            $table->string('Dicipline', 255)->nullable();
            $table->string('Language', 255)->nullable();
            $table->string('Provider_is_Bilingual', 255)->nullable();
            $table->string('Pay_Type', 255)->nullable();
            $table->string('Gender', 255)->nullable();
            $table->string('Address1', 255)->nullable();
            $table->string('Address2', 255)->nullable();
            $table->string('City', 255)->nullable();
            $table->string('State', 255)->nullable();
            $table->string('Zip', 255)->nullable();
            $table->string('Phone', 255)->nullable();
            $table->string('Mobile', 255)->nullable();
            $table->string('Provider_Home_Branch', 255)->nullable();
            $table->string('AOC_Email', 255)->nullable();
            $table->string('External_Email', 255)->nullable();
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
        Schema::dropIfExists('table_cold_calling');
    }
}
