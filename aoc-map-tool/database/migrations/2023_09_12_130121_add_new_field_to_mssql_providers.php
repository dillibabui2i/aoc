<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewFieldToMssqlProviders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_providers', function (Blueprint $table) {
            $table->unsignedBigInteger('requested_status')->nullable();
            $table->string('request_outcome')->nullable();
            $table->string('request_outcome_reason')->nullable();
            $table->text('request_outcome_comment')->nullable();
            $table->string('request_pending_document')->nullable();
            $table->string('request_reason')->nullable();
            $table->date('request_date')->nullable();
            $table->unsignedBigInteger('request_by')->nullable();
            $table->datetime('request_last_followup')->nullable();
            $table->unsignedBigInteger('request_advocacy_specialist')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mssql_providers', function (Blueprint $table) {
            //
        });
    }
}
