<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToRecruiterScorecardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recruiter_scorecards', function (Blueprint $table) {
            $table->decimal('score')->change();
            $table->decimal('skills_recruiter_dashboard')->change();
            $table->decimal('skills_nth_checklists_worked')->change();
            $table->decimal('skills_social_posting')->change();
            $table->decimal('skills_jobvite')->change();
            $table->decimal('skills_indeed')->change();
            $table->decimal('perform_headhunting')->change();
            $table->decimal('perform_ipea')->change();
            $table->decimal('perform_hired_nurses')->change();
            $table->decimal('perform_conversion')->change();
            $table->decimal('perform_pepiwepi')->change();
            $table->decimal('perform_hrsremoved')->change();
            $table->decimal('perform_first_shift_worked')->change();
            $table->decimal('halo_competitive')->change();
            $table->decimal('halo_positive')->change();
            $table->decimal('halo_accountable')->change();
            $table->decimal('halo_collaborative')->change();
            $table->decimal('halo_honest')->change();
            $table->decimal('halo_growing')->change();
            $table->decimal('halo_communicates')->change();

            $table->decimal('perform_headhunting_goal')->nullable()->after('perform_first_shift_worked');
            $table->decimal('perform_ipea_goal')->nullable()->after('perform_headhunting_goal');
            $table->decimal('perform_hired_nurses_goal')->nullable()->after('perform_ipea_goal');
            $table->decimal('perform_conversion_goal')->nullable()->after('perform_hired_nurses_goal');
            $table->decimal('perform_pepiwepi_goal')->nullable()->after('perform_conversion_goal');
            $table->decimal('perform_hrsremoved_goal')->nullable()->after('perform_pepiwepi_goal');
            $table->decimal('perform_first_shift_worked_goal')->nullable()->after('perform_hrsremoved_goal');

            $table->decimal('perform_headhunting_actual')->nullable()->after('perform_first_shift_worked_goal');
            $table->decimal('perform_ipea_actual')->nullable()->after('perform_headhunting_actual');
            $table->decimal('perform_hired_nurses_actual')->nullable()->after('perform_ipea_actual');
            $table->decimal('perform_conversion_actual')->nullable()->after('perform_hired_nurses_actual');
            $table->decimal('perform_pepiwepi_actual')->nullable()->after('perform_conversion_actual');
            $table->decimal('perform_hrsremoved_actual')->nullable()->after('perform_pepiwepi_actual');
            $table->decimal('perform_first_shift_worked_actual')->nullable()->after('perform_hrsremoved_actual');

            $table->string('action', 255)->nullable()->after('grade');
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
            $table->dropColumn('ccc_intro_call');
            $table->dropColumn('ccc_intro_call_made_at');
            $table->dropColumn('ccc_intro_call_made_by');
        });
    }
}
