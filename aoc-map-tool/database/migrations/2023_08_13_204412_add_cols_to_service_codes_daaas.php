<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColsToServiceCodesDaaas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('service_codes_daas', function (Blueprint $table) {
            $table->decimal('ContractualRate', 13, 2)->nullable();
            $table->string('ContractualBillMode',50)->nullable();
            $table->string('UnitType',50)->nullable();
            $table->integer('Unit')->nullable();
            $table->integer('MinuteInUnit')->nullable();
            $table->integer('Category')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('service_codes_daas', function (Blueprint $table) {
            //
        });
    }
}
