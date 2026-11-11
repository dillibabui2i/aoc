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
        Schema::table('ccr_availability_block', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'block_date']);
            $table->renameColumn('user_id', 'aoc_facility_id');
            $table->index(['aoc_facility_id', 'block_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ccr_availability_block', function (Blueprint $table) {
            $table->dropIndex(['aoc_facility_id', 'block_date']);
            $table->renameColumn('aoc_facility_id', 'user_id');
            $table->index(['user_id', 'block_date']);
        });
    }
};
