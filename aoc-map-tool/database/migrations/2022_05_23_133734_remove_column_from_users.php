<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveColumnFromUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('candidate_start_hour_goals');
            $table->dropColumn('ipea_goal');
            $table->dropColumn('pepi_wepi_goal');
            $table->dropColumn('client_match_goal');
            $table->dropColumn('client_match_hours_goal');
            $table->dropColumn('candidate_start_goal');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
