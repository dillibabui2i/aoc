<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecruiterScorecardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recruiter_scorecards', function (Blueprint $table) {
            $table->id();
            $table->integer('recruiter_id')->nullable();
            $table->integer('manager_id')->nullable();
            $table->date('scorecard_date')->nullable();
            $table->integer('score')->nullable();
            $table->string('grade',1)->nullable();
            $table->integer('skills_recruiter_dashboard')->nullable()->comment('Weight - 7%');
            $table->integer('skills_nth_checklists_worked')->nullable()->comment('Weight - 8%');
            $table->integer('skills_social_posting')->nullable()->comment('Weight - 8%');
            $table->integer('skills_jobvite')->nullable()->comment('Weight - 8%');
            $table->integer('skills_indeed')->nullable()->comment('Weight - 5%');
            $table->integer('perform_headhunting')->nullable()->comment('Weight - 5%');
            $table->integer('perform_ipea')->nullable()->comment('Weight - 8%');
            $table->integer('perform_hired_nurses')->nullable()->comment('Weight - 8%');
            $table->integer('perform_conversion')->nullable()->comment('Weight - 5%');
            $table->integer('perform_pepiwepi')->nullable()->comment('Weight - 2%');
            $table->integer('perform_hrsremoved')->nullable()->comment('Weight - 2%');
            $table->integer('perform_first_shift_worked')->nullable()->comment('Weight - 20%');
            $table->integer('halo_competitive')->nullable()->comment('Weight - 2%');
            $table->integer('halo_positive')->nullable()->comment('Weight - 2%');
            $table->integer('halo_accountable')->nullable()->comment('Weight - 2%');
            $table->integer('halo_collaborative')->nullable()->comment('Weight - 2%');
            $table->integer('halo_honest')->nullable()->comment('Weight - 2%');
            $table->integer('halo_growing')->nullable()->comment('Weight - 2%');
            $table->integer('halo_communicates')->nullable()->comment('Weight - 2%');
            $table->datetime('submitted_at')->nullable();
            $table->integer('submitted_by')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('recruiter_scorecards');
    }
}
