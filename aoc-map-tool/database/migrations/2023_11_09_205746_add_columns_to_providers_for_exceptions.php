<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToProvidersForExceptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_providers', function (Blueprint $table) {
            $table->string('Designation',350)->nullable();
            $table->string('PayrollGroup',20)->nullable();
            $table->boolean('IsBlockFromFurtherScheduling')->default(0);
            $table->dateTime('BlockSchedulingEffectiveDate');
            $table->boolean('IsOverrideScheduleRule')->default(0);
            $table->boolean('IsOverriedServicesinClinicianProfile')->default(0);
            $table->boolean('IsNotAutoApproveTeleSchedules')->default(0);
            $table->boolean('IsoverrideClinicianMaxHoursPerWeek')->default(0);
            $table->float('MaxHoursPerWeek', 8, 2);
            $table->string('WarningType_MaxHoursPerWeek', 5);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('providers_for_exceptions', function (Blueprint $table) {
            //
        });
    }
}
