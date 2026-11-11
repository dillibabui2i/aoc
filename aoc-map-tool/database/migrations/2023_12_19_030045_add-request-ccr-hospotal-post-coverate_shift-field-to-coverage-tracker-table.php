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
        Schema::table('coverage_tracker', function (Blueprint $table) {
            $table->boolean('request_ccr_hopsital_post_coverage_shift')->nullable()->default('0');
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
            $table->dropColumn('request_ccr_hopsital_post_coverage_shift');
        });
    }
};
