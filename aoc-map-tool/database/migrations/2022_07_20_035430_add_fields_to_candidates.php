<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToCandidates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->datetime('workFlowStateChangeDate')->after('application_date')->nullable();
            $table->datetime('recruiterPhoneScreenDate')->after('workFlowStateChangeDate')->nullable();
            $table->datetime('PET1Date')->after('recruiterPhoneScreenDate')->nullable();
            $table->datetime('PET2Date')->after('PET1Date')->nullable();
            $table->datetime('PET3Date')->after('PET2Date')->nullable();
            $table->datetime('IPEAScheduledDate')->after('PET3Date')->nullable();
            $table->datetime('nursingSchoolGradDate')->after('IPEAScheduledDate')->nullable();
            $table->datetime('addedToPotentialNurseDate')->after('nursingSchoolGradDate')->nullable();
            $table->datetime('offerRejectedDate')->after('addedToPotentialNurseDate')->nullable();
            $table->datetime('rejectedByAgencyDate')->after('offerRejectedDate')->nullable();
            $table->text('appComments')->after('rejectedByAgencyDate')->nullable();
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
            //
        });
    }
}