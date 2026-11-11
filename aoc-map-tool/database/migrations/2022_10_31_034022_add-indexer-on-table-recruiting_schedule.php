<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexerOnTableRecruitingSchedule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recruiting_schedule', function(Blueprint $table)
        {
            $table->index(['aoc_patient_id','scheduled_date'], 'rec_index_schedule_date_pid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('recruiting_schedule', function(Blueprint $table)
        {
            $table->dropIndex('rec_index_schedule_date_pid');
        });
    }
}
