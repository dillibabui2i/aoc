<?php

namespace Tests\Support\Fixtures;

class CandidatesFixture extends Fixture
{
    protected function table(): string
    {
        return 'candidates';
    }

    protected function defaults(): array
    {
        return [
            // NOT NULL columns with no schema default — fixed, uninteresting values.
            'jobvite_email' => '',
            'consentStatus' => '',
            'disposition' => '',
            'appId' => '',
            'gender' => '',
            'hasArtifacts' => 0,
            'hireDate' => '1970-01-01 00:00:00',
            'jobCategoryName' => '',
            'jobDepartment' => '',
            'jobType' => '',
            'jobId' => '',
            'jobLocation' => '',
            'jobBranch' => '',
            'jobPostingType' => '',
            'jobPrimaryRecruiter' => '',
            'jobPrimaryRecruiterJson' => '',
            'jobRecruiters' => '',
            'jobRecruitersJson' => '',
            'jobRequisitionId' => '',
            'jobTitle' => '',
            'appJobviteChannel' => '',
            'appLastUpdatedDate' => '1970-01-01 00:00:00',
            'appPersonalDataProcessingStatus' => '',
            'appRace' => '',
            'appResumeContent' => '',
            'appResumeFormat' => '',
            'appResumeName' => '',
            'appSentDate' => '1970-01-01 00:00:00',
            'appSource' => '',
            'appSourceType' => '',
            'appStartDate' => '1970-01-01 00:00:00',
            'appVeteranStatus' => '',
            'appWorkflowState' => '',
            'appWorkFlowStateId' => '',
            'bilingual' => 0,
            'languages' => '',
            'skillset' => '',
            'workFlowStateChanged' => '1970-01-01',
            'tmpAppDates' => '',

            'firstName' => 'Test',
            'lastName' => 'Candidate',
            'is_deleted' => 0,
        ];
    }
}
