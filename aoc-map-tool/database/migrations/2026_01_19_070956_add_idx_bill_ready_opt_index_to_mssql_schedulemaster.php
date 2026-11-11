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
                ['IsBillable', 'IsBilled', 'HasChild', 'PatientID', 'ScheduleDate', 'PlannedStart', 'ScheduleStatusID', 'EChartStatusID'],
                'idx_bill_ready_opt'
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
            $table->dropIndex('idx_bill_ready_opt');
        });
    }
};
