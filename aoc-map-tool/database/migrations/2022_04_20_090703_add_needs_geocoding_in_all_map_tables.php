<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNeedsGeocodingInAllMapTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $allMapList = DB::table('map_configurations')->select('map_tablename')->get();
        if (count($allMapList) > 0) {
            foreach ($allMapList as $allMapData) {
                if (Schema::hasTable($allMapData->map_tablename)) {
                    if (!Schema::hasColumn($allMapData->map_tablename, 'needs_geocoding')) {
                        Schema::table($allMapData->map_tablename, function (Blueprint $table) {
                            //
                            $table->tinyInteger('needs_geocoding')->comment('0-No,1-Yes')->default(1);
                        });
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $allMapList = DB::table('map_configurations')->select('map_tablename')->get();
        if (count($allMapList) > 0) {
            foreach ($allMapList as $allMapData) {
                if (Schema::hasTable($allMapData->map_tablename)) {
                    if (Schema::hasColumn($allMapData->map_tablename, 'needs_geocoding')) {
                        Schema::table($allMapData->map_tablename, function (Blueprint $table) {
                            //
                            $table->dropColumn('needs_geocoding');
                        });
                    }
                }
            }
        }
    }
}
