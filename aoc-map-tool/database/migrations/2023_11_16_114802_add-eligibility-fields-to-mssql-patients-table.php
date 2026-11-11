<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEligibilityFieldsToMssqlPatientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_patients', function (Blueprint $table) {
            $table->string('medicaid_type', 255)->nullable();
            $table->date('eligibility_end_date')->nullable();
            $table->date('time_renew_date')->nullable();
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
            $table->dropColumn('medicaid_type');
            $table->dropColumn('eligibility_end_date');
            $table->dropColumn('time_renew_date');
        });
    }
}
