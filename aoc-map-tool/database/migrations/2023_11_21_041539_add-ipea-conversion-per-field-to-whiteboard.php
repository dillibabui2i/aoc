<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIpeaConversionPerFieldToWhiteboard extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('whiteboard', function (Blueprint $table) {
            $table->integer('ipea_coversion_per')->nullable()->after('candidate_start_goal');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('whiteboard', function (Blueprint $table) {
            $table->dropColumn('ipea_coversion_per');
        });
    }
}
