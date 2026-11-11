<?php

namespace Tests\Support\Fixtures;

use App\Enums\MssqlRecreturTrackerStatus;
use App\Enums\MssqlRecruiterTrackerBonusStatus;

class MssqlRecruitertrackerFixture extends Fixture
{
    protected function table(): string
    {
        return 'mssql_recruitertracker';
    }

    protected function defaults(): array
    {
        return [
            // NOT NULL columns with no schema default — fixed, uninteresting values.
            'IPEA_Time' => '',
            'mssqlID' => 0,
            'UserID' => 1,
            'ProviderID' => 0,
            'CandidateID' => 0,
            'PatientID' => 0,
            'FacilityID' => 0,
            'is_updated' => 0,
            'whiteboard_row' => '',
            'no_candidate_match' => 0,
            'no_provider_match' => 0,
            'is_rehire' => 0,
            'tmpPayer' => '',
            'tmpStartingBucket' => '',
            'bonus_period' => '1970-01-01',
            'bonus_override_by' => 0,

            // Fields the bonus-eligibility/duplicate-check logic actually reads.
            'aoc_provider_id' => random_int(100000, 999999),
            'aoc_patient_id' => random_int(100000, 999999),
            'user_id' => random_int(1, 999999),
            'Bonus_Status' => MssqlRecruiterTrackerBonusStatus::PENDING_HOURS->value,
            'Candidate_Current_Status' => MssqlRecreturTrackerStatus::FIRST_SHIFT_CONFIRMED->value,
            'Candidate_Start_Date' => '2024-01-01 00:00:00',
            'is_deleted' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
