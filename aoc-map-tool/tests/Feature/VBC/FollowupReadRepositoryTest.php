<?php

namespace Tests\Feature\VBC;

use App\MssqlPatients;
use App\User;
use App\VBC\DTO\Followup\FollowupFilterDTO;
use App\VBC\Enum\Encounter\EncounterStatusEnum;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFollowupStatusEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerIcmLevelEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Http\Resources\Followup\FollowupCollection;
use App\VBC\Models\Encounter\EncounterModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerFollowupModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use App\VBC\Repositories\Followup\FollowupReadRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\Fixtures\UserFixture;
use Tests\Support\Scenarios\VbcPatientScenario;
use Tests\TestCase;

/**
 * Behavioral tests for the Follow-up bucket read repository.
 *
 * Runs against the existing dev DB (mssql_patients / mssql_facilities mirrors are
 * already there). DatabaseTransactions rolls everything back after each test, so no
 * data is wiped or left behind. Tests isolate their fixtures by assigning them to a
 * user the seeder never uses (assignee ids 1 / 11), then scoping queries by that
 * assignee — so assertions never depend on pre-existing rows.
 *
 * Grouping: scoping baseline -> filters -> search -> sorting -> regression -> response shape.
 */
class FollowupReadRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private FollowupReadRepository $repository;
    private User $assignee;
    private User $otherUser;
    private ?MssqlPatients $defaultPatient = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(FollowupReadRepository::class);
        $this->assignee = $this->createUser();
        $this->otherUser = $this->createUser();
    }

    // --- scoping baseline ---------------------------------------------------

    public function test_query_returns_only_followups_assigned_to_the_given_assignee(): void
    {
        $a = $this->makeFollowup();
        $b = $this->makeFollowup();

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $this->ids($this->dto()));
    }

    // --- filters ------------------------------------------------------------

    public function test_status_filter_includes_matching_status_and_excludes_others(): void
    {
        $canceled = $this->makeFollowup(['status' => HospitalizationTrackerFollowupStatusEnum::CANCELED]);
        $open = $this->makeFollowup(['status' => HospitalizationTrackerFollowupStatusEnum::OPEN]);

        $ids = $this->ids($this->dto(['statuses' => ['CANCELED']]));

        $this->assertContains($canceled->id, $ids);
        $this->assertNotContains($open->id, $ids);
    }

    public function test_state_filter_includes_patients_in_that_state_and_excludes_other_states(): void
    {
        $tx = $this->makeFollowup(['patient_id' => $this->patientInState('TX')->id]);
        $co = $this->makeFollowup(['patient_id' => $this->patientInState('CO')->id]);

        $ids = $this->ids($this->dto(['state' => ['TX']]));

        $this->assertContains($tx->id, $ids);
        $this->assertNotContains($co->id, $ids);
    }

    public function test_branch_filter_includes_patients_in_that_facility_and_excludes_other_facilities(): void
    {
        [$p1, $p2] = $this->twoPatientsInDistinctFacilities();
        $inBranch = $this->makeFollowup(['patient_id' => $p1->id]);
        $otherBranch = $this->makeFollowup(['patient_id' => $p2->id]);

        $ids = $this->ids($this->dto(['branchIds' => [$p1->aoc_facility_id]]));

        $this->assertContains($inBranch->id, $ids);
        $this->assertNotContains($otherBranch->id, $ids);
    }

    public function test_region_filter_includes_patients_in_that_region_and_excludes_other_regions(): void
    {
        [$p1, $p2] = $this->twoPatientsInDistinctRegions();
        $inRegion = $this->makeFollowup(['patient_id' => $p1->id]);
        $otherRegion = $this->makeFollowup(['patient_id' => $p2->id]);

        $ids = $this->ids($this->dto(['regionIds' => [(int) $p1->facility->region]]));

        $this->assertContains($inRegion->id, $ids);
        $this->assertNotContains($otherRegion->id, $ids);
    }

    public function test_me_mode_includes_only_the_authenticated_users_followups(): void
    {
        $this->actingAs($this->assignee);

        $mine = $this->makeFollowup();
        $someoneElses = $this->makeFollowup(['assigned_to_id' => $this->otherUser->id]);

        $ids = $this->ids(new FollowupFilterDTO(meMode: true, perPage: 50));

        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($someoneElses->id, $ids);
    }

    // --- search -------------------------------------------------------------

    public function test_search_matches_by_patient_account_and_excludes_non_matching(): void
    {
        [$p1, $p2] = $this->twoDistinctPatients();
        $matching = $this->makeFollowup(['patient_id' => $p1->id]);
        $nonMatching = $this->makeFollowup(['patient_id' => $p2->id]);

        $ids = $this->ids($this->dto(['search' => $p1->PatientAccount]));

        $this->assertContains($matching->id, $ids);
        $this->assertNotContains($nonMatching->id, $ids);
    }

    // --- sorting ------------------------------------------------------------

    public function test_sort_by_followup_date_orders_from_oldest_to_newest_when_ascending(): void
    {
        [$oldest, $mid, $newest] = $this->threeFollowupsWithSpacedDates();

        $ids = $this->ids($this->dto(['sortBy' => 'followup_date', 'sortDir' => 'asc']));

        $this->assertSame([$oldest->id, $mid->id, $newest->id], $ids);
    }

    public function test_sort_by_followup_date_orders_from_newest_to_oldest_when_descending(): void
    {
        [$oldest, $mid, $newest] = $this->threeFollowupsWithSpacedDates();

        $ids = $this->ids($this->dto(['sortBy' => 'followup_date', 'sortDir' => 'desc']));

        $this->assertSame([$newest->id, $mid->id, $oldest->id], $ids);
    }

    // --- regression ---------------------------------------------------------

    /** Status filter + assignee sort used to throw "status is ambiguous" (users.status vs followups.status). */
    public function test_status_filter_combined_with_assignee_sort_does_not_throw_ambiguous_status_column(): void
    {
        $canceled = $this->makeFollowup(['status' => HospitalizationTrackerFollowupStatusEnum::CANCELED]);

        $ids = $this->ids($this->dto(['statuses' => ['CANCELED'], 'sortBy' => 'assignee_name']));

        $this->assertContains($canceled->id, $ids);
    }

    // --- response shape -----------------------------------------------------

    public function test_response_exposes_expected_fields_with_us_formatted_followup_date(): void
    {
        $followup = $this->makeFollowup(['scheduled_at' => now()]);

        $row = $this->serializeRow($followup->id);

        $this->assertSame([
            'id', 'patient_id', 'patient_name', 'patient_account', 'status', 'hospitalization_level',
            'is_hospitalized', 'hospitalization_tracker_id', 'hospitalization_status',
            'facility_name', 'assignee_name', 'followup_date', 'is_overdue',
        ], array_keys($row));
        $this->assertMatchesRegularExpression('#^\d{2}/\d{2}/\d{4}$#', $row['followup_date']);
    }

    public function test_response_marks_followup_not_hospitalized_when_it_has_no_active_tracker(): void
    {
        $followup = $this->makeFollowup();

        $row = $this->serializeRow($followup->id);

        $this->assertFalse($row['is_hospitalized']);
        $this->assertNull($row['hospitalization_level']);
        $this->assertNull($row['hospitalization_status']);
        $this->assertNull($row['hospitalization_tracker_id']);
    }

    public function test_response_marks_followup_hospitalized_and_exposes_tracker_level_and_status_when_tracker_is_active(): void
    {
        $patient = $this->defaultPatient();
        $tracker = $this->makeActiveTracker($patient, HospitalizationTrackerStatusEnum::OPEN, HospitalizationTrackerIcmLevelEnum::LEVEL_II);
        $followup = $this->makeFollowup([
            'patient_id' => $patient->id,
            'hospitalization_tracker_id' => $tracker->id,
        ]);

        $row = $this->serializeRow($followup->id);

        $this->assertTrue($row['is_hospitalized']);
        $this->assertSame($tracker->id, $row['hospitalization_tracker_id']);
        $this->assertSame('Level II', $row['hospitalization_level']);   // icm_level enum -> label
        $this->assertSame('Open', $row['hospitalization_status']);      // tracker status enum -> label
    }

    public function test_response_maps_patient_fields_from_the_related_patient(): void
    {
        $patient = $this->defaultPatient();
        $followup = $this->makeFollowup(['patient_id' => $patient->id]);

        $row = $this->serializeRow($followup->id);

        $this->assertSame($patient->PatientName, $row['patient_name']);
        $this->assertSame($patient->PatientAccount, $row['patient_account']);
        $this->assertSame($patient->facility?->FacilityName, $row['facility_name']);
        $this->assertSame($this->assignee->name, $row['assignee_name']);
    }

    // --- fixtures -----------------------------------------------------------

    private function makeFollowup(array $overrides = []): HospitalizationTrackerFollowupModel
    {
        return HospitalizationTrackerFollowupModel::create(array_merge([
            'patient_id' => $this->defaultPatient()->id,
            'assigned_to_id' => $this->assignee->id,
            'requested_by_id' => $this->assignee->id,
            'status' => HospitalizationTrackerFollowupStatusEnum::SCHEDULED,
            'scheduled_at' => now(),
        ], $overrides));
    }

    private function makeActiveTracker(
        MssqlPatients $patient,
        HospitalizationTrackerStatusEnum $status,
        HospitalizationTrackerIcmLevelEnum $level
    ): HospitalizationTrackerModel {
        $encounter = EncounterModel::create([
            'patient_id' => $patient->id,
            'type' => EncounterTypeEnum::IP,
            'status' => EncounterStatusEnum::ADMITTED,
        ]);

        return HospitalizationTrackerModel::create([
            'encounter_id' => $encounter->id,
            'status' => $status,
            'icm_level' => $level,
        ]);
    }

    /** @return array{0: HospitalizationTrackerFollowupModel, 1: HospitalizationTrackerFollowupModel, 2: HospitalizationTrackerFollowupModel} */
    private function threeFollowupsWithSpacedDates(): array
    {
        $mid = $this->makeFollowup(['scheduled_at' => now()]);
        $oldest = $this->makeFollowup(['scheduled_at' => now()->subDays(3)]);
        $newest = $this->makeFollowup(['scheduled_at' => now()->addDays(3)]);

        return [$oldest, $mid, $newest];
    }

    private function dto(array $args = []): FollowupFilterDTO
    {
        return new FollowupFilterDTO(...array_merge([
            'assigneeIds' => [$this->assignee->id],
            'perPage' => 50,
        ], $args));
    }

    private function ids(FollowupFilterDTO $dto): array
    {
        return $this->repository->getPaginated($dto)->pluck('id')->all();
    }

    private function serializeRow(int $followupId): array
    {
        $paginated = $this->repository->getPaginated($this->dto());
        $data = json_decode((new FollowupCollection($paginated))->toResponse(request())->getContent(), true)['data'];
        $row = collect($data)->firstWhere('id', $followupId);

        $this->assertNotNull($row, "Follow-up {$followupId} not found in serialized response");

        return $row;
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
