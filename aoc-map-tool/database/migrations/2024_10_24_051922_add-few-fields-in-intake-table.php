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
        Schema::table('intake', function (Blueprint $table) {
            $table->timestamp('intake_followup')->nullable();
            $table->timestamp('IntakeDate')->nullable();
            $table->string('intake_outcome')->nullable();
            $table->integer('intake_outcome_by')->nullable();
            $table->dateTime('intake_outcome_at')->nullable();
            $table->text('intake_comment')->nullable();
            $table->string('intake_contact')->nullable();
            $table->tinyInteger('intake_readytostart')->nullable();
            $table->text('intake_readytostart_reason')->nullable();
            $table->string('intake_nonadmit_reason')->nullable();
            $table->string('intake_controllable')->nullable();
            $table->tinyInteger('intake_lead_exclude')->nullable();
            $table->dateTime('intake_approved_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('intake', function (Blueprint $table) {
            $table->dropColumn([
                'intake_followup',
                'IntakeDate',
                'intake_outcome',
                'intake_outcome_by',
                'intake_outcome_at',
                'intake_comment',
                'intake_contact',
                'intake_readytostart',
                'intake_readytostart_reason',
                'intake_nonadmit_reason',
                'intake_controllable',
                'intake_lead_exclude',
                'intake_approved_date'
            ]);
        });
    }
};
