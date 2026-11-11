<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsUpdatedToRecruitingSchedule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recruiting_schedule', function (Blueprint $table) {
            $table->integer('is_updated')->nullable();
            $table->integer('aoc_patient_id')->after('id')->nullable();
            $table->integer('aoc_provider_id')->after('aoc_patient_id')->nullable();
            $table->integer('aoc_candidate_id')->after('aoc_provider_id')->nullable();
            $table->integer('aoc_facility_id')->after('aoc_candidate_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('recruiting_schedule', function (Blueprint $table) {
            //
        });
    }
}
