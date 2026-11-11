<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAddressFieldsToNeedToHireMap extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('maps_nth', function (Blueprint $table) {
            $table->string('lat2',255)->after('lng')->nullable();
            $table->string('lng2',255)->after('lat2')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('need_to_hire_map', function (Blueprint $table) {
            //
        });
    }
}
