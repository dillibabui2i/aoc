<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNrrCandidatesDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nrr_candidates_data', function (Blueprint $table) {
            $table->id();
            $table->string('whiteboard_row')->nullable();
            $table->integer('user_id')->default(0);
            $table->string('Recruiter')->nullable();
            $table->string('territory')->nullable();
            $table->string('hire_date')->nullable();
            $table->string('candidate_start_hour_goals')->nullable();
            $table->string('state')->nullable();
            $table->string('region_name')->nullable();
            $table->string('region')->nullable();
            $table->string('Monday')->nullable();
            $table->integer('myCount')->default(0);
            $table->date('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('nrr_candidates_data');
    }
}
