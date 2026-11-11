<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeFieldTypeForPayerEffectiveDatesInnNeedtoHireAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('NeedToHire_Accounts', function (Blueprint $table) {
            $table->date('payerEffectiveDates')->nullable()->change();
        });
        Schema::table('NeedToHire_Accounts_Audit', function (Blueprint $table) {
            $table->date('payerEffectiveDates')->nullable()->change();
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
