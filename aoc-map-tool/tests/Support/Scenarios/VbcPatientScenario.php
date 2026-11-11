<?php

namespace Tests\Support\Scenarios;

use App\MssqlPatients;
use Illuminate\Support\Str;
use Tests\Support\Fixtures\MssqlFacilitiesFixture;
use Tests\Support\Fixtures\MssqlPatientsFixture;
use Tests\Support\ResolvesOverrides;

/**
 * A patient that the VBC read repositories and controllers can pick up.
 *
 * Backs app/VBC (Hospitalization, Care Coordination, Follow-up, Sick Plan, tracker sub-resources),
 * which join encounters/trackers to mssql_patients and mssql_facilities. Wires mssql_patients + a
 * linked mssql_facilities row so that, by default:
 *
 * - IsLatestAdmit = 1 and is_deleted = 0 (the only admit row the UI can reach)
 * - Patient_State = 'TX', and the facility is in the same state
 * - PatientName, PatientAccount and PatientID are set and unique per call, so a search by any of them
 *   matches exactly one patient
 * - aoc_facility_id points at its own freshly created facility (own FacilityName, own region), so two
 *   patients never share a branch or a region
 *
 * Pass $overrides to break one of these, e.g. `['Patient_State' => 'CO']` or
 * `['aoc_facility_id' => $facility->id]` to put two patients into the same facility.
 * Returns the Eloquent model (not a bare row), because the tests read `->facility`.
 */
class VbcPatientScenario
{
    use ResolvesOverrides;

    private static int $regionSequence = 1000;

    public function create(array $overrides = []): MssqlPatients
    {
        $unique = Str::upper(Str::random(8));
        $state = $overrides['Patient_State'] ?? 'TX';

        $attributes = $this->resolveOverrides([
            'aoc_facility_id' => fn () => $this->createFacility($state)->id,
            'Patient_State' => $state,
            'PatientID' => random_int(100000, 999999),
            'PatientAccount' => "ACC{$unique}",
            'PatientName' => "Patient {$unique}",
            'Search_Name' => "Patient {$unique}",
            'IsLatestAdmit' => 1,
        ], $overrides);

        $row = (new MssqlPatientsFixture())->create($attributes);

        return MssqlPatients::query()->findOrFail($row->id);
    }

    public function createInState(string $state): MssqlPatients
    {
        return $this->create(['Patient_State' => $state]);
    }

    /** Two patients in different facilities and regions. @return array{0: MssqlPatients, 1: MssqlPatients} */
    public function createPair(): array
    {
        return [$this->create(), $this->create()];
    }

    private function createFacility(string $state): object
    {
        return (new MssqlFacilitiesFixture())->create([
            'State' => $state,
            'FacilityName' => 'Facility ' . Str::upper(Str::random(8)),
            'BranchName' => 'Branch ' . Str::upper(Str::random(8)),
            'region' => ++self::$regionSequence,
        ]);
    }
}
