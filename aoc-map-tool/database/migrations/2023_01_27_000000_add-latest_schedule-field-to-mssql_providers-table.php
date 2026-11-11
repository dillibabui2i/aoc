<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLatestScheduleFieldToMssqlProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('mssql_providers', 'latest_schedule')) {
            Schema::table('mssql_providers', function (Blueprint $table) {
                $table->date('latest_schedule')->after('has_billable_visit')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('mssql_providers', 'latest_schedule')) {
            Schema::table('mssql_providers', function (Blueprint $table) {
                $table->dropColumn('latest_schedule');
            });
        }
    }
}
