<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeLatLngFieldTypeInLocationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('location', function (Blueprint $table) {
            //
            $table->decimal('lat', 12, 9)->nullable()->change();
            $table->decimal('lng', 12, 9)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('location', function (Blueprint $table) {
            //
            $table->decimal('lat', 9, 7)->nullable()->change();
            $table->decimal('lng', 9, 7)->nullable()->change();
        });
    }
}
