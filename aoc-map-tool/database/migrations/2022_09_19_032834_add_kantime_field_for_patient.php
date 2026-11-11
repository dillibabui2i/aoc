<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKantimeFieldForPatient extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_patients', function (Blueprint $table) {
            $table->integer('rdod_id')->nullable()->comment('Regional Director of Operations');
            $table->integer('dbo_id')->nullable()->comment('Director of Branch Operations');
            $table->integer('ccc_id')->nullable()->comment('Client Care Coordinator');
            $table->integer('ic_id')->nullable()->comment('Intake Coordinator');
            $table->integer('r_id')->nullable()->comment('Recruiter');
            $table->integer('ccm_id')->nullable()->comment('Clinical Care Manager');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
