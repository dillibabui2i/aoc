<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWhiteboardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('whiteboard', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->string('user_name', 100);
            $table->integer('row_number')->nullable();
            $table->string('row_id', 100);
            $table->string('territory')->nullable();
            $table->integer('candidate_start_hour_goals')->nullable();
            $table->integer('ipea_goal')->nullable();
            $table->integer('pepi_wepi_goal')->nullable();
            $table->integer('client_match_goal')->nullable();
            $table->integer('client_match_hours_goal')->nullable();
            $table->integer('candidate_start_goal')->nullable();
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
