<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewFiekldsInNrrCandidatesDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nrr_candidates_data', function (Blueprint $table) {
            //
            $table->dropColumn('user_id');
            $table->dropColumn('hire_date');
            $table->dropColumn('candidate_start_hour_goals');
            $table->dropColumn('region');
            $table->dropColumn('myCount');
            $table->string('CandidateStartDate')->nullable();
            $table->string('PatientName')->nullable();
            $table->string('ProviderName')->nullable();
            $table->string('Total_Hours')->nullable();
            $table->string('CGTaskID')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('nrr_candidates_data', function (Blueprint $table) {
            //
            $table->integer('user_id')->default(0);
            $table->string('hire_date')->nullable();
            $table->string('candidate_start_hour_goals')->nullable();
            $table->string('region')->nullable();
            $table->integer('myCount')->default(0);
            $table->dropColumn('CandidateStartDate');
            $table->dropColumn('PatientName');
            $table->dropColumn('ProviderName');
            $table->dropColumn('Total_Hours');
            $table->dropColumn('CGTaskID');
        });
    }
}
