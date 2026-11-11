<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddIsIgnoredFieldToEachMapTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $allMaps = DB::table('map_configurations')->get()->toArray();
        foreach ($allMaps as $map) {
            if (Schema::hasTable($map->map_tablename)) {
                Schema::table($map->map_tablename, function (Blueprint $table) use ($map) {
                    if (!Schema::hasColumn($map->map_tablename, 'is_ignored')) {
                        $table->boolean('is_ignored')->default(0);
                    }
                });
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
        $allMaps = DB::table('map_configurations')->get()->toArray();
        foreach ($allMaps as $map) {
            if (Schema::hasTable($map->map_tablename)) {
                Schema::table($map->map_tablename, function (Blueprint $table) use ($map) {
                    if (Schema::hasColumn($map->map_tablename, 'is_ignored')) {
                        $table->dropColumn('is_ignored');
                    }
                });
            }
        }
    }
}
