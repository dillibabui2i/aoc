<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameFieldsByRecruitingSchedule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recruiting_schedule', function (Blueprint $table) {
            $table->renameColumn('first_followup_completed',"first_followup_outcome");
            $table->renameColumn('sevenday_followup_completed',"sevenday_followup_outcome");
            $table->renameColumn('thirtyday_followup_completed',"thirtyday_followup_outcome");
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
