<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIpeaFieldsToRecruitingSchedule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recruiting_schedule', function (Blueprint $table) {
            $table->enum('IPEA_Confirmed', ['Yes', 'No'])->after('description')->nullable();
            $table->string('IPEA_lunch_preference', 255)->after('IPEA_Confirmed')->nullable();
            $table->enum('IPEA_clinician_arrived', ['Yes', 'NC/NS', 'Rescheduled'])->after('IPEA_lunch_preference')->nullable();
            $table->enum('IPEA_drug_test', ['Passed', 'Failed'])->after('IPEA_clinician_arrived')->nullable();
            $table->enum('IPEA_background', ['Passed', 'Failed'])->after('IPEA_drug_test')->nullable();
            $table->enum('IPEA_interview', ['Good Fit', 'Suggest Other Clients'])->after('IPEA_background')->nullable();
            $table->enum('IPEA_competency_check', ['Passed', 'Failed'])->after('IPEA_interview')->nullable();
            $table->enum('IPEA_tpn_class', ['Scheduled', 'N/A'])->after('IPEA_competency_check')->nullable();
            $table->enum('IPEA_trachvent_class', ['Scheduled', 'N/A'])->after('IPEA_tpn_class')->nullable();
            $table->enum('IPEA_onboarding_status', ['Ready To Work', 'Incomplete'])->after('IPEA_trachvent_class')->nullable();
            $table->string('IPEA_pending', 255)->after('IPEA_onboarding_status')->nullable();
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
