<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldInMssqlPatientTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_patients', function (Blueprint $table) {
            $table->string('hh_chartid',20)->nullable();
            $table->date('hh_soc')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mssql_patients', function (Blueprint $table) {
            //
            $table->dropColumn('hh_chartid');
            $table->dropColumn('hh_soc');
        });
    }
}
