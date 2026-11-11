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
        Schema::table('NeedToHire_Accounts', function (Blueprint $table) {
            $table->string('pay_range_rn')->nullable();
            $table->string('pay_range_lvn')->nullable();
        });
        Schema::table('NeedToHire_Accounts_Audit', function (Blueprint $table) {
            $table->string('pay_range_rn')->nullable();
            $table->string('pay_range_lvn')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('NeedToHire_Accounts', function (Blueprint $table) {
            $table->dropColumn('pay_range_rn');
            $table->dropColumn('pay_range_lvn');
        });
        Schema::table('NeedToHire_Accounts_Audit', function (Blueprint $table) {
            $table->dropColumn('pay_range_rn');
            $table->dropColumn('pay_range_lvn');
        });
    }
};
