<?php

namespace Tests\Feature\VBC;

use App\MssqlPatients;
use App\User;
use App\VBC\DTO\Hospitalization\HospitalizationFilterDTO;
use App\VBC\Enum\Encounter\EncounterAdmissionTypeEnum;
use App\VBC\Enum\Encounter\EncounterStatusEnum;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFollowupStatusEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerIcmLevelEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Http\Resources\Hospitalization\AllHospitalizationCollection;
use App\VBC\Http\Resources\Hospitalization\EdVisitCollection;
use App\VBC\Http\Resources\Hospitalization\HospitalizationCollection;
use App\VBC\Models\Encounter\EncounterModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerFollowupModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use App\VBC\Models\PatientCustomProperty\PatientCustomPropertyDefinitionModel;
use App\VBC\Models\PatientCustomProperty\PatientCustomPropertyModel;
use App\VBC\Repositories\Hospitalization\HospitalizationReadRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\Fixtures\UserFixture;
use Tests\Support\Scenarios\VbcPatientScenario;
use Tests\TestCase;

/**
 * Behavioral tests for the Hospitalization bucket read repository.
 *
 * A "hospitalization" row is a HospitalizationTracker tied to an Encounter (which holds
 * the patient + admission dates). Runs against the existing dev DB; DatabaseTransactions
 * rolls everything back per test. Fixtures are isolated by a non-seed assignee (ids 1/11)
 * and queries are scoped by that assignee, so assertions never depend on pre-existing rows.
 *
 * Grouping: scoping baseline -> filters -> search -> sorting -> regression -> response shape.
 */
class HospitalizationReadRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private HospitalizationReadRepository $repository;
    private User $assignee;
    private User $otherUser;
    private ?MssqlPatients $defaultPatient = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(HospitalizationReadRepository::class);
        $this->assignee = $this->createUser();
        $this->otherUser = $this->createUser();
    }

    // --- scoping baseline ---------------------------------------------------

    public function test_query_returns_only_trackers_assigned_to_the_given_assignee(): void
    {
        $a = $this->makeTracker();
        $b = $this->makeTracker();

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $this->ids($this->dto()));
    }

    // --- filters ------------------------------------------------------------

    public function test_status_filter_includes_matching_status_and_excludes_others(): void
    {
        $open = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::OPEN]);
        $discharged = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::DISCHARGED]);

        $ids = $this->ids($this->dto(['statuses' => ['OPEN']]));

        $this->assertContains($open->id, $ids);
        $this->assertNotContains($discharged->id, $ids);
    }

    public function test_level_filter_includes_matching_level_and_excludes_others(): void
    {
        $levelOne = $this->makeTracker(['icm_level' => HospitalizationTrackerIcmLevelEnum::LEVEL_I]);
        $levelTwo = $this->makeTracker(['icm_level' => HospitalizationTrackerIcmLevelEnum::LEVEL_II]);

        $ids = $this->ids($this->dto(['levels' => ['LEVEL_I']]));

        $this->assertContains($levelOne->id, $ids);
        $this->assertNotContains($levelTwo->id, $ids);
    }

    public function test_facility_name_filter_includes_matching_facility_and_excludes_others(): void
    {
        $seedHospital = $this->makeTracker([], ['facility_name' => 'General Hospital']);
        $otherHospital = $this->makeTracker([], ['facility_name' => 'Other Hospital']);

        $ids = $this->ids($this->dto(['facilityNames' => ['General Hospital']]));

        $this->assertContains($seedHospital->id, $ids);
        $this->assertNotContains($otherHospital->id, $ids);
    }

    public function test_export_type_high_utilizers_includes_only_flagged_patients_trackers(): void
    {
        [$flaggedPatient, $otherPatient] = $this->twoPatientsInDistinctFacilities();
        $this->markPatientAsHighUtilizer($flaggedPatient);

        $flagged = $this->makeTracker([], ['patient_id' => $flaggedPatient->id]);
        $other = $this->makeTracker([], ['patient_id' => $otherPatient->id]);

        $ids = $this->ids($this->dto(['exportType' => 'HIGH_UTILIZERS']));

        $this->assertContains($flagged->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_export_type_hospitalizations_does_not_restrict_by_high_utilizer_flag(): void
    {
        [$flaggedPatient, $otherPatient] = $this->twoPatientsInDistinctFacilities();
        $this->markPatientAsHighUtilizer($flaggedPatient);

        $flagged = $this->makeTracker([], ['patient_id' => $flaggedPatient->id]);
        $other = $this->makeTracker([], ['patient_id' => $otherPatient->id]);

        $ids = $this->ids($this->dto(['exportType' => 'HOSPITALIZATIONS']));

        $this->assertContains($flagged->id, $ids);
        $this->assertContains($other->id, $ids);
    }

    public function test_state_filter_includes_patients_in_that_state_and_excludes_other_states(): void
    {
        $tx = $this->makeTracker([], ['patient_id' => $this->patientInState('TX')->id]);
        $co = $this->makeTracker([], ['patient_id' => $this->patientInState('CO')->id]);

        $ids = $this->ids($this->dto(['state' => ['TX']]));

        $this->assertContains($tx->id, $ids);
        $this->assertNotContains($co->id, $ids);
    }

    public function test_branch_filter_includes_patients_in_that_facility_and_excludes_other_facilities(): void
    {
        [$p1, $p2] = $this->twoPatientsInDistinctFacilities();
        $inBranch = $this->makeTracker([], ['patient_id' => $p1->id]);
        $otherBranch = $this->makeTracker([], ['patient_id' => $p2->id]);

        $ids = $this->ids($this->dto(['branchIds' => [$p1->aoc_facility_id]]));

        $this->assertContains($inBranch->id, $ids);
        $this->assertNotContains($otherBranch->id, $ids);
    }

    public function test_region_filter_includes_patients_in_that_region_and_excludes_other_regions(): void
    {
        [$p1, $p2] = $this->twoPatientsInDistinctRegions();
        $inRegion = $this->makeTracker([], ['patient_id' => $p1->id]);
        $otherRegion = $this->makeTracker([], ['patient_id' => $p2->id]);

        $ids = $this->ids($this->dto(['regionIds' => [(int) $p1->facility->region]]));

        $this->assertContains($inRegion->id, $ids);
        $this->assertNotContains($otherRegion->id, $ids);
    }

    public function test_me_mode_includes_only_the_authenticated_users_trackers(): void
    {
        $this->actingAs($this->assignee);

        $mine = $this->makeTracker();
        $someoneElses = $this->makeTracker(['assigned_to_id' => $this->otherUser->id]);

        $ids = $this->ids(new HospitalizationFilterDTO(meMode: true, perPage: 50));

        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($someoneElses->id, $ids);
    }

    public function test_admission_date_range_includes_trackers_within_range_and_excludes_outside(): void
    {
        $inside = $this->makeTracker([], ['admission_at' => '2024-06-15 10:00:00']);
        $outside = $this->makeTracker([], ['admission_at' => '2020-01-01 10:00:00']);

        $ids = $this->ids($this->dto([
            'admissionDateFrom' => '2024-06-01',
            'admissionDateTo' => '2024-06-30',
        ]));

        $this->assertContains($inside->id, $ids);
        $this->assertNotContains($outside->id, $ids);
    }

    public function test_encounter_type_filter_includes_matching_type_and_excludes_others(): void
    {
        $ed = $this->makeTracker([], ['type' => EncounterTypeEnum::ED]);
        $ip = $this->makeTracker([], ['type' => EncounterTypeEnum::IP]);

        $ids = $this->ids($this->dto(['encounterTypes' => ['ED']]));

        $this->assertContains($ed->id, $ids);
        $this->assertNotContains($ip->id, $ids);
    }

    public function test_no_encounter_type_filter_returns_every_type(): void
    {
        $ed = $this->makeTracker([], ['type' => EncounterTypeEnum::ED]);
        $ip = $this->makeTracker([], ['type' => EncounterTypeEnum::IP]);

        $this->assertEqualsCanonicalizing([$ed->id, $ip->id], $this->ids($this->dto()));
    }

    // --- search -------------------------------------------------------------

    public function test_search_matches_by_tracker_id_and_excludes_non_matching(): void
    {
        $target = $this->makeTracker();
        $other = $this->makeTracker();

        $ids = $this->ids($this->dto(['search' => (string) $target->id]));

        $this->assertContains($target->id, $ids);

        // $other only matches if its id string contains the target id string; guard against that.
        if (!str_contains((string) $other->id, (string) $target->id)) {
            $this->assertNotContains($other->id, $ids);
        }
    }

    // --- bucket scope ---------------------------------------------------------

    public function test_bucket_paginated_returns_the_icm_bucket_statuses_and_excludes_completed(): void
    {
        $open = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::OPEN]);
        $inProgress = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS]);
        $discharged = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::DISCHARGED]);
        $readyForReview = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::READY_FOR_REVIEW]);
        $readyForNextSteps = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::READY_FOR_NEXT_STEPS]);
        $completed = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::COMPLETED]);

        $ids = $this->repository->getBucketPaginated($this->dto())->pluck('id')->all();

        $this->assertEqualsCanonicalizing(
            [$open->id, $inProgress->id, $discharged->id, $readyForReview->id, $readyForNextSteps->id],
            $ids
        );
        $this->assertNotContains($completed->id, $ids);
    }

    public function test_ed_visits_bucket_returns_in_progress_ready_for_review_and_ready_for_next_steps_trackers(): void
    {
        $ed = ['type' => EncounterTypeEnum::ED];
        $inProgress = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS], $ed);
        $readyForReview = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::READY_FOR_REVIEW], $ed);
        $readyForNextSteps = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::READY_FOR_NEXT_STEPS], $ed);
        $open = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::OPEN], $ed);
        $discharged = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::DISCHARGED], $ed);
        $completed = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::COMPLETED], $ed);

        $ids = $this->edVisitIds($this->dto(['encounterTypes' => ['ED']]));

        $this->assertEqualsCanonicalizing([$inProgress->id, $readyForReview->id, $readyForNextSteps->id], $ids);
        $this->assertNotContains($open->id, $ids);
        $this->assertNotContains($discharged->id, $ids);
        $this->assertNotContains($completed->id, $ids);
    }

    public function test_ed_visits_bucket_status_filter_narrows_within_scope_and_cannot_widen_it(): void
    {
        $ed = ['type' => EncounterTypeEnum::ED];
        $inProgress = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS], $ed);
        $readyForReview = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::READY_FOR_REVIEW], $ed);
        $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::OPEN], $ed);

        $narrowed = $this->edVisitIds($this->dto(['encounterTypes' => ['ED'], 'statuses' => ['READY_FOR_REVIEW']]));
        $outside = $this->edVisitIds($this->dto(['encounterTypes' => ['ED'], 'statuses' => ['OPEN']]));

        $this->assertSame([$readyForReview->id], $narrowed);
        $this->assertNotContains($inProgress->id, $narrowed);
        $this->assertSame([], $outside);
    }

    public function test_ed_visits_bucket_with_ed_encounter_type_excludes_ip_trackers(): void
    {
        $ed = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS], ['type' => EncounterTypeEnum::ED]);
        $ip = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS], ['type' => EncounterTypeEnum::IP]);

        $ids = $this->edVisitIds($this->dto(['encounterTypes' => ['ED']]));

        $this->assertContains($ed->id, $ids);
        $this->assertNotContains($ip->id, $ids);
    }

    /** The ICM Hospitalization bucket keeps its own Open + In Progress scope and every encounter type. */
    public function test_icm_bucket_scope_is_unchanged_by_the_ed_visits_bucket(): void
    {
        $open = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::OPEN], ['type' => EncounterTypeEnum::IP]);
        $inProgressEd = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS], ['type' => EncounterTypeEnum::ED]);

        $ids = $this->repository->getBucketPaginated($this->dto())->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$open->id, $inProgressEd->id], $ids);
    }

    // --- sorting ------------------------------------------------------------

    public function test_sort_by_admission_date_orders_from_oldest_to_newest_when_ascending(): void
    {
        [$oldest, $mid, $newest] = $this->threeTrackersWithSpacedAdmissions();

        $ids = $this->ids($this->dto(['sortBy' => 'admission_date', 'sortDir' => 'asc']));

        $this->assertSame([$oldest->id, $mid->id, $newest->id], $ids);
    }

    public function test_sort_by_admission_date_orders_from_newest_to_oldest_when_descending(): void
    {
        [$oldest, $mid, $newest] = $this->threeTrackersWithSpacedAdmissions();

        $ids = $this->ids($this->dto(['sortBy' => 'admission_date', 'sortDir' => 'desc']));

        $this->assertSame([$newest->id, $mid->id, $oldest->id], $ids);
    }

    public function test_sort_by_last_follow_up_orders_trackers_by_most_recent_completed_followup(): void
    {
        $withoutFollowup = $this->makeTracker();
        $olderFollowup = $this->makeTracker();
        $newerFollowup = $this->makeTracker();

        $this->makeCompletedFollowup($olderFollowup, now()->subDays(3));
        $this->makeCompletedFollowup($newerFollowup, now());

        $ids = $this->ids($this->dto(['sortBy' => 'last_follow_up', 'sortDir' => 'asc']));

        $this->assertSame([$withoutFollowup->id, $olderFollowup->id, $newerFollowup->id], $ids);
    }

    // --- regression ---------------------------------------------------------

    /** Default sort joins encounters; unqualified status filter threw "status is ambiguous". */
    public function test_status_filter_on_default_sort_does_not_throw_ambiguous_status_column(): void
    {
        $open = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::OPEN]);

        $ids = $this->ids($this->dto(['statuses' => ['OPEN']]));

        $this->assertContains($open->id, $ids);
    }

    /** SELECT * with the encounters join used to overwrite the tracker's status with the encounter's. */
    public function test_response_returns_tracker_status_not_encounter_status(): void
    {
        $tracker = $this->makeTracker(
            ['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS],
            ['status' => EncounterStatusEnum::ADMITTED]
        );

        $row = $this->serializeRow($tracker->id);

        $this->assertSame('In Progress', $row['status']);
    }

    // --- response shape -----------------------------------------------------

    public function test_response_exposes_expected_fields_with_formatted_dates_and_level_label(): void
    {
        $tracker = $this->makeTracker(
            ['icm_level' => HospitalizationTrackerIcmLevelEnum::LEVEL_II],
            ['admission_at' => now(), 'discharged_at' => now()->addDays(5)]
        );

        $row = $this->serializeRow($tracker->id);

        $this->assertSame([
            'id', 'patient_id', 'patient_name', 'patient_account', 'type', 'level', 'tracker_id',
            'admission_date', 'facility_name', 'discharge_date', 'status', 'assignee_name',
            'terminal_payer', 'branch', 'last_follow_up', 'is_overdue',
        ], array_keys($row));
        $this->assertSame('Level II', $row['level']);                                       // icm_level enum -> label
        $this->assertMatchesRegularExpression('#^\d{2}/\d{2}/\d{4} \d{2}:\d{2} (AM|PM)$#', $row['admission_date']);
        $this->assertMatchesRegularExpression('#^\d{2}/\d{2}/\d{4} \d{2}:\d{2} (AM|PM)$#', $row['discharge_date']);
    }

    public function test_bucket_response_exposes_only_the_bucket_fields(): void
    {
        $tracker = $this->makeTracker(['icm_level' => HospitalizationTrackerIcmLevelEnum::LEVEL_II]);

        $paginated = $this->repository->getBucketPaginated($this->dto());
        $data = json_decode((new HospitalizationCollection($paginated))->toResponse(request())->getContent(), true)['data'];
        $row = collect($data)->firstWhere('id', $tracker->id);

        $this->assertSame([
            'id', 'patient_id', 'patient_name', 'patient_account', 'type', 'level', 'tracker_id',
            'admission_date', 'expected_discharge_date', 'status', 'assignee_name', 'terminal_payer', 'branch', 'is_overdue',
        ], array_keys($row));
    }

    public function test_response_exposes_the_most_recent_completed_followup_date(): void
    {
        $tracker = $this->makeTracker();
        $latest = now();
        $this->makeCompletedFollowup($tracker, now()->subDays(5));
        $this->makeCompletedFollowup($tracker, $latest);

        $row = $this->serializeRow($tracker->id);

        $this->assertSame($latest->format('m/d/Y h:i A'), $row['last_follow_up']);
    }

    /** "type" reflects the encounter's admission classification (Avoidable/Unavoidable/Planned), not its IP/ED venue type. */
    public function test_response_type_field_reflects_admission_type_not_encounter_type(): void
    {
        $tracker = $this->makeTracker([], [
            'type' => EncounterTypeEnum::ED,
            'admission_type' => EncounterAdmissionTypeEnum::UNAVOIDABLE,
        ]);

        $row = $this->serializeRow($tracker->id);

        $this->assertSame('Unavoidable', $row['type']);
    }

    public function test_response_maps_patient_fields_from_the_related_patient(): void
    {
        $patient = $this->defaultPatient();
        $tracker = $this->makeTracker([], ['patient_id' => $patient->id]);

        $row = $this->serializeRow($tracker->id);

        $this->assertSame($patient->PatientName, $row['patient_name']);
        $this->assertSame($patient->PatientAccount, $row['patient_account']);
        $this->assertSame($patient->facility?->FacilityName, $row['branch']);   // branch maps to FacilityName, not BranchName
    }

    /** "facility_name" is the hospital stored on the encounter, distinct from "branch" (the AOC servicing facility). */
    public function test_response_exposes_the_encounters_facility_name_distinct_from_branch(): void
    {
        $tracker = $this->makeTracker([], ['facility_name' => 'General Hospital']);

        $row = $this->serializeRow($tracker->id);

        $this->assertSame('General Hospital', $row['facility_name']);
        $this->assertNotSame($row['facility_name'], $row['branch']);
    }

    public function test_ed_visits_response_exposes_only_the_ed_visits_bucket_fields(): void
    {
        $tracker = $this->makeTracker(
            ['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS, 'icm_level' => HospitalizationTrackerIcmLevelEnum::LEVEL_II],
            ['type' => EncounterTypeEnum::ED]
        );

        $row = $this->serializeEdVisitRow($tracker->id);

        $this->assertSame([
            'id', 'patient_id', 'patient_name', 'patient_account', 'level', 'tracker_id', 'admission_date',
            'follow_up_status', 'status', 'assignee_name', 'terminal_payer', 'branch', 'is_overdue',
        ], array_keys($row));
        $this->assertSame('Level II', $row['level']);
        $this->assertSame('In Progress', $row['status']);
        $this->assertMatchesRegularExpression('#^\d{2}/\d{2}/\d{4} \d{2}:\d{2} (AM|PM)$#', $row['admission_date']);
    }

    /** "Follow-up Status" is the most recently created follow-up's status, completed or not. */
    public function test_ed_visits_follow_up_status_is_the_most_recent_followups_status(): void
    {
        $tracker = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS], ['type' => EncounterTypeEnum::ED]);
        $this->makeCompletedFollowup($tracker, now()->subDay());
        HospitalizationTrackerFollowupModel::create([
            'hospitalization_tracker_id' => $tracker->id,
            'status' => HospitalizationTrackerFollowupStatusEnum::SCHEDULED,
        ]);

        $row = $this->serializeEdVisitRow($tracker->id);

        $this->assertSame('Scheduled', $row['follow_up_status']);
    }

    public function test_ed_visits_follow_up_status_is_null_without_followups(): void
    {
        $tracker = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS], ['type' => EncounterTypeEnum::ED]);

        $this->assertNull($this->serializeEdVisitRow($tracker->id)['follow_up_status']);
    }

    /** AC18: overdue once admitted more than 24 hours ago. */
    public function test_ed_visit_admitted_more_than_24_hours_ago_is_overdue(): void
    {
        $tracker = $this->makeTracker(
            ['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS],
            ['type' => EncounterTypeEnum::ED, 'admission_at' => now()->subHours(25)]
        );

        $this->assertTrue($this->serializeEdVisitRow($tracker->id)['is_overdue']);
    }

    /** AC18 only — unlike the ICM bucket, a past expected discharge date does not make it overdue. */
    public function test_ed_visit_admitted_less_than_24_hours_ago_is_not_overdue_even_past_expected_discharge(): void
    {
        $tracker = $this->makeTracker(
            ['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS],
            ['type' => EncounterTypeEnum::ED, 'admission_at' => now()->subHours(23), 'expected_discharge_at' => now()->subDays(2)]
        );

        $this->assertFalse($this->serializeEdVisitRow($tracker->id)['is_overdue']);
    }

    // --- fixtures -----------------------------------------------------------

    private function makeTracker(array $tracker = [], array $encounter = []): HospitalizationTrackerModel
    {
        $enc = EncounterModel::create(array_merge([
            'patient_id' => $this->defaultPatient()->id,
            'type' => EncounterTypeEnum::IP,
            'status' => EncounterStatusEnum::ADMITTED,
            'admission_at' => now(),
        ], $encounter));

        return HospitalizationTrackerModel::create(array_merge([
            'encounter_id' => $enc->id,
            'status' => HospitalizationTrackerStatusEnum::OPEN,
            'icm_level' => HospitalizationTrackerIcmLevelEnum::LEVEL_I,
            'assigned_to_id' => $this->assignee->id,
        ], $tracker));
    }

    /** @return array{0: HospitalizationTrackerModel, 1: HospitalizationTrackerModel, 2: HospitalizationTrackerModel} */
    private function threeTrackersWithSpacedAdmissions(): array
    {
        $mid = $this->makeTracker([], ['admission_at' => now()]);
        $oldest = $this->makeTracker([], ['admission_at' => now()->subDays(3)]);
        $newest = $this->makeTracker([], ['admission_at' => now()->addDays(3)]);

        return [$oldest, $mid, $newest];
    }

    private function markPatientAsHighUtilizer(MssqlPatients $patient): PatientCustomPropertyModel
    {
        $definition = PatientCustomPropertyDefinitionModel::firstOrCreate(
            ['key' => 'high_utilizer'],
            ['name' => 'High Utilizer?', 'default_value' => false]
        );

        return PatientCustomPropertyModel::create([
            'patient_id' => $patient->id,
            'definition_id' => $definition->id,
            'value' => true,
        ]);
    }

    private function makeCompletedFollowup(HospitalizationTrackerModel $tracker, $completedAt): HospitalizationTrackerFollowupModel
    {
        return HospitalizationTrackerFollowupModel::create([
            'hospitalization_tracker_id' => $tracker->id,
            'status' => HospitalizationTrackerFollowupStatusEnum::COMPLETED,
            'completed_at' => $completedAt,
        ]);
    }

    private function dto(array $args = []): HospitalizationFilterDTO
    {
        return new HospitalizationFilterDTO(...array_merge([
            'assigneeIds' => [$this->assignee->id],
            'perPage' => 50,
        ], $args));
    }

    private function ids(HospitalizationFilterDTO $dto): array
    {
        return $this->repository->getPaginated($dto)->pluck('id')->all();
    }

    private function serializeRow(int $trackerId): array
    {
        $paginated = $this->repository->getPaginated($this->dto());
        $data = json_decode((new AllHospitalizationCollection($paginated))->toResponse(request())->getContent(), true)['data'];
        $row = collect($data)->firstWhere('id', $trackerId);

        $this->assertNotNull($row, "Tracker {$trackerId} not found in serialized response");

        return $row;
    }

    private function edVisitIds(HospitalizationFilterDTO $dto): array
    {
        return $this->repository->getEdVisitsBucketPaginated($dto)->pluck('id')->all();
    }

    private function serializeEdVisitRow(int $trackerId): array
    {
        $paginated = $this->repository->getEdVisitsBucketPaginated($this->dto(['encounterTypes' => ['ED']]));
        $data = json_decode((new EdVisitCollection($paginated))->toResponse(request())->getContent(), true)['data'];
        $row = collect($data)->firstWhere('id', $trackerId);

        $this->assertNotNull($row, "Tracker {$trackerId} not found in serialized ED Visits response");

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
