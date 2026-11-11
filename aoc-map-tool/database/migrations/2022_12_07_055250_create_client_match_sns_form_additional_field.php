<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientMatchSnsFormAdditionalField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_recruitertracker', function (Blueprint $table) {
            $table->boolean('hard_stop')->after("aoc_intake_id")->nullable();
            $table->boolean('pay_rate_entered_in_kantime')->after("hard_stop")->nullable();
            $table->boolean('right_signature_sent')->after("pay_rate_entered_in_kantime")->nullable();
            $table->boolean('first_week_created')->after("right_signature_sent")->nullable();
            $table->integer('kantime_patient_status')->after("first_week_created")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_match_sns_form_additional_field');
    }
}
