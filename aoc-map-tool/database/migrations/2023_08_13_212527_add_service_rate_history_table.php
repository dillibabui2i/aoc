<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServiceRateHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_code_rates', function (Blueprint $table) {
            $table->id();
			$table->integer('agency_id')->nullable();
			$table->integer('serviceRateId')->nullable();
            $table->integer('serviceCodeId')->nullable();
            $table->timestamp('startDate')->nullable();
            $table->timestamp('endDate')->nullable();           
            $table->decimal('ContractualRate', 13, 2)->nullable();
            $table->string('ContractualBillMode',50)->nullable();
            $table->string('UnitType',50)->nullable();
            $table->integer('Unit')->nullable();
            $table->integer('MinuteInUnit')->nullable();
            $table->integer('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->integer('updated_by')->nullable();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->boolean('is_deleted')->nullable();
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
