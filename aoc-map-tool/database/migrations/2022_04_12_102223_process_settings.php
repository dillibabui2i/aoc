<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProcessSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('process_settings', function (Blueprint $table) {
            $table->id();
            $table->string('process', 50);
            $table->char('last_updated_date', 19);
            $table->string('last_updated_id', 255);
            $table->char('last_batch_start', 19);
            $table->char('last_batch_end', 19);
            $table->tinyInteger('running_now');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('process_settings');
    }
}
