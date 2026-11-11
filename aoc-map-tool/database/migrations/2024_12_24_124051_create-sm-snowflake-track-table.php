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
        Schema::create('sm_snowflake_track', function (Blueprint $table) {
            $table->id();
            $table->dateTime('current_date')->nullable();
			$table->dateTime('etl_date')->nullable();
			$table->unsignedInteger('count')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sm_snowflake_track');
    }
};
