<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterIpeaBackgroungCheckForRefusedToRecruiterSchedule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recruiting_schedule', function (Blueprint $table) {
            DB::statement("ALTER TABLE recruiting_schedule MODIFY COLUMN IPEA_background ENUM('Passed','Failed','Refused','Initiated')");
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
