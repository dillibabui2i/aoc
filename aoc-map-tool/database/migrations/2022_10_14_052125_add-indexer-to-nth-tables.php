<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexerToNthTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_patients', function(Blueprint $table)
        {
            $table->index('id');
            $table->index('aoc_facility_id');
        });

        Schema::table('NeedToHire_Accounts', function(Blueprint $table)
        {
            $table->index('id');
            $table->index('aoc_patient_id');
        });

        Schema::table('mssql_facilities', function(Blueprint $table)
        {
            $table->index('id');
        });

        Schema::table('NeedToHire_Checklist', function(Blueprint $table)
        {
            $table->index('id');
            $table->index('aoc_nthaccounts_id');
        });

        Schema::table('maps_nth', function(Blueprint $table)
        {
            $table->index('id');
            $table->index('aoc_nthaccounts_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mssql_patients', function(Blueprint $table)
        {
            $table->dropIndex('id');
            $table->dropIndex('aoc_facility_id');
        });

        Schema::table('NeedToHire_Accounts', function(Blueprint $table)
        {
            $table->dropIndex('id');
            $table->dropIndex('aoc_patient_id');
        });

        Schema::table('mssql_facilities', function(Blueprint $table)
        {
            $table->dropIndex('id');
        });

        Schema::table('NeedToHire_Checklist', function(Blueprint $table)
        {
            $table->dropIndex('id');
            $table->dropIndex('aoc_nthaccounts_id');
        });

        Schema::table('maps_nth', function(Blueprint $table)
        {
            $table->dropIndex('id');
            $table->dropIndex('aoc_nthaccounts_id');
        });
    }
}
