<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAocNthaccountsIdInNeedToHireChecklistTable extends Migration
{
    /**
     * Run the migrations.
     * @author Nayan
     * @return void
     */
    public function up()
    {
        Schema::table('NeedToHire_Checklist', function (Blueprint $table) {
            //
            $table->bigInteger('aoc_nthaccounts_id')->after('NeedToHireAccountsID')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('NeedToHire_Checklist', function (Blueprint $table) {
            //
            $table->dropColumn('aoc_nthaccounts_id');
        });
    }
}
