<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexIntoMssqlProvidersAndMssqlEmploymentstatusTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_schedulemaster', function (Blueprint $table) {
            $table->index(['ProviderID', 'ScheduleStatusID', 'ScheduleDate'], 'idx_schedulemaster_provider_status_date');
        });

        Schema::table('mssql_providers', function (Blueprint $table) {
            $table->index(['EmploymentStatusID', 'is_deleted'], 'idx_providers_employment_deleted');
        });

        Schema::table('mssql_employmentstatus', function (Blueprint $table) {
            $table->index(['EmploymentStatus'], 'idx_employmentstatus_status');
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
            $table->dropIndex('idx_schedulemaster_provider_status_date');
        });

        Schema::table('mssql_providers', function (Blueprint $table) {
            $table->dropIndex('idx_providers_employment_deleted');
        });

        Schema::table('mssql_employmentstatus', function (Blueprint $table) {
            $table->dropIndex('idx_employmentstatus_status');
        });
    }
}
