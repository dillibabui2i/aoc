<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIntakeFieldInMapsCoverageneedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('maps_coverageneeds', function (Blueprint $table) {
            //
            $table->string('intake',10)->default("No");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('maps_coverageneeds', function (Blueprint $table) {
            //
            $table->dropColumn('intake');
        });
    }
}
