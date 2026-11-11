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
        Schema::table('ccc_followup_calls', function (Blueprint $table) {
            $table->integer('aoc_patient_id')->nullable()->change();
            $table->integer('aoc_nthaccount_id')->nullable()->change();
            $table->date('start_date')->nullable()->change();
            $table->date('base_date')->nullable()->change();
            $table->integer('ccc_id')->nullable()->change();
            $table->datetime('last_weekly_followup')->nullable()->change();
            $table->datetime('last_monthly_followup')->nullable()->change();
            $table->datetime('requested_callback')->nullable()->change();
            $table->integer('attempt_count')->nullable()->change();
            $table->string('outcome', 255)->nullable()->change();
            $table->string('result', 255)->nullable()->change();
            $table->datetime('weekly_follow_up_attempts')->nullable()->change();
            $table->datetime('monthly_follow_up_attempts')->nullable()->change();
            $table->datetime('created_at')->nullable()->change();
            $table->integer('created_by')->nullable()->change();
            $table->datetime('updated_at')->nullable()->change();
            $table->integer('updated_by')->nullable()->change();
            $table->date('due_date')->nullable()->change();
            $table->string('type', 10)->nullable()->change();
            $table->string('outcome_json')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
