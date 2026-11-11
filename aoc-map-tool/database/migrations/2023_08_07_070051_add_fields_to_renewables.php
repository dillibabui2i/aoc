<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToRenewables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renewables', function (Blueprint $table) {
            $table->integer('last_followup_by')->default(0)->nullable();
            $table->datetime('last_follow_up')->nullable();
            $table->integer('thirtyzeroexpiring_attempt_count')->default(0)->nullable();
            $table->integer('ninetythirtyexpiring_attempt_count')->default(0)->nullable();
            $table->integer('missing_attempt_count')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('renewables', function (Blueprint $table) {
            //
        });
    }
}
