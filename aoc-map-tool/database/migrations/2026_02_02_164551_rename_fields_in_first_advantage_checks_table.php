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
        Schema::table('first_advantage_checks', function (Blueprint $table) {
            $table->renameColumn('recruiting_schedule_id', 'aoc_candidate_id');
            $table->renameColumn('fi_candidate_id', 'fa_candidate_id');
            $table->renameColumn('fi_order_id', 'fa_order_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('first_advantage_checks', function (Blueprint $table) {
            $table->renameColumn('aoc_candidate_id', 'recruiting_schedule_id');
            $table->renameColumn('fa_candidate_id', 'fi_candidate_id');
            $table->renameColumn('fa_order_id', 'fi_order_id');
        });
    }
};
