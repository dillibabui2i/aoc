<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIPEAInHomeCargiverToRecruitingScheduleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recruiting_schedule', function (Blueprint $table) {
            $table->enum('IPEA_in_home_cargiver', ['yes', 'no'])->nullable();
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
            $table->dropColumn('IPEA_in_home_cargiver');
        });
    }
}
