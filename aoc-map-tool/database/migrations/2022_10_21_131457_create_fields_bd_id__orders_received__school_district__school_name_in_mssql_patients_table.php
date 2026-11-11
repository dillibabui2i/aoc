<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFieldsBdIdOrdersReceivedSchoolDistrictSchoolNameInMssqlPatientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_patients', function (Blueprint $table) {
            $table->integer('bd_id')->nullable();
            $table->tinyInteger('OrdersReceived')->default(0);
            $table->string('SchoolDistrict', 200)->nullable()->default('');
            $table->string('SchoolName', 200)->nullable()->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mssql_patients', function (Blueprint $table) {
            $table->dropColumn('bd_id');
            $table->dropColumn('OrdersReceived');
            $table->dropColumn('SchoolDistrict');
            $table->dropColumn('SchoolName');
        });
    }
}
