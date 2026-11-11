<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientChangingPayerInNeedtoHireAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('NeedToHire_Accounts', function (Blueprint $table) {
            $table->boolean('isClientChangingPayer')->default(0);
            $table->string('newTerminalPayer',100)->nullable();
            $table->string('payerEffectiveDates',100)->nullable();
            $table->string('newRespiteTerminalPayer',50)->nullable();
        });
        Schema::table('NeedToHire_Accounts_Audit', function (Blueprint $table) {
            $table->boolean('isClientChangingPayer')->default(0);
            $table->string('newTerminalPayer',100)->nullable();
            $table->string('payerEffectiveDates',100)->nullable();
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
        Schema::table('needto_hire_accounts', function (Blueprint $table) {
            //
        });
    }
}
