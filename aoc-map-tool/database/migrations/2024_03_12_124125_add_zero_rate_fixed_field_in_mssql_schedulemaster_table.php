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
        Schema::table('mssql_schedulemaster', function (Blueprint $table) {
            $table->datetime('zero_rate_fixed_at')->nullable();
            $table->integer('zero_rate_fixed_by')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mssql_schedulemaster', function (Blueprint $table) {
            $table->dropColumn('zero_rate_fixed_at');
            $table->dropColumn('zero_rate_fixed_by');
        });
    }
};
