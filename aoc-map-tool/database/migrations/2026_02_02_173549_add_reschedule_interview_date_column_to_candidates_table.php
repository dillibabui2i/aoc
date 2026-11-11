<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dateTime('rescheduleInterviewDate')->nullable()->after('scheduleInterviewDate');
            $table->unsignedBigInteger('interviewManager')->nullable();
            $table->text('outlookEventId')->nullable()->after('recruiterPhoneScreenDate');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn('rescheduleInterviewDate');
            $table->dropColumn('interviewManager');
            $table->dropColumn('outlookEventId');
        });
    }
};
