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
        Schema::table('mssql_patients_test_daas', function (Blueprint $table) {
            $table->string('client_classification', 500)->nullable();
            $table->string('marital_status', 50)->nullable();
            $table->date('requested_soc_date')->nullable();
            $table->string('transport_level_assistance', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mssql_patients_test_daas', function (Blueprint $table) {
            $table->dropColumn('client_classification');
            $table->dropColumn('marital_status');
            $table->dropColumn('requested_soc_date');
            $table->dropColumn('transport_level_assistance');
        });
    }
};
