<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayerRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payer_rates', function (Blueprint $table) {
            $table->id();
            $table->integer('payer_id')->nullable();
            $table->string('state',255)->nullable();
            $table->string('discipline',50)->nullable();
            $table->timestamp('effectiveFrom')->nullable();
            $table->timestamp('effectiveThrough')->nullable();
            $table->string('comment')->nullable();
            $table->decimal('rate', 13, 2)->nullable();
            $table->decimal('specRate', 13, 2)->nullable();
            $table->boolean('status')->nullable()->default(0);
            $table->integer('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->integer('updated_by')->nullable();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->integer('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
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
        Schema::dropIfExists('payer_rates');
    }
}
