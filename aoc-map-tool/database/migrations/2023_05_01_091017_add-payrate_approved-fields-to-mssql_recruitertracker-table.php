<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPayrateApprovedFieldsToMssqlRecruitertrackerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_recruitertracker', function (Blueprint $table) {
            $table->boolean('payrate_approved')->default(0)->nullable();
            $table->integer('payrate_approved_by')->nullable();
            $table->datetime('payrate_approved_on')->nullable();
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
            $table->dropColumn('payrate_approved');
            $table->dropColumn('payrate_approved_by');
            $table->dropColumn('payrate_approved_on');
        });
    }
}
