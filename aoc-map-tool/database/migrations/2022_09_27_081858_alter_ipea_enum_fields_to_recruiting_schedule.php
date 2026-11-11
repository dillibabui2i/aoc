<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AlterIpeaEnumFieldsToRecruitingSchedule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recruiting_schedule', function (Blueprint $table) {
            DB::statement("ALTER TABLE recruiting_schedule MODIFY COLUMN IPEA_drug_test ENUM('Passed','Failed', 'Refused')");
            DB::statement("ALTER TABLE recruiting_schedule MODIFY COLUMN IPEA_trachvent_class ENUM('Scheduled','Completed', 'N/A','Needs to be scheduled')");
            DB::statement("ALTER TABLE recruiting_schedule MODIFY COLUMN IPEA_tpn_class ENUM('Scheduled','Completed', 'N/A','Needs to be scheduled')");
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
