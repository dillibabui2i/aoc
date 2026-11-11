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
        Schema::table('mssql_schedulemaster', function (Blueprint $table) {
            $table->dateTime('SubmittedOn')->nullable();
            $table->dateTime('ApprovedOn')->nullable();
            $table->date('ScheduleInvoiceDate')->nullable();
            $table->date('PaymentReceivedOn')->nullable();
            $table->string('SecondaryPayerSourceNames',250)->nullable();
            $table->string('TertiaryPayerSourceNames',250)->nullable();
            $table->integer('BillingFacilityID')->nullable();
            $table->integer('BillingProviderID')->nullable();
            $table->integer('BillingPatientID')->nullable();
            $table->string('MissedVisitReason',250)->nullable();
            $table->integer('is_deleted')->default(0);

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
};
