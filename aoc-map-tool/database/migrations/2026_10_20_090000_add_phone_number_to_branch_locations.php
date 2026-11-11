<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPhoneNumberToBranchLocations extends Migration
{
    public function up()
    {
        Schema::table('branch_locations', function (Blueprint $table) {
            // E.164, e.g. +15705550142
            $table->string('phone_number', 16)->nullable()->after('zipcode');
        });
    }

    public function down()
    {
        Schema::table('branch_locations', function (Blueprint $table) {
            $table->dropColumn('phone_number');
        });
    }
}
