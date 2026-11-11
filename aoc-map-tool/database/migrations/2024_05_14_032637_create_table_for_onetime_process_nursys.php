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
        Schema::create('nursys_transaction_onetime', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_type');
            $table->text('transaction_id');
        });

        Schema::create('nursys_errors_log', function (Blueprint $table) {
            $table->id();
            $table->integer('aoc_provider_id')->nullable();
            $table->text('error_log'); 
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('table_for_onetime_process_nursys');
    }
};
