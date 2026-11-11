<?php

namespace Tests\Integration\Repositories\MssqlPatients;

use App\Repositories\MssqlPatients\MssqlPatientsReadRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\Fixtures\LobFixture;
use Tests\Support\Fixtures\MssqlFacilitiesFixture;
use Tests\Support\Fixtures\MssqlPatientsFixture;
use Tests\Support\Scenarios\EligibleCccCallPatientScenario;
use Tests\TestCase;

/**
 * @coversDefaultClass \App\Repositories\MssqlPatients\MssqlPatientsReadRepository
 */
class MssqlPatientsReadRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    protected MssqlPatientsReadRepository $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = $this->app->make(MssqlPatientsReadRepository::class);
    }

    /**
     * @test
     * @covers ::getById
     */
    public function it_should_return_patient(): void
    {
        $patient = (new MssqlPatientsFixture())->create([
            'ccc_id' => 42,
            'ccc_client_call_frequency' => 7,
        ]);

        $result = $this->sut->getById($patient->id);

        $this->assertNotNull($result);
        $this->assertSame($patient->id, $result->id);
        $this->assertSame($patient->ccc_id, $result->ccc_id);
        $this->assertSame($patient->ccc_client_call_frequency, $result->ccc_client_call_frequency);
    }

    /**
     * @test
     * @covers ::getById
     */
    public function it_should_return_null_when_patient_not_found(): void
    {
        $this->assertNull($this->sut->getById(999999999));
    }

    /**
     * @test
     * @covers ::getEligibleForCccCalls
     */
    public function it_should_include_patient_matching_all_eligibility_criteria(): void
    {
        $id = $this->createEligiblePatient();
        $result = $this->sut->getEligibleForCccCalls('2024-02-01');

        $this->assertTrue($result->pluck('id')->contains($id));
    }

    /**
     * @test
     * @covers ::getEligibleForCccCalls
     * @dataProvider excludedLineOfBusinessProvider
     */
    public function it_should_exclude_patient_by_line_of_business(string $lobName): void
    {
        $lob = (new LobFixture())->create(['LOBName' => $lobName]);
        $id = $this->createEligiblePatient(['LOBID' => $lob->LOBID]);
        $result = $this->sut->getEligibleForCccCalls('2024-02-01');

        $this->assertFalse($result->pluck('id')->contains($id));
    }

    public function excludedLineOfBusinessProvider(): array
    {
        return [
            'PT' => ['PT'],
            'OT' => ['OT'],
            'ST' => ['ST'],
        ];
    }

    /**
     * @test
     * @covers ::getEligibleForCccCalls
     * @dataProvider excludedFacilityIdProvider
     */
    public function it_should_exclude_patient_by_blacklisted_facility(int $facilityId): void
    {
        $id = $this->createEligiblePatient(['aoc_facility_id' => $facilityId]);
        $result = $this->sut->getEligibleForCccCalls('2024-02-01');

        $this->assertFalse($result->pluck('id')->contains($id));
    }

    public function excludedFacilityIdProvider(): array
    {
        return [
            'facility 14' => [14],
            'facility 43' => [43],
            'facility 44' => [44],
        ];
    }

    /**
     * @test
     * @covers ::getEligibleForCccCalls
     */
    public function it_should_exclude_prn_patient(): void
    {
        $id = $this->createEligiblePatient(['isPRN' => 1]);
        $result = $this->sut->getEligibleForCccCalls('2024-02-01');

        $this->assertFalse($result->pluck('id')->contains($id));
    }

    /**
     * @test
     * @covers ::getEligibleForCccCalls
     * @dataProvider excludedSearchNameProvider
     */
    public function it_should_exclude_patient_by_blacklisted_search_name(string $searchName): void
    {
        $id = $this->createEligiblePatient(['Search_Name' => $searchName]);
        $result = $this->sut->getEligibleForCccCalls('2024-02-01');

        $this->assertFalse($result->pluck('id')->contains($id));
    }

    public function excludedSearchNameProvider(): array
    {
        return [
            'contains Test' => ['John Test Smith'],
            'contains KanTime' => ['KanTime Import'],
            'contains PRN' => ['Jane PRN Doe'],
        ];
    }

    /**
     * @test
     * @covers ::getEligibleForCccCalls
     */
    public function it_should_exclude_colorado_facility_unless_lob_is_1023(): void
    {
        $coFacility = (new MssqlFacilitiesFixture())->create(['State' => 'CO']);
        $excludedId = $this->createEligiblePatient(['aoc_facility_id' => $coFacility->id]);
        $lob = (new LobFixture())->create(['LOBID' => 1023, 'LOBName' => 'RN']);
        $includedId = $this->createEligiblePatient([
            'aoc_facility_id' => $coFacility->id,
            'LOBID' => $lob->LOBID,
        ]);

        $result = $this->sut->getEligibleForCccCalls('2024-02-01');

        $this->assertFalse($result->pluck('id')->contains($excludedId));
        $this->assertTrue($result->pluck('id')->contains($includedId));
    }

    /**
     * @test
     * @covers ::getEligibleForCccCalls
     */
    public function it_should_exclude_patient_created_on_or_after_month_start(): void
    {
        $id = $this->createEligiblePatient(['created_at' => '2024-02-01 00:00:00']);
        $result = $this->sut->getEligibleForCccCalls('2024-02-01');

        $this->assertFalse($result->pluck('id')->contains($id));
    }

    /**
     * @test
     * @covers ::getEligibleForCccCalls
     * @dataProvider excludedStatusProvider
     */
    public function it_should_exclude_patient_with_status_outside_allowed_list(int $statusId): void
    {
        $id = $this->createEligiblePatient(['Patient_CurrentStatusID' => $statusId]);
        $result = $this->sut->getEligibleForCccCalls('2024-02-01');

        $this->assertFalse($result->pluck('id')->contains($id));
    }

    public function excludedStatusProvider(): array
    {
        return [
            'status name 1' => [2],
            'status name 2' => [3],
            'status name 3' => [9],
        ];
    }

    /**
     * @test
     * @covers ::getEligibleForCccCalls
     */
    public function it_should_exclude_patient_when_not_latest_admit(): void
    {
        $id = $this->createEligiblePatient(['IsLatestAdmit' => 0]);
        $result = $this->sut->getEligibleForCccCalls('2024-02-01');

        $this->assertFalse($result->pluck('id')->contains($id));
    }

    /**
     * @test
     * @covers ::getEligibleForCccCalls
     * @dataProvider excludedPatientIdProvider
     */
    public function it_should_exclude_patient_with_missing_patient_id(?int $patientId): void
    {
        $id = $this->createEligiblePatient(['PatientID' => $patientId]);
        $result = $this->sut->getEligibleForCccCalls('2024-02-01');

        $this->assertFalse($result->pluck('id')->contains($id));
    }

    public function excludedPatientIdProvider(): array
    {
        return [
            'zero' => [0],
            'null' => [null],
        ];
    }

    private function createEligiblePatient(array $overrides = []): int
    {
        return (new EligibleCccCallPatientScenario())->create($overrides)->id;
    }
}
