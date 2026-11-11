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
            $table->tinyInteger('has_billable_visit')->default(0)->index();
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
            $table->dropColumn('has_billable_visit');
        });
    }
};
