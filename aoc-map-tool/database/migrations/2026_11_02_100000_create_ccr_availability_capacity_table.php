<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ccr_availability_capacity', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('aoc_facility_id');
            $table->unsignedTinyInteger('day_of_week')->comment('ISO-8601: 1 = Monday ... 7 = Sunday');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('capacity_per_hour');
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['aoc_facility_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ccr_availability_capacity');
    }
};
