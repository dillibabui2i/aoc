<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGoalSettingProfessioanlDevelopmentFieldToOfficeEvaluation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('office_evaluations', function (Blueprint $table) {
            $table->text('goal_setting_professional_development')->nullable()->after('development_areas');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('office_evaluations', function (Blueprint $table) {
            $table->dropColumn('has_client_over21');
        });
    }
}
