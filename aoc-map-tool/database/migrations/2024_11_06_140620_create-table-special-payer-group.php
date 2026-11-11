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
        Schema::create('special_payer_group', function (Blueprint $table) {
            $table->id();
            $table->integer('SpecialPayerGroupMappingID')->nullable();
            $table->string('SpecialPayerGroupName')->nullable();
            $table->integer('InsuranceID')->nullable();
            $table->integer('BillingInsuranceID')->nullable();
			$table->string('PatientAccount')->nullable();
            $table->timestamp('CreatedOn')->useCurrent()->nullable();
			$table->string('CreatedBy')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('special_payer_group');
    }
};
