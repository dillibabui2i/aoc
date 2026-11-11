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
        Schema::table('recruiting_schedule', function (Blueprint $table) {
            $table->string('first_shift_follow_up_que_one')->nullable();
            $table->string('first_shift_follow_up_que_two')->nullable();
            $table->string('first_shift_follow_up_que_three')->nullable();
            $table->string('first_shift_follow_up_que_four')->nullable();
            $table->string('first_shift_follow_up_que_five')->nullable();

            $table->string('seven_day_follow_up_que_one')->nullable();
            $table->string('seven_day_follow_up_que_two')->nullable();
            $table->string('seven_day_follow_up_que_three')->nullable();
            $table->string('seven_day_follow_up_que_four')->nullable();
            $table->string('seven_day_follow_up_que_five')->nullable();

            $table->string('thirty_day_follow_up_que_one')->nullable();
            $table->string('thirty_day_follow_up_que_two')->nullable();
            $table->string('thirty_day_follow_up_que_three')->nullable();
            $table->string('thirty_day_follow_up_que_four')->nullable();
            $table->string('thirty_day_follow_up_que_five')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('recruitertracker', function (Blueprint $table) {
            //
        });
    }
};
