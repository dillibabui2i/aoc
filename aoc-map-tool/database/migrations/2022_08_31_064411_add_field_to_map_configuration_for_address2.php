<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldToMapConfigurationForAddress2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('map_configurations', function (Blueprint $table) {
            $table->text('latlong_fields2')->after("latlong_fields")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('map_configuration_for_address2', function (Blueprint $table) {
            //
        });
    }
}
