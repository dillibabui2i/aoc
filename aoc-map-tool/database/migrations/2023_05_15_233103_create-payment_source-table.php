<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentSourceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_source', function (Blueprint $table) {
            $table->id();
            $table->integer('payersource_id')->nullable();
            $table->integer('agency_id')->nullable();
            $table->string('name',255)->nullable();
			$table->boolean('is_childcare')->nullable()->default(0);
			$table->boolean('is_episodical')->nullable()->default(0);
			$table->integer('program_id')->nullable();
            $table->string('program_type',200)->nullable();
			$table->integer('payergroup_id')->nullable();
            $table->string('payergroup_name',200)->nullable();
			$table->string('caretype',200)->nullable();
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
        Schema::dropIfExists('payment_source');
    }
}
