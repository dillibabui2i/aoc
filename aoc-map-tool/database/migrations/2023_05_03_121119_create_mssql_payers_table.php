<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMssqlPayersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mssql_payers', function (Blueprint $table) {
            $table->id();
            $table->integer('InsuranceID');
            $table->integer('BillingInsuranceID')->nullable();
            $table->string('PayerName', 50)->nullable();
            $table->integer('ProgramTypeID')->nullable();
            $table->integer('PayerGroupID')->nullable();
            $table->integer('PayerFinancialClassID')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mssql_payers');
    }
}
