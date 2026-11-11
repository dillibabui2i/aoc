<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CoveragetrackerLogFieldChange extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('coverage_tracker_log', function (Blueprint $table) {
            $table->renameColumn('text_call_prn_clinicians_from_loca_prn_schedule_number', 'text_call_prn_clinicians_from_local_prn_schedule_number');
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
