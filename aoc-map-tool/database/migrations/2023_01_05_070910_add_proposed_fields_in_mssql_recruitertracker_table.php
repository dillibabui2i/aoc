<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProposedFieldsInMssqlRecruitertrackerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_recruitertracker', function (Blueprint $table) {
            //
            $table->string('proposed_next_step',20)->nullable();
            $table->date('proposed_ns_date')->nullable();
            $table->time('proposed_ns_time')->nullable();
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
            $table->dropColumn('proposed_next_step');
            $table->dropColumn('proposed_ns_date');
            $table->dropColumn('proposed_ns_time');
        });
    }
}
