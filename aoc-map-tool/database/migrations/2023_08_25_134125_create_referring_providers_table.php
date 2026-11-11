<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferringProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ReferringProviders', function (Blueprint $table) {
            $table->id('ReferringProviderID');
            $table->integer('BillingReferringProviderID')->nullable();
            $table->integer('SalesPersonID')->nullable();
            $table->string('FirstName',50)->nullable();
            $table->string('MiddleName',50)->nullable();
            $table->string('LastName',50)->nullable();
            $table->string('NPI_Number',50)->nullable();
            $table->string('Status',50)->nullable();
            $table->dateTime('CreatedOn')->nullable();
            $table->integer('EMRID');
            $table->string('ReferringProviderName',250)->nullable();
            $table->integer('is_updated')->nullable();
            $table->integer('is_deleted')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
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
        Schema::dropIfExists('ReferringProviders');
    }
}
