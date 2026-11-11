<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApprovalFieldToOfficeEvaluations2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('office_evaluations', function (Blueprint $table) {
            $table->integer('exec_team_submitted')->nullable()->after('exec_team_id');
           
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('office__evaluations2', function (Blueprint $table) {
            //
        });
    }
}
