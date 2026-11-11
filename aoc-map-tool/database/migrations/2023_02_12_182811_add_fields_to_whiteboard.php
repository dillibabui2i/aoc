<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToWhiteboard extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('whiteboard', function (Blueprint $table) {
            $table->integer('tmpTotalCandidateStartHours')->default(0);
            $table->integer('tmpCandidateStartHoursGoal')->default(0);
            $table->double('tmpPercentageOfGoal', 5, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('whiteboard', function (Blueprint $table) {
            //
        });
    }
}
