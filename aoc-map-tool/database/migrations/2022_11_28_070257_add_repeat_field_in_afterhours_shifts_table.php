<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRepeatFieldInAfterhoursShiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('afterhours_shifts', function (Blueprint $table) {
            $table->string('repeat_on')->nullable()->after('repeat');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('afterhours_shifts', function (Blueprint $table) {
            //
            $table->dropColumn('repeat_on');
        });
    }
}
