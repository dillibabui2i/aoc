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
        Schema::create('candidate_screenings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('recruiter_id');
            $table->unsignedTinyInteger('licensing');
            $table->unsignedTinyInteger('workload');
            $table->unsignedSmallInteger('visits');
            $table->unsignedDecimal('pay_expectations');
            $table->boolean('bilingual');
            $table->boolean('supervise');
            $table->boolean('qualified');
            $table->date('start_date');
            $table->text('experience');
            $table->text('areas_wishing_to_cover');
            $table->text('strengths');
            $table->text('areas_of_growth');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('candidate_screenings');
    }
};
