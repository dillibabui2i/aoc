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
        Schema::create('payer_rate_services', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payer_rate_id')->constrained('payer_rates')->cascadeOnDelete();
            $table->string('service_name');
            $table->decimal('rate', 13, 2)->nullable();
            $table->decimal('pending_rate', 13, 2)->nullable();
            $table->boolean('pending_deletion')->default(false);
            $table->unique(['payer_rate_id', 'service_name']);
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
        Schema::dropIfExists('payer_rate_services');
    }
};
