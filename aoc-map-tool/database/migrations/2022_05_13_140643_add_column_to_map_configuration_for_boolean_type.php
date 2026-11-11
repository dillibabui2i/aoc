<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToMapConfigurationForBooleanType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('map_configurations', function (Blueprint $table) {
            //
            $table->text('map_boolean_field')->after('enable_address_filter')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('map_configuration_for_boolean_type', function (Blueprint $table) {
            //
        });
    }
}
