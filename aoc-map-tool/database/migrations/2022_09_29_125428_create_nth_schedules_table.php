<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNthSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('NeedToHire_Schedules', function (Blueprint $table) {
            $table->integer('NeedToHireScheduleID');
            $table->integer('NeedToHireAccountsID');
            $table->string('StartDayOfWeek',25);
            $table->tinyInteger('StartDayOfWeekInt');
            $table->tinyInteger('StartHour');
            $table->tinyInteger('StartMinute');
            $table->tinyInteger('EndHour');
            $table->tinyInteger('EndMinute');
            $table->boolean('StartAndEndOnSameDay');
            $table->tinyInteger('ShiftType');
            $table->date('CreatedOn')->useCurrent();
            $table->integer('CreatedBy');
            $table->date('ModifiedOn')->useCurrent();
            $table->integer('ModifiedBy');
            $table->boolean('IsActive');
            $table->string('AdditionReason',500);
            $table->string('ModificationReason',500);
            $table->string('RemovalReason',500);
            $table->float('ShiftHours', 8, 2);
            $table->string('ShiftTypeDescription',100);
            $table->boolean('is_updated');
            $table->date('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('NeedToHire_Schedules');
    }
}
