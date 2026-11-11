<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToCandidate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('candidates', function (Blueprint $table) {
            //
            $table->string('consentStatus', 100);
            $table->string('disposition', 100);
            $table->string('appId', 50);
            $table->string('gender', 50);
            $table->boolean('hasArtifacts');
            $table->datetime('hireDate');
            $table->string('jobCategoryName', 255);
            $table->string('jobDepartment', 255);
            $table->string('jobType', 255);
            $table->string('jobId', 50);
            $table->string('jobLocation', 255);
            $table->string('jobPostingType', 100);
            $table->string('jobPrimaryRecruiter', 255);
            $table->json('jobPrimaryRecruiterJson');
            $table->text('jobRecruiters');
            $table->json('jobRecruitersJson');
            $table->string('jobRequisitionId', 100);
            $table->string('jobTitle', 500);
            $table->string('appJobviteChannel', 255);
            $table->datetime('appLastUpdatedDate');
            $table->string('appPersonalDataProcessingStatus', 100);
            $table->string('appRace', 100);
            $table->text('appResumeContent');
            $table->string('appResumeFormat', 100);
            $table->string('appResumeName', 255);
            $table->datetime('appSentDate');
            $table->string('appSource', 255);
            $table->string('appSourceType', 255);
            $table->datetime('appStartDate');
            $table->string('appVeteranStatus', 100);
            $table->string('appWorkflowState', 255);
            $table->string('appWorkFlowStateId', 50);
            $table->boolean('bilingual');
            $table->string('languages', 255);
            $table->string('skillset', 50);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('candidate', function (Blueprint $table) {
            //
        });
    }
}
