<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ConvertCandidateTableToCandidatemap extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('lat')->nullable();
            $table->string('lng')->nullable();
            $table->tinyInteger('is_updated')->nullable();
            $table->tinyInteger('needs_geocoding')->nullable();
            $table->tinyInteger('is_ignored')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('candidates', function (Blueprint $table) {
            //
        });
    }
}
