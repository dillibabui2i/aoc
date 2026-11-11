<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApprovalFieldToOfficeEvaluations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('office_evaluations', function (Blueprint $table) {
            $table->datetime('exec_team_datetime')->nullable()->after('manager_id');
            $table->integer('exec_team_id')->nullable()->after('exec_team_datetime');
           
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('office__evaluations', function (Blueprint $table) {
            //
        });
    }
}
