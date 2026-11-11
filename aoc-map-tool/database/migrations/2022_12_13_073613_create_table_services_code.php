<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableServicesCode extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_codes', function (Blueprint $table) {
            $table->id();
            $table->integer('ServiceCodeID')->nullable();
            $table->integer('ParentServiceCodeID')->nullable();
            $table->integer('BillingServiceCodeID')->nullable();
            $table->integer('BillingParentCodeID')->nullable();
            $table->integer('ServiceTypeID')->nullable();
            $table->integer('PayerID')->nullable();
            $table->string('ServiceName',255)->nullable();
            $table->string('ServiceCode',200)->nullable();
            $table->string('PayCode',200)->nullable();
            $table->integer('FormID')->nullable();
            $table->datetime('CreatedOn')->nullable();
            $table->integer('EMRID')->nullable();
            $table->integer('DistinctServiceCodeID')->nullable();
            $table->boolean('ExistsInKanTime')->nullable()->default(0);
            $table->boolean('Active')->nullable()->default(0);
            $table->integer('ServiceCodeBridgeID')->nullable();
            $table->double('CurrentContractualRate',7,5)->nullable();
            $table->string('UnitType',200)->nullable();
            $table->integer('Unit')->nullable();
            $table->integer('MinuteInUnit')->nullable();
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
        Schema::dropIfExists('table_services_code');
    }
}

