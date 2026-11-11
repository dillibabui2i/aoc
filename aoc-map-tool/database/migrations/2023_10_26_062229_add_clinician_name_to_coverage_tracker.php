<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClinicianNameToCoverageTracker extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('coverage_tracker', function (Blueprint $table) {
            $table->string('new_clinician_name',100)->nullable();
        });
        Schema::table('coverage_tracker_log', function (Blueprint $table) {
            $table->string('new_clinician_name',100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {}
}
