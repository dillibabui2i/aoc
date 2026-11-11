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
        Schema::table('paycom', function (Blueprint $table) {
            $table->dropColumn('EmployeeRegisterID');
            $table->dropColumn('ProviderID');
            $table->dropColumn('UpdatedOn');
            $table->renameColumn('ClinicianID', 'EmployeeCode');

            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('paycom', function (Blueprint $table) {
            //
        });
    }
};
