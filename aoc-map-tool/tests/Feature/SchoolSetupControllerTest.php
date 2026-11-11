<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\Fixtures\MssqlFacilitiesFixture;
use Tests\Support\Fixtures\MssqlPatientsFixture;
use Tests\Support\Fixtures\MssqlProvidersFixture;
use Tests\Support\Fixtures\SchoolSetupRequestFixture;
use Tests\Support\Fixtures\UserFixture;
use Tests\TestCase;

/**
 * @coversDefaultClass \App\Http\Controllers\SchoolSetupController
 */
class SchoolSetupControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->findOrFail((new UserFixture())->create(['name' => 'Test Super Admin'])->id);
        $this->actingAs($this->admin);
    }

    /**
     * @test
     * @covers ::getSchoolSetUpRequest
     */
    public function it_should_redirect_guests_to_login(): void
    {
        auth()->logout();

        $this->get(route('dashboard.getSchoolSetUpsRequest'))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @covers ::getSchoolSetUpRequest
     */
    public function it_should_return_only_open_and_started_non_deleted_requests(): void
    {
        $patient = (new MssqlPatientsFixture())->create();

        $this->createRequest(['aoc_patient_id' => $patient->id, 'status' => 'Open']);
        $this->createRequest(['aoc_patient_id' => $patient->id, 'status' => 'Started']);
        $this->createRequest(['aoc_patient_id' => $patient->id, 'status' => 'Completed']);
        $this->createRequest(['aoc_patient_id' => $patient->id, 'status' => 'Open', 'is_deleted' => 1]);

        $response = $this->getJson(
            route('dashboard.getSchoolSetUpsRequest', $this->baseParams()),
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertOk()->assertJson([
            'draw' => 1,
            'recordsTotal' => 2,
            'recordsFiltered' => 2,
        ]);
    }

    /**
     * @test
     * @covers ::getSchoolSetUpRequest
     */
    public function it_should_filter_results_by_the_global_search_term(): void
    {
        $alpha = (new MssqlPatientsFixture())->create(['Patient_FirstName' => 'Alpha', 'Patient_LastName' => 'Anderson']);
        $beta = (new MssqlPatientsFixture())->create(['Patient_FirstName' => 'Beta', 'Patient_LastName' => 'Baker']);

        $this->createRequest(['aoc_patient_id' => $alpha->id]);
        $this->createRequest(['aoc_patient_id' => $beta->id]);

        $response = $this->getJson(
            route('dashboard.getSchoolSetUpsRequest', $this->baseParams(['search' => ['value' => $alpha->Patient_FirstName]])),
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
        $this->assertSame("{$alpha->Patient_FirstName} {$alpha->Patient_LastName}", $response->json('data.0.clientName'));
    }

    /**
     * @test
     * @covers ::getSchoolSetUpRequest
     */
    public function it_should_filter_results_by_branch_facility(): void
    {
        $facilityA = (new MssqlFacilitiesFixture())->create(['FacilityName' => 'Branch A']);
        $facilityB = (new MssqlFacilitiesFixture())->create(['FacilityName' => 'Branch B']);

        $patientA = (new MssqlPatientsFixture())->create(['aoc_facility_id' => $facilityA->id]);
        $patientB = (new MssqlPatientsFixture())->create(['aoc_facility_id' => $facilityB->id]);

        $this->createRequest(['aoc_patient_id' => $patientA->id]);
        $this->createRequest(['aoc_patient_id' => $patientB->id]);

        $filters = [['name' => 'open_school_setup_requests_aoc_facility_id', 'value' => $facilityA->id]];

        $response = $this->getJson(
            route('dashboard.getSchoolSetUpsRequest', $this->baseParams(['filters' => $filters])),
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
        $this->assertSame($facilityA->FacilityName, $response->json('data.0.FacilityName'));
    }

    /**
     * @test
     * @covers ::getSchoolSetUpRequest
     */
    public function it_should_limit_me_mode_results_to_requests_assigned_to_the_authenticated_user(): void
    {
        $otherCcc = (new UserFixture())->create(['name' => 'Other Coordinator']);
        $patient = (new MssqlPatientsFixture())->create();

        $this->createRequest(['aoc_patient_id' => $patient->id, 'assigned_ccc' => $this->admin->id]);
        $this->createRequest(['aoc_patient_id' => $patient->id, 'assigned_ccc' => $otherCcc->id]);

        $filters = [['name' => 'open_school_setup_requests_memode', 'value' => 1]];

        $response = $this->getJson(
            route('dashboard.getSchoolSetUpsRequest', $this->baseParams(['filters' => $filters])),
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertOk();
        $this->assertSame(1, $response->json('recordsFiltered'));
        $this->assertSame($this->admin->name, $response->json('data.0.Client_Coordinator'));
    }

    /**
     * @test
     * @covers ::getSchoolSetUpRequest
     */
    public function it_should_resolve_the_clinician_name_from_the_assigned_provider(): void
    {
        $provider = (new MssqlProvidersFixture())->create(['fullName' => 'Jane Clinician']);
        $patient = (new MssqlPatientsFixture())->create();

        $this->createRequest([
            'aoc_patient_id' => $patient->id,
            'aoc_provider_id' => $provider->id,
        ]);

        $response = $this->getJson(
            route('dashboard.getSchoolSetUpsRequest', $this->baseParams()),
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertOk();
        $this->assertSame($provider->fullName, $response->json('data.0.clinicianName'));
    }

    private function createRequest(array $overrides = []): object
    {
        return (new SchoolSetupRequestFixture())->create(array_merge([
            'assigned_ccc' => $this->admin->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ], $overrides));
    }

    private function baseParams(array $overrides = []): array
    {
        return array_merge([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'order' => [['column' => 1, 'dir' => 'asc']],
        ], $overrides);
    }
}
