<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOutcomeAttemptToTriageCallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('triageCalls', function (Blueprint $table) {
            $table->integer('outcome_attempt')->default('0');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('triageCalls', function (Blueprint $table) {
            $table->dropColumn('outcome_attempt');
        });
    }
}
