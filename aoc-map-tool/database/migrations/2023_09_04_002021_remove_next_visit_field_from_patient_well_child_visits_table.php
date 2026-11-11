<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveNextVisitFieldFromPatientWellChildVisitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patient_well_child_visits', function (Blueprint $table) {
            //
            $table->dropColumn('next_visit');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('patient_well_child_visits', function (Blueprint $table) {
            //
            $table->date('next_visit')->after('last_visit');
        });
    }
}
