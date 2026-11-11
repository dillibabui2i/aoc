<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToNeedToHireAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('NeedToHire_Accounts', function (Blueprint $table) {
            $table->dateTime('HoursAddedOn')->after('IntakeCoordinator')->nullable();
            $table->integer('aoc_patient_id')->after('id')->default(0);
            $table->integer('user_id')->after('aoc_patient_id')->default(0);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
