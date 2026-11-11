<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCcrAvailabilityBlockTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ccr_availability_block', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->date('block_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->text('note')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'block_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ccr_availability_block');
    }
}
