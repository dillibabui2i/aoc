<?php

namespace Tests\Feature\VBC;

use App\MssqlPatients;
use App\User;
use App\VBC\DTO\CareCoordination\CareCoordinationFilterDTO;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Http\Resources\CareCoordination\CareCoordinationCollection;
use App\VBC\Models\CareCoordination\CareCoordinationModel;
use App\VBC\Models\Encounter\EncounterModel;
use App\VBC\Repositories\CareCoordination\CareCoordinationReadRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\Fixtures\CareCoordinationTypeFixture;
use Tests\Support\Fixtures\UserFixture;
use Tests\Support\Scenarios\VbcPatientScenario;
use Tests\TestCase;

/**
 * Behavioral tests for the Care Coordination bucket read repository.
 *
 * A care coordination is tied to an Encounter (which holds the patient) and a
 * care_coordination_type. Runs against the existing dev DB; DatabaseTransactions rolls
 * everything back per test. Fixtures are isolated by a non-seed assignee (ids 1/11) and
 * queries are scoped by that assignee, so assertions never depend on pre-existing rows.
 *
 * Grouping: scoping baseline -> filters -> search -> sorting -> regression -> response shape.
 */
class CareCoordinationReadRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private CareCoordinationReadRepository $repository;
    private User $assignee;
    private User $otherUser;
    private ?MssqlPatients $defaultPatient = null;
    private ?int $typeId = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(CareCoordinationReadRepository::class);
        $this->assignee = $this->createUser();
        $this->otherUser = $this->createUser();
    }

    // --- scoping baseline ---------------------------------------------------

    public function test_query_returns_only_care_coordinations_assigned_to_the_given_assignee(): void
    {
        $a = $this->makeCareCoordination();
        $b = $this->makeCareCoordination();

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $this->ids($this->dto()));
    }

    // --- filters ------------------------------------------------------------

    public function test_is_completed_filter_returns_only_completed_when_true(): void
    {
        $completed = $this->makeCareCoordination(['completed_at' => now()->subDay()]);
        $notCompleted = $this->makeCareCoordination(['completed_at' => null]);

        $ids = $this->ids($this->dto(['isCompleted' => true]));

        $this->assertContains($completed->id, $ids);
        $this->assertNotContains($notCompleted->id, $ids);
    }

    public function test_is_completed_filter_returns_only_not_completed_when_false(): void
    {
        $completed = $this->makeCareCoordination(['completed_at' => now()->subDay()]);
        $notCompleted = $this->makeCareCoordination(['completed_at' => null]);

        $ids = $this->ids($this->dto(['isCompleted' => false]));

        $this->assertContains($notCompleted->id, $ids);
        $this->assertNotContains($completed->id, $ids);
    }

    public function test_state_filter_includes_patients_in_that_state_and_excludes_other_states(): void
    {
        $tx = $this->makeCareCoordination([], ['patient_id' => $this->patientInState('TX')->id]);
        $co = $this->makeCareCoordination([], ['patient_id' => $this->patientInState('CO')->id]);

        $ids = $this->ids($this->dto(['state' => ['TX']]));

        $this->assertContains($tx->id, $ids);
        $this->assertNotContains($co->id, $ids);
    }

    public function test_branch_filter_includes_patients_in_that_facility_and_excludes_other_facilities(): void
    {
        [$p1, $p2] = $this->twoPatientsInDistinctFacilities();
        $inBranch = $this->makeCareCoordination([], ['patient_id' => $p1->id]);
        $otherBranch = $this->makeCareCoordination([], ['patient_id' => $p2->id]);

        $ids = $this->ids($this->dto(['branchIds' => [$p1->aoc_facility_id]]));

        $this->assertContains($inBranch->id, $ids);
        $this->assertNotContains($otherBranch->id, $ids);
    }

    public function test_region_filter_includes_patients_in_that_region_and_excludes_other_regions(): void
    {
        [$p1, $p2] = $this->twoPatientsInDistinctRegions();
        $inRegion = $this->makeCareCoordination([], ['patient_id' => $p1->id]);
        $otherRegion = $this->makeCareCoordination([], ['patient_id' => $p2->id]);

        $ids = $this->ids($this->dto(['regionIds' => [(int) $p1->facility->region]]));

        $this->assertContains($inRegion->id, $ids);
        $this->assertNotContains($otherRegion->id, $ids);
    }

    public function test_me_mode_includes_only_the_authenticated_users_care_coordinations(): void
    {
        $this->actingAs($this->assignee);

        $mine = $this->makeCareCoordination();
        $someoneElses = $this->makeCareCoordination(['assigned_to_id' => $this->otherUser->id]);

        $ids = $this->ids(new CareCoordinationFilterDTO(meMode: true, perPage: 50));

        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($someoneElses->id, $ids);
    }

    // --- search -------------------------------------------------------------

    public function test_search_matches_by_patient_account_and_excludes_non_matching(): void
    {
        [$p1, $p2] = $this->twoDistinctPatients();
        $matching = $this->makeCareCoordination([], ['patient_id' => $p1->id]);
        $nonMatching = $this->makeCareCoordination([], ['patient_id' => $p2->id]);

        $ids = $this->ids($this->dto(['search' => $p1->PatientAccount]));

        $this->assertContains($matching->id, $ids);
        $this->assertNotContains($nonMatching->id, $ids);
    }

    // --- sorting ------------------------------------------------------------

    public function test_sort_by_due_date_orders_from_oldest_to_newest_when_ascending(): void
    {
        [$oldest, $mid, $newest] = $this->threeWithSpacedDueDates();

        $ids = $this->ids($this->dto(['sortBy' => 'due_date', 'sortDir' => 'asc']));

        $this->assertSame([$oldest->id, $mid->id, $newest->id], $ids);
    }

    public function test_sort_by_due_date_orders_from_newest_to_oldest_when_descending(): void
    {
        [$oldest, $mid, $newest] = $this->threeWithSpacedDueDates();

        $ids = $this->ids($this->dto(['sortBy' => 'due_date', 'sortDir' => 'desc']));

        $this->assertSame([$newest->id, $mid->id, $oldest->id], $ids);
    }

    // --- regression ---------------------------------------------------------

    /** Sorting by patient_name joins encounters + patients; SELECT * used to collide on id/created_at. */
    public function test_sort_by_patient_name_does_not_collide_with_joined_tables(): void
    {
        $cc = $this->makeCareCoordination();

        $ids = $this->ids($this->dto(['sortBy' => 'patient_name', 'sortDir' => 'asc']));

        $this->assertContains($cc->id, $ids);
    }

    // --- response shape -----------------------------------------------------

    public function test_response_exposes_expected_fields_with_formatted_dates(): void
    {
        $cc = $this->makeCareCoordination(['due_at' => now()->addDay()]);

        $row = $this->serializeRow($cc->id);

        $this->assertSame([
            'id', 'created_at', 'patient_id', 'patient_name', 'patient_account', 'hospital_name',
            'coordination_type', 'provider', 'assignee_name', 'due_date', 'is_overdue', 'is_completed',
        ], array_keys($row));
        $this->assertMatchesRegularExpression('#^\d{2}/\d{2}/\d{4} \d{2}:\d{2} (AM|PM)$#', $row['created_at']);
        $this->assertMatchesRegularExpression('#^\d{2}/\d{2}/\d{4} \d{2}:\d{2} (AM|PM)$#', $row['due_date']);
        $this->assertNull($row['provider']);
    }

    public function test_response_maps_patient_fields_from_patient_and_encounter(): void
    {
        $patient = $this->defaultPatient();
        $cc = $this->makeCareCoordination([], ['patient_id' => $patient->id, 'facility_name' => 'Unit Test Hospital']);

        $row = $this->serializeRow($cc->id);

        $this->assertSame($patient->PatientName, $row['patient_name']);
        $this->assertSame($patient->PatientAccount, $row['patient_account']);
        $this->assertSame('Unit Test Hospital', $row['hospital_name']);   // hospital_name maps to encounter.facility_name
    }

    // --- fixtures -----------------------------------------------------------

    private function makeCareCoordination(array $careCoordination = [], array $encounter = []): CareCoordinationModel
    {
        $enc = EncounterModel::create(array_merge([
            'patient_id' => $this->defaultPatient()->id,
            'type' => EncounterTypeEnum::IP,
        ], $encounter));

        return CareCoordinationModel::create(array_merge([
            'encounter_id' => $enc->id,
            'care_coordination_type_id' => $this->typeId(),
            'assigned_to_id' => $this->assignee->id,
            'due_at' => now()->addDay(),
        ], $careCoordination));
    }

    /** @return array{0: CareCoordinationModel, 1: CareCoordinationModel, 2: CareCoordinationModel} */
    private function threeWithSpacedDueDates(): array
    {
        $mid = $this->makeCareCoordination(['due_at' => now()]);
        $oldest = $this->makeCareCoordination(['due_at' => now()->subDays(3)]);
        $newest = $this->makeCareCoordination(['due_at' => now()->addDays(3)]);

        return [$oldest, $mid, $newest];
    }

    private function dto(array $args = []): CareCoordinationFilterDTO
    {
        return new CareCoordinationFilterDTO(...array_merge([
            'assigneeIds' => [$this->assignee->id],
            'perPage' => 50,
        ], $args));
    }

    private function ids(CareCoordinationFilterDTO $dto): array
    {
        return $this->repository->getPaginated($dto)->pluck('id')->all();
    }

    private function serializeRow(int $careCoordinationId): array
    {
        $paginated = $this->repository->getPaginated($this->dto());
        $data = json_decode((new CareCoordinationCollection($paginated))->toResponse(request())->getContent(), true)['data'];
        $row = collect($data)->firstWhere('id', $careCoordinationId);

        $this->assertNotNull($row, "Care coordination {$careCoordinationId} not found in serialized response");

        return $row;
    }

    private function typeId(): int
    {
        return $this->typeId ??= (new CareCoordinationTypeFixture())->create()->id;
    }

    private function createUser(): User
    {
        return User::query()->findOrFail((new UserFixture())->create()->id);
    }

    private function defaultPatient(): MssqlPatients
    {
        return $this->defaultPatient ??= $this->patientInState('TX');
    }

    private function patientInState(string $state): MssqlPatients
    {
        return (new VbcPatientScenario())->createInState($state);
    }

    /** @return array{0: MssqlPatients, 1: MssqlPatients} */
    private function twoDistinctPatients(): array
    {
        return (new VbcPatientScenario())->createPair();
    }

    /** @return array{0: MssqlPatients, 1: MssqlPatients} */
    private function twoPatientsInDistinctFacilities(): array
    {
        return (new VbcPatientScenario())->createPair();
    }

    /** @return array{0: MssqlPatients, 1: MssqlPatients} */
    private function twoPatientsInDistinctRegions(): array
    {
        return (new VbcPatientScenario())->createPair();
    }
}
