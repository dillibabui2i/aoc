<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterEnumTypeForOnboardingStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recruiting_schedule', function (Blueprint $table) {
            DB::statement("ALTER TABLE recruiting_schedule MODIFY COLUMN IPEA_onboarding_status ENUM('Ready To Work','Incomplete','Ineligible for Hire/Rehire','Inactive')");
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
