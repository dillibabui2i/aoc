<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSurvaySentFieldToTriageFollowupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('triageFollowup', function (Blueprint $table) {
            $table->boolean('survay_sent')->comment("1= survay sent")->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('triageFollowup', function (Blueprint $table) {
            $table->dropColumn('survay_sent');
        });
    }
}
