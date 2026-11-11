<?php

namespace Tests\Support\Fixtures;

class MssqlPatientsFixture extends Fixture
{
    protected function table(): string
    {
        return 'mssql_patients';
    }

    protected function defaults(): array
    {
        return [
            'ccc_id' => null,
            'ccc_client_call_frequency' => 0,
            'isPRN' => 0,
            'Search_Name' => 'Test Patient',
            'testPayer' => '',
            'PrimarycarePhysicianID' => '',
            'created_at' => now(),
        ];
    }

    /**
     * Patient-level attributes required by MssqlPatientsReadRepository::getEligibleForCccCalls().
     * Doesn't set aoc_facility_id/LOBID — those need rows in other tables, wired by the
     * caller (see MssqlPatientsReadRepositoryTest::createEligiblePatient()).
     */
    public function eligibleForCccCalls(): static
    {
        return $this->state([
            'isPRN' => 0,
            'Search_Name' => 'Eligible Patient',
            'created_at' => '2024-01-01 00:00:00',
            'Patient_CurrentStatusID' => 1,
            'IsLatestAdmit' => 1,
            'PatientID' => random_int(100000, 999999),
        ]);
    }
}
