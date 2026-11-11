<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToOfficeEvaluationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('office_evaluations', function (Blueprint $table) {
            $table->integer('supervisors_submitted')->nullable()->after('overall_performance');
            $table->integer('manager_submitted')->nullable()->after('supervisors_submitted');
            $table->integer('hr_submitted')->nullable()->after('manager_submitted');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('office_evaluations', function (Blueprint $table) {
            $table->dropColumn('supervisors_submitted');
            $table->dropColumn('manager_submitted');
            $table->dropColumn('hr_submitted');
        });
    }
}
