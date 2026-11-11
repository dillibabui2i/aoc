<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeAddressNullableOnMapsPotentialNurseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('maps_potential_nurse', function (Blueprint $table) {
            $table->text('Address')->unsigned()->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('maps_potential_nurse', function (Blueprint $table) {
            $table->text('Address')->unsigned()->nullable(false)->change();
        });
    }
}
