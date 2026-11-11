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
        Schema::create('first_advantage_checks', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('recruiting_schedule_id');
            $table->string('fi_candidate_id')->nullable(); // first advantace candidate id
            $table->string('fi_order_id')->nullable(); // first advantage ordere id
            $table->string('status')->nullable();
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
        Schema::dropIfExists('first_advantage_checks');
    }
};
