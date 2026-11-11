<?php

namespace Tests\Support\Scenarios;

use App\Enums\MssqlRecreturTrackerStatus;
use Tests\Support\Fixtures\CandidatesFixture;
use Tests\Support\Fixtures\MssqlPatientsFixture;
use Tests\Support\Fixtures\MssqlPatientstatusFixture;
use Tests\Support\Fixtures\MssqlRecruitertrackerFixture;
use Tests\Support\ResolvesOverrides;

/**
 * A recruiter tracker row listed in the Intake Coordinator "Candidate Starts" bucket.
 *
 * Backs App\Helpers\IntakeCoordinatorStaffTransfers::getIntakeStaffTransfers() for
 * type = intake_candidate_starts. Wires mssql_recruitertracker + a non-deleted candidates
 * row + an mssql_patients row whose Patient_CurrentStatusID points to an mssql_patientstatus
 * row, so that by default every base filter of that bucket passes:
 *
 * - aoc_intake_id is not 0/null
 * - the linked candidate has is_deleted = 0
 * - Candidate_Current_Status is 'Client Match'
 * - Candidate_Start_Date is in the past (<= today, not the zero date)
 * - the linked patient has a status (mssql_patientstatus.PatientStatus = 'Test Status')
 *
 * The returned tracker row gets an extra `PatientStatus` property with the linked status text.
 * Override `Candidate_Start_Date` with a future date to get a "Pending Candidate Starts" row
 * (intake_pending_candidate_starts: status in Client Match/Current Nurse Placed/New Nurse
 * Placed/Pending Start and start date >= today). Override `PatientStatus` to set the status text.
 */
class IntakeCandidateStartTrackerScenario
{
    use ResolvesOverrides;

    public function create(array $overrides = []): object
    {
        $statusText = $overrides['PatientStatus'] ?? 'Test Status';
        unset($overrides['PatientStatus']);

        $attributes = $this->resolveOverrides([
            'aoc_intake_id' => random_int(100000, 999999),
            'aoc_candidate_id' => fn () => (new CandidatesFixture())->create()->id,
            'aoc_patient_id' => fn () => (new MssqlPatientsFixture())->create([
                'Patient_CurrentStatusID' => (new MssqlPatientstatusFixture())
                    ->create(['PatientStatus' => $statusText])->PatientStatusID,
            ])->id,
            'Candidate_Current_Status' => MssqlRecreturTrackerStatus::CLIENT_MATCH->value,
            'Candidate_Start_Date' => '2024-01-01 00:00:00',
        ], $overrides);

        $tracker = (new MssqlRecruitertrackerFixture())->create($attributes);
        $tracker->PatientStatus = $statusText;

        return $tracker;
    }
}
