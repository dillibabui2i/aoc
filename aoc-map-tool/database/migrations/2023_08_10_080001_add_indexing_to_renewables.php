<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexingToRenewables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renewables', function(Blueprint $table)
        {
            $table->index('id');
            $table->index('aoc_provider_id');
            $table->index('renewables_type');
            $table->index('expiration_date');
            $table->index('isMissing');
        });
        Schema::table('mssql_providers', function(Blueprint $table)
        {
            $table->index('EmploymentStatusID');
        });
        Schema::table('mssql_facilities', function(Blueprint $table)
        {
            $table->index('branchid');
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
