<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldToMssqlRecruitertrackerNew extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_recruitertracker', function (Blueprint $table) {
            $table->boolean('schedules_built')->default(0)->nullable(); 
            $table->integer('checked_off_by')->default(0)->nullable();
            $table->datetime('checked_off_at')->nullable();
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
