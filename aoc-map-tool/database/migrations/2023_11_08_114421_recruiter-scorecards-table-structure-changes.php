<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RecruiterScorecardsTableStructureChanges extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recruiter_scorecards', function (Blueprint $table) {
            $table->dropColumn('skills_social_posting');
            $table->dropColumn('skills_indeed');
            $table->dropColumn('perform_hired_nurses');
            $table->dropColumn('perform_hired_nurses_goal');
            $table->dropColumn('perform_hired_nurses_actual');

            $table->decimal('phone_screen_tone_confidence')->nullable()->after('halo_communicates');
            $table->decimal('phone_screen_aoc_values')->nullable()->after('phone_screen_tone_confidence');
            $table->decimal('phone_screen_case_specifics')->nullable()->after('phone_screen_aoc_values');
            $table->decimal('phone_screen_hh_nurse_role')->nullable()->after('phone_screen_case_specifics');
            $table->decimal('phone_screen_pay_and_benefits')->nullable()->after('phone_screen_hh_nurse_role');
            $table->decimal('phone_screen_clear_expectations_next_steps')->nullable()->after('phone_screen_pay_and_benefits');

            $table->dropColumn('halo_competitive');
            $table->dropColumn('halo_positive');
            $table->dropColumn('halo_accountable');
            $table->dropColumn('halo_collaborative');
            $table->dropColumn('halo_honest');
            $table->dropColumn('halo_growing');
            $table->dropColumn('halo_communicates');

            $table->decimal('phone_call_screening_score')->nullable()->comment('Weight - 10%')->after('phone_screen_clear_expectations_next_steps');
            $table->decimal('attendance_and_dependability')->nullable()->comment('Weight - 10%')->after('phone_call_screening_score');
            $table->decimal('professionalism')->nullable()->comment('Weight - 10%')->after('attendance_and_dependability');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('recruiter_scorecards', function (Blueprint $table) {            
            $table->decimal('skills_social_posting')->nullable()->comment('Weight - 8%')->after('skills_nth_checklists_worked');
            $table->decimal('skills_indeed')->nullable()->comment('Weight - 8%')->after('skills_jobvite');
            $table->decimal('perform_hired_nurses')->nullable()->comment('Weight - 8%')->after('perform_ipea');
            $table->decimal('perform_hired_nurses_goal')->nullable()->comment('Weight - 8%')->after('perform_ipea_goal');
            $table->decimal('perform_hired_nurses_actual')->nullable()->comment('Weight - 8%')->after('perform_ipea_actual');

            $table->dropColumn('phone_screen_tone_confidence');
            $table->dropColumn('phone_screen_aoc_values');
            $table->dropColumn('phone_screen_case_specifics');
            $table->dropColumn('phone_screen_hh_nurse_role');
            $table->dropColumn('phone_screen_pay_and_benefits');
            $table->dropColumn('phone_screen_clear_expectations_next_steps');

            $table->decimal('halo_competitive')->nullable()->comment('Weight - 2%')->after('perform_first_shift_worked_actual');
            $table->decimal('halo_positive')->nullable()->comment('Weight - 2%')->after('halo_competitive');
            $table->decimal('halo_accountable')->nullable()->comment('Weight - 2%')->after('halo_positive');
            $table->decimal('halo_collaborative')->nullable()->comment('Weight - 2%')->after('halo_accountable');
            $table->decimal('halo_honest')->nullable()->comment('Weight - 2%')->after('halo_collaborative');
            $table->decimal('halo_growing')->nullable()->comment('Weight - 2%')->after('halo_honest');
            $table->decimal('halo_communicates')->nullable()->comment('Weight - 2%')->after('halo_growing');

            $table->dropColumn('phone_call_screening_score');
            $table->dropColumn('attendance_and_dependability');
            $table->dropColumn('professionalism');
        });
    }
}
