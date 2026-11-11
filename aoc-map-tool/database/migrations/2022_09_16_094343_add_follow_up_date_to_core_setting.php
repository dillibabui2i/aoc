<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddFollowUpDateToCoreSetting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('core_setting', function (Blueprint $table) {
            DB::table('core_setting')->updateOrInsert([
                'opt' => 'jobvite_follow_up_date',
                'val' => '14',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('core_setting', function (Blueprint $table) {
            //
        });
    }
}
