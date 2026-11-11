<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewFieldLinkedIpeaToMssqlRecruitingtracker extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_recruitertracker', function (Blueprint $table) {
            $table->integer('linked_IPEA')->nullable();
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
