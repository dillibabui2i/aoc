<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEdAndHospitalizedFieldsToTriage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('triageCalls', function (Blueprint $table) {
            $table->boolean('hospitalized_1d')->default(0);
            $table->boolean('ed_visited_1d')->default(0);
            $table->boolean('hospitalized_2d')->default(0);
            $table->boolean('ed_visited_2d')->default(0);
            $table->boolean('hospitalized_7d')->default(0);
            $table->boolean('ed_visited_7d')->default(0);
            $table->boolean('hospitalized_30d')->default(0);
            $table->boolean('ed_visited_30d')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {}
}
