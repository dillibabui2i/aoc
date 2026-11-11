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
        Schema::create('first_advantage_errors', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('code');
            $table->text('message', 500);
            $table->unsignedBigInteger('userId')->nullable();
            $table->unsignedBigInteger('recruitingScheduleId');
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
        Schema::dropIfExists('first_advantage_errors');
    }
};
