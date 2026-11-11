<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AlterIpeaFieldsToRecruitingSchedule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recruiting_schedule', function (Blueprint $table) {
            DB::statement("ALTER TABLE recruiting_schedule MODIFY COLUMN IPEA_tpn_class ENUM('Scheduled','Completed', 'N/A')");
            DB::statement("ALTER TABLE recruiting_schedule MODIFY COLUMN IPEA_trachvent_class ENUM('Scheduled','Completed', 'N/A')");
            DB::statement("ALTER TABLE recruiting_schedule MODIFY COLUMN IPEA_interview ENUM('Good Fit', 'Suggest Other Clients','Not Qualified')");
            DB::statement("ALTER TABLE recruiting_schedule MODIFY COLUMN IPEA_onboarding_status ENUM('Ready To Work', 'Incomplete','Ineligible for Rehire')");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('recruiting_schedule', function (Blueprint $table) {
            //
        });
    }
}