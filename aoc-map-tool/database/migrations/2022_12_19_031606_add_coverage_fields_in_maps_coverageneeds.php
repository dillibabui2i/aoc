<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCoverageFieldsInMapsCoverageneeds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('maps_coverageneeds', function (Blueprint $table) {
            //
            $table->integer('aoc_patient_id')->default(0);
            $table->date('shift_date')->nullable();
            $table->timestamp('last_activity_date')->nullable();
            $table->integer('last_activity_by')->default(0);
            $table->string('last_activity')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('maps_coverageneeds', function (Blueprint $table) {
            //
            $table->dropColumn('aoc_patient_id');
            $table->dropColumn('shift_date');
            $table->dropColumn('last_activity_date');
            $table->dropColumn('last_activity_by');
            $table->dropColumn('last_activity');
        });
    }
}
