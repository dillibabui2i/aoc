<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ncc_report_weekly_calls', function (Blueprint $table) {
            $table->id();
            $table->integer('aoc_nthaccount_id')->nullable();
            $table->timestamp('week_start')->nullable();
            $table->integer('ccc_id')->nullable();
            $table->timestamp('last_weekly_followup')->nullable();
            $table->integer('attempt_count')->nullable();
            $table->string('outcome', 255)->nullable();
            $table->string('result', 255)->nullable();
            $table->timestamp('weekly_follow_up_attempts')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ncc_report_weekly_calls');
    }
};
