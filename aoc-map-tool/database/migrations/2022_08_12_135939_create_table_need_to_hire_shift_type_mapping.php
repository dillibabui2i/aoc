<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableNeedToHireShiftTypeMapping extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('NeedToHire_ShiftTypeMapping', function (Blueprint $table) {
            $table->id();
			$table->integer('NeedToHireShiftTypeMappingID')->nullable();
			$table->string('StartDayOfWeek', 25)->default('');
			$table->integer('StartDayOfWeekInt')->nullable();
			$table->string('StartTimeAMPM', 2)->default('');
			$table->string('EndTimeAMPM', 2)->default('');
			$table->integer('ShiftType')->nullable();
            $table->integer('CreatedOn')->nullable();
			$table->integer('CreatedBy')->nullable();
            $table->timestamp('ModifiedOn')->useCurrent();
            $table->integer('ModifiedBy')->nullable();
			$table->boolean('is_deleted')->default(0);
			$table->boolean('is_updated')->default(0);
			$table->boolean('IsActive')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('NeedToHire_ShiftTypeMapping');
    }
}
