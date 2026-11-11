<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableForRenewables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('renewables', function (Blueprint $table) {
            $table->id();
            $table->integer('renewables_type')->nullable();
            $table->integer('aoc_provider_id')->nullable();
            $table->date('expiration_date')->nullable();
            $table->date('issued_date')->nullable();
            $table->string('license_no','250')->nullable();
            $table->string('license_state','100')->nullable();
            $table->boolean('mandatory')->default(false);
            $table->text('description')->nullable();
            $table->integer('created_by')->nullable()->comment('Users ID Ref');
            $table->integer('updated_by')->nullable()->comment('Users ID Ref');
            $table->timestamps();
            $table->boolean('is_deleted')->default(false);
            $table->boolean('is_updated')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('renewables');
    }
}
