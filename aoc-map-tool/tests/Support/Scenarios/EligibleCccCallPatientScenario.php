<?php

namespace Tests\Support\Scenarios;

use Tests\Support\Fixtures\LobFixture;
use Tests\Support\Fixtures\MssqlFacilitiesFixture;
use Tests\Support\Fixtures\MssqlPatientsFixture;
use Tests\Support\ResolvesOverrides;

/**
 * A patient eligible for CCC (Client Care Coordinator) outreach calls.
 *
 * Backs App\Repositories\MssqlPatients\MssqlPatientsReadRepository::getEligibleForCccCalls().
 * Wires mssql_patients + a linked mssql_facilities row + a linked lob row so that, by
 * default, every one of the repository's filters passes:
 *
 * - Line of business (lob.LOBName) is not PT/OT/ST
 * - aoc_facility_id is not in the hard-coded exclusion list (14, 43, 44)
 * - isPRN = 0 (not an as-needed patient)
 * - Search_Name doesn't contain "Test" / "KanTime" / "PRN"
 * - Facility State isn't Colorado, UNLESS LOBID = 1023 (explicit carve-out)
 * - created_at is before the queried month start
 * - Patient_CurrentStatusID is one of 1, 6, 7, 8
 * - IsLatestAdmit = 1
 * - PatientID is set (not 0/null)
 *
 * Pass $overrides to violate exactly one of the rules above and get an *ineligible*
 * patient for a specific exclusion test — see MssqlPatientsReadRepositoryTest for the
 * full set of exclusion cases exercised this way.
 */
class EligibleCccCallPatientScenario
{
    use ResolvesOverrides;

    public function create(array $overrides = []): object
    {
        $attributes = $this->resolveOverrides([
            'aoc_facility_id' => fn () => (new MssqlFacilitiesFixture())->create()->id,
            'LOBID' => fn () => (new LobFixture())->create()->LOBID,
        ], $overrides);

        return (new MssqlPatientsFixture())->eligibleForCccCalls()->create($attributes);
    }
}
