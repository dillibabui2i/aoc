<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToMssqlFacilities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_facilities', function (Blueprint $table) {
            $table->text('IPEA_instructions')->after('BranchName')->nullable();
            $table->boolean('hide')->after('IPEA_instructions')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mssql_facilities', function (Blueprint $table) {
            //
        });
    }
}
