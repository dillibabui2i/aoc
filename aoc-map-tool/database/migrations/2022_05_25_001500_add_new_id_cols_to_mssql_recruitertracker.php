<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewIdColsToMssqlRecruitertracker extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_recruitertracker', function (Blueprint $table) {
            $table->integer('aoc_patient_id')->after('id')->nullable();
            $table->integer('aoc_provider_id')->after('aoc_patient_id')->nullable();
            $table->integer('aoc_candidate_id')->after('aoc_provider_id')->nullable();
            $table->integer('aoc_facility_id')->after('aoc_candidate_id')->nullable();
            $table->integer('aoc_employmentstatus_id')->after('aoc_facility_id')->nullable();
            $table->date('application_date')->nullable();
            $table->boolean('no_candidate_match');
            $table->boolean('no_provider_match');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mssql_recruitertracker', function (Blueprint $table) {
            //
        });
    }
}
