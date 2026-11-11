<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTablePatientForAuthorize extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_patients', function (Blueprint $table) {
            $table->date('authorization_from')->nullable();
            $table->date('authorization_to')->nullable();
            $table->date('respite_authorization_from')->nullable();
            $table->date('respite_authorization_to')->nullable();
            $table->string('authorization_status_future')->nullable();
            $table->string('authorization_status_current')->nullable();
            $table->string('authorization_outcome_future')->nullable();
            $table->string('authorization_outcome_current')->nullable();
            $table->string('authorization_reason_future')->nullable();
            $table->string('authorization_reason_current')->nullable();
            $table->string('respite_authorization_status_future')->nullable();
            $table->string('respite_authorization_status_current')->nullable();
            $table->string('respite_authorization_outcome_future')->nullable();
            $table->string('respite_authorization_outcome_current')->nullable();
            $table->string('respite_authorization_reason_future')->nullable();
            $table->string('respite_authorization_reason_current')->nullable();
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
