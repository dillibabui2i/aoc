<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedManuallyInCoverageTrackerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('coverage_tracker', function (Blueprint $table) {
            //
            $table->boolean('created_manually')->default(0)->comment("1 = when record created from coverage tracker form");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('coverage_tracker', function (Blueprint $table) {
            //
            $table->dropColumn('created_manually');
        });
    }
}
