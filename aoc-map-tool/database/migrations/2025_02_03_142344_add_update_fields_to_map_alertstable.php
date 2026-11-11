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
        Schema::table('mssql_providers', function (Blueprint $table) {
            $table->string('SMS_Status')->nullable()->after("CF_DoNotContact");
        });

        Schema::table('maps_nthalerts', function (Blueprint $table) {
            $table->renameColumn('ClinicialID', 'ClinicianID');
            $table->string("Mile_Preference")->nullable()->after("State_Preference");
            $table->string('SMS_Status')->nullable(false)->change();
            $table->float('WeeklyAverage', 8, 2)->change();
            
        });

       
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('map_alertstable', function (Blueprint $table) {
            //
        });
    }
};
