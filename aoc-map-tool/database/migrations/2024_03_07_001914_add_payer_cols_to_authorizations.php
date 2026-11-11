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
        Schema::table('authorizations', function (Blueprint $table) {
            $table->boolean('is_respite')->default(0);
            $table->boolean('is_invalid')->default(0);
            $table->integer('PatientID')->nullable();
            $table->integer('auth_entered_by')->nullable();
            $table->integer('auth_requested_by')->nullable();
            $table->integer('auth_ack_by')->nullable();
            $table->date('auth_received_date')->nullable();
            $table->date('auth_created_date')->nullable();
            $table->date('auth_requested_date')->nullable();
            $table->date('auth_ack_date')->nullable();
            $table->string('priority_type',10)->nullable();

        });
        Schema::table('mssql_patients', function (Blueprint $table) {
            $table->integer('active_auth_id')->nullable();
            $table->integer('active_respite_auth_id')->nullable();
            $table->integer('future_auth_id')->nullable();
            $table->integer('future_respite_auth_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        echo 'reverse migration';
    }
};
