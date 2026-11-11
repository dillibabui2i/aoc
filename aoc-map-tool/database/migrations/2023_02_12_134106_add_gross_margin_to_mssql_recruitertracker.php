<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGrossMarginToMssqlRecruitertracker extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_recruitertracker', function (Blueprint $table) {
            $table->double('actual_gp', 5, 2)->default(0);
            $table->date('bonus_period');
            $table->integer('bonus_override_by');
            $table->double('bonus_override')->default(0);
            $table->integer('bonus_calc_goals')->default(0);
            $table->double('bonus_calc_pct_to_goal')->default(0);
            $table->integer('bonus_calc_total_hours')->default(0);
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
        });
    }
}
