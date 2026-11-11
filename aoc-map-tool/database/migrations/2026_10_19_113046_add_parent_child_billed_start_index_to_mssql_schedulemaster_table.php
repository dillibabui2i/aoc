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
            $table->index(
                ['ParentCgTaskID', 'IsChild', 'IsBilled', 'PlannedStart'],
                'idx_msm_parent_child_billed_start'
            );
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
            $table->dropIndex('idx_msm_parent_child_billed_start');
        });
    }
};
