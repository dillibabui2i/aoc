<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Polygons extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('polygons', function (Blueprint $table) {
            $table->id('id');
            $table->string('map_id', 255);
            $table->string('name', 255)->nullable()->default('');
            $table->string('color', 255)->nullable()->default('');
            $table->text('coordinates')->nullable();
            $table->timestamp('created_at')->useCurrent();
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
