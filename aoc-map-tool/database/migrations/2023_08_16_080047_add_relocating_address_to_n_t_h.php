<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRelocatingAddressToNTH extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('NeedToHire_Accounts', function (Blueprint $table) {
            $table->boolean('clientIsRelocating')->default(0);
            $table->string('relocatingAddress1',100)->nullable();
            $table->string('relocatingAddress2',100)->nullable();
            $table->string('relocatingCity',50)->nullable();
            $table->string('relocatingState',50)->nullable();
            $table->string('relocatingZipCode',50)->nullable();
        });
        Schema::table('NeedToHire_Accounts_Audit', function (Blueprint $table) {
            $table->boolean('clientIsRelocating')->default(0);
            $table->string('relocatingAddress1',100)->nullable();
            $table->string('relocatingAddress2',100)->nullable();
            $table->string('relocatingCity',50)->nullable();
            $table->string('relocatingState',50)->nullable();
            $table->string('relocatingZipCode',50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {}
}
