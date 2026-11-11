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
        $maps =DB::table('map_configurations')
        ->where("map_tablename","maps_branch")->update(["map_tablename"=>"branch_locations"]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('map', function (Blueprint $table) {
            //
        });
    }
};
