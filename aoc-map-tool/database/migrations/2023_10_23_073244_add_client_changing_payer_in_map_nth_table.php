<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientChangingPayerInMapNthTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('maps_nth', function (Blueprint $table) {
            //
            $table->boolean('isClientChangingPayer')->default(0);
            $table->string('newTerminalPayer',100)->nullable();
            $table->date('payerEffectiveDates')->nullable();
            $table->string('newRespiteTerminalPayer',50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('maps_nth', function (Blueprint $table) {
            //
        });
    }
}
