<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('territory')->after('recruiter')->nullable();
            $table->integer('candidate_start_hour_goals')->after('territory')->nullable();
            $table->integer('ipea_goal')->after('candidate_start_hour_goals')->nullable();
            $table->integer('pepi_wepi_goal')->after('ipea_goal')->nullable();
            $table->integer('client_match_goal')->after('pepi_wepi_goal')->nullable();
            $table->integer('client_match_hours_goal')->after('client_match_goal')->nullable();
            $table->integer('candidate_start_goal')->after('client_match_hours_goal')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
}
