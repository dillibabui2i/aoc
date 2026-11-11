<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColsToNthSchedules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('NeedToHire_Schedules', function (Blueprint $table) {
            //
            $table->id();
            $table->bigInteger('aoc_nthaccounts_id')->after('NeedToHireAccountsID')->default(0);
            $table->string('location_description',500)->after('ShiftTypeDescription')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
