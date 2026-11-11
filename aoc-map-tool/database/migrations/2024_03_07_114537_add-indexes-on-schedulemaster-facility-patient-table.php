<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_schedulemaster', function (Blueprint $table) {
            $table->index(
                [
                    'IsBillable',
                    'ScheduleDate',
                    'PatientID',
                    'is_missed_visit'
                ],
                'NCC_MISSED_SCHEDULEMASTER'
            );
        });

        Schema::table('mssql_facilities', function (Blueprint $table) {
            $table->index(
                [
                    'id',
                    'State',
                    'region'
                ],
                'NCC_MISSED_FACILITY'
            );
        });

        Schema::table('mssql_patients', function (Blueprint $table) {
            $table->index(
                [
                    'aoc_facility_id',
                    'PatientID'
                ],
                'NCC_MISSED_PATIENTS'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mssql_schedulemaster', function (Blueprint $table) {
            $table->dropIndex('NCC_MISSED_SCHEDULEMASTER');
        });

        Schema::table('mssql_facilities', function (Blueprint $table) {
            $table->dropIndex('NCC_MISSED_FACILITY');
        });

        Schema::table('mssql_patients', function (Blueprint $table) {
            $table->dropIndex('NCC_MISSED_PATIENTS');
        });
    }
};
