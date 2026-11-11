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
        Schema::create('ccc_followup_calls', function (Blueprint $table) {
            $table->id();
            $table->integer('aoc_patient_id');
            $table->integer('aoc_nthaccount_id');
            $table->date('start_date');
            $table->integer('ccc_id');
            $table->datetime('last_weekly_followup');
            $table->datetime('last_monthly_followup');
            $table->datetime('requested_callback');
            $table->integer('attempt_count');
            $table->string('outcome', 255);
            $table->string('result', 255);
            $table->datetime('weekly_follow_up_attempts');
            $table->datetime('monthly_follow_up_attempts');
            $table->datetime('created_at');
            $table->integer('created_by');
            $table->datetime('updated_at');
            $table->integer('updated_by');
            $table->date('due_date');
            $table->string('type', 10);
            $table->string('outcome_json');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ccc_followup_calls');
    }
};
