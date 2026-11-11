<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldToNeedToAccountAndPatient extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('NeedToHire_Accounts', function (Blueprint $table) {
            $table->integer('attempt_count')->nullable();
            $table->string('outcome')->nullable();
            $table->datetime('requested_callback')->nullable();
        });

        Schema::table('mssql_patients', function (Blueprint $table) {
            $table->integer('attempt_count')->nullable();
            $table->string('outcome')->nullable();
            $table->datetime('requested_callback')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('need_to_account_and_patient', function (Blueprint $table) {
            //
        });
    }
}
