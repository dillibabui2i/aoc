<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterIpeaBackgroungCheckToRecruiterSchedule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recruiting_schedule', function (Blueprint $table) {
            DB::statement("ALTER TABLE recruiting_schedule MODIFY COLUMN IPEA_background ENUM('Passed','Failed', 'Initiated')");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('recruiter_schedule', function (Blueprint $table) {
            //
        });
    }
}
