<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldAcceptsCoverageToNeedToHireSchedulesTalbe extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('NeedToHire_Schedules', function (Blueprint $table) {
            $table->boolean('AcceptsCoverage')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('NeedToHire_Schedules', function (Blueprint $table) {
            $table->dropColumn('AcceptsCoverage');
        });
    }
}
