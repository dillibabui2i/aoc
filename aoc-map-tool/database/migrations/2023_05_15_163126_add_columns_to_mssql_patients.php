<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToMssqlPatients extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_patients', function (Blueprint $table) {
            $table->timestamp('ACTIVEDATE')->nullable();
            $table->string('ADMISSIONSOURCE',500)->nullable();
            $table->timestamp('ADMIT_DATE')->nullable();
            $table->integer('ADMIT_GROUP_NO')->nullable();
            $table->timestamp('INTAKE_ACCEPTEDDATE')->nullable();
            $table->integer('INTAKE_ADDEDBY')->nullable();
            $table->timestamp('INTAKE_DATE')->nullable();
            $table->string('INTAKE_DIAGNOSIS',1000)->nullable();
            $table->string('INTAKE_DIAGNOSISCODE',1000)->nullable();
            $table->string('PAYERCODE',100)->nullable();
            



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
            //
        });
    }
}
