<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NeedToHireAuditTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('NeedToHire_Audit', function (Blueprint $table) {
            $table->id();
            $table->integer('aoc_nthaccounts_id');
            $table->string('StartDayOfWeek',25);
            $table->tinyInteger('StartDayOfWeekInt');
            $table->tinyInteger('StartHour');
            $table->tinyInteger('StartMinute');
            $table->tinyInteger('EndHour');
            $table->tinyInteger('EndMinute');
            $table->boolean('StartAndEndOnSameDay');
            $table->tinyInteger('ShiftType');
            $table->string('Type',1);
            $table->string('Reason',500);
            $table->float('ShiftHours', 8, 2);
            $table->string('ShiftTypeDescription',100);
            $table->string('ChangeDescription',100);
            $table->date('created_at')->useCurrent();
            $table->integer('created_by');
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
