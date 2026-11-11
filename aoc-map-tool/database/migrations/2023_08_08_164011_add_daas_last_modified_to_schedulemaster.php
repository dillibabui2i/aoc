<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDaasLastModifiedToSchedulemaster extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_schedulemaster_daas', function (Blueprint $table) {
            $table->string('DAAS_LAST_MODIFIED_TIMESTAMP',255);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('schedulemaster', function (Blueprint $table) {
            //
        });
    }
}
