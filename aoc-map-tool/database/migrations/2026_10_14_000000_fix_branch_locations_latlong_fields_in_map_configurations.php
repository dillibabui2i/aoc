<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::table('map_configurations')
            ->where('map_tablename', 'branch_locations')
            ->update(['latlong_fields' => '{"address1":"address","city":"city","state":"state","zipcode":"zipcode"}']);
    }

    public function down()
    {
        DB::table('map_configurations')
            ->where('map_tablename', 'branch_locations')
            ->update(['latlong_fields' => '{"address1":"street_address","city":"city","state":"state","zipcode":"zipcode"}']);
    }
};
