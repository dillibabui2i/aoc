<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAttemptCountForAllTriageCallType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //attempts_24, attempts_48, attempts_7day, attempts_30day
        Schema::table('triageCalls', function (Blueprint $table) {
            $table->integer('attempts_24')->default(0)->nullable();
            $table->integer('attempts_48')->default(0)->nullable();
            $table->integer('attempts_7day')->default(0)->nullable();
            $table->integer('attempts_30day')->default(0)->nullable();
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
}
