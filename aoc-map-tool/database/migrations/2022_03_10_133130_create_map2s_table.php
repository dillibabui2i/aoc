<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMap2sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('map2', function (Blueprint $table) {
            $table->id();
            $table->string('Date')->nullable(true);
            $table->string('Client_First_Name')->nullable(true);
            $table->string('Client_Last_Name')->nullable(true);
            $table->string('Name')->nullable(true);
            $table->text('Payer')->nullable(true);
            $table->string('Discipline')->nullable(true);
            $table->string('Service')->nullable(true);
            $table->string('Schedule_Time')->nullable(true);
            $table->string('Hours')->nullable(true);
            $table->string('Authorization')->nullable(true);
            $table->string('Client_Address')->nullable(true);
            $table->string('Client_County')->nullable(true);
            $table->string('Client_City')->nullable(true);
            $table->string('Client_ZipCode')->nullable(true);
            $table->string('State')->nullable(true);
            $table->string('Locations')->nullable(true);
            $table->string('lat')->nullable(true);
            $table->string('lng')->nullable(true);

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
        Schema::dropIfExists('map1s');
    }
}
