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
            $table->boolean('intake_been_addressed')->default(0);
            $table->integer('intake_addressed_by')->default(0);
            $table->datetime('intake_addressed_at')->nullable();
            $table->boolean('perm_filled')->default(0);
        });
        Schema::table('coverage_tracker_log', function (Blueprint $table) {
            $table->boolean('intake_been_addressed')->default(0);
            $table->integer('intake_addressed_by')->default(0);
            $table->datetime('intake_addressed_at')->nullable();
            $table->boolean('perm_filled')->default(0);
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
            $table->dropColumn('intake_been_addressed');
            $table->dropColumn('intake_addressed_by');
            $table->dropColumn('intake_addressed_at');
            $table->dropColumn('perm_filled');
        });
        Schema::table('coverage_tracker_log', function (Blueprint $table) {
            $table->dropColumn('intake_been_addressed');
            $table->dropColumn('intake_addressed_by');
            $table->dropColumn('intake_addressed_at');
            $table->dropColumn('perm_filled');
        });
    }
};
