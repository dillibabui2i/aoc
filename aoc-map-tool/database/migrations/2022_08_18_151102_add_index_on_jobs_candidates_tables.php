<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexOnJobsCandidatesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('candidates', function(Blueprint $table)
        {
            $table->index('jobId');
        });
        Schema::table('jobs', function(Blueprint $table)
        {
            $table->index('eId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('candidates', function (Blueprint $table)
        {
            $table->dropIndex(['jobId']);
        });
        Schema::table('jobs', function (Blueprint $table)
        {
            $table->dropIndex(['eId']);
        });
    }
}
