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
        Schema::table('intake', function (Blueprint $table) {
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->string('subject')->nullable();
            $table->string('location')->nullable();
            $table->string('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('intake', function (Blueprint $table) {
            $table->dropColumn('start_time');
            $table->dropColumn('end_time');
            $table->dropColumn('subject');
            $table->dropColumn('location');
            $table->dropColumn('description');
        });
    }
};
