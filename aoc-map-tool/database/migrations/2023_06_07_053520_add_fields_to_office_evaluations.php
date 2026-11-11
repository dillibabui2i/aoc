<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToOfficeEvaluations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('office_evaluations', function (Blueprint $table) {
            $table->datetime('supervisor_datetime')->nullable()->after('supervisors_submitted');
            $table->integer('supervisor_id')->nullable()->after('supervisor_datetime');

            $table->datetime('manager_datetime')->nullable()->after('manager_submitted');
            $table->integer('manager_id')->nullable()->after('manager_datetime');

            $table->datetime('hr_datetime')->nullable()->after('hr_submitted');
            $table->integer('hr_id')->nullable()->after('hr_datetime');
           
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
            //
        });
    }
}
