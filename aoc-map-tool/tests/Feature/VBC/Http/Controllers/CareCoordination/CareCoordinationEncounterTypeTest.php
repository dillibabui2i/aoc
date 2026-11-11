<?php

namespace Tests\Feature\VBC\Http\Controllers\CareCoordination;

use App\MssqlPatients;
use App\User;
use App\VBC\Enum\Encounter\EncounterStatusEnum;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Http\Middleware\EnsurePermission;
use App\VBC\Models\CareCoordination\CareCoordinationModel;
use App\VBC\Models\Encounter\EncounterModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\Fixtures\CareCoordinationTypeFixture;
use Tests\Support\Fixtures\UserFixture;
use Tests\Support\Scenarios\VbcPatientScenario;
use Tests\TestCase;

/**
 * PD-3969: the Care Coordination API is encounter-type aware (`encounter_type` = ED | IP) — list scoping,
 * patient search eligibility, create validation and the server-side read-only guard on update.
 * Runs against the dev DB; DatabaseTransactions rolls everything back.
 */
class CareCoordinationEncounterTypeTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsurePermission::class);
        $this->user = User::query()->findOrFail((new UserFixture())->create()->id);
    }

    // --- patient search ------------------------------------------------------

    public function test_ed_search_offers_a_patient_with_an_in_progress_ed_tracker(): void
    {
        $patient = $this->uniquePatient();
        $tracker = $this->makeTracker($patient, EncounterTypeEnum::ED, HospitalizationTrackerStatusEnum::IN_PROGRESS);

        $this->assertSame([$tracker->id], $this->searchTrackerIds($patient, 'ED'));
    }

    public function test_ed_search_excludes_an_ed_tracker_that_is_not_in_progress(): void
    {
        $patient = $this->uniquePatient();
        $this->makeTracker($patient, EncounterTypeEnum::ED, HospitalizationTrackerStatusEnum::DISCHARGED);

        $this->assertSame([], $this->searchTrackerIds($patient, 'ED'));
    }

    public function test_ed_search_excludes_a_patient_who_also_has_an_active_ip_tracker(): void
    {
        $patient = $this->uniquePatient();
        $this->makeTracker($patient, EncounterTypeEnum::ED, HospitalizationTrackerStatusEnum::IN_PROGRESS);
        $this->makeTracker($patient, EncounterTypeEnum::IP, HospitalizationTrackerStatusEnum::READY_FOR_REVIEW);

        $this->assertSame([], $this->searchTrackerIds($patient, 'ED'));
    }

    public function test_ed_search_still_offers_a_patient_whose_ip_tracker_is_no_longer_active(): void
    {
        $patient = $this->uniquePatient();
        $ed = $this->makeTracker($patient, EncounterTypeEnum::ED, HospitalizationTrackerStatusEnum::IN_PROGRESS);
        $this->makeTracker($patient, EncounterTypeEnum::IP, HospitalizationTrackerStatusEnum::CLOSED);

        $this->assertSame([$ed->id], $this->searchTrackerIds($patient, 'ED'));
    }

    public function test_ip_search_offers_only_ip_trackers(): void
    {
        $patient = $this->uniquePatient();
        $this->makeTracker($patient, EncounterTypeEnum::ED, HospitalizationTrackerStatusEnum::IN_PROGRESS);
        $ip = $this->makeTracker($patient, EncounterTypeEnum::IP, HospitalizationTrackerStatusEnum::DISCHARGED);

        $this->assertSame([$ip->id], $this->searchTrackerIds($patient, 'IP'));
    }

    public function test_search_rejects_an_unknown_encounter_type(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('care-coordination.clients.index', ['search' => 'a', 'encounter_type' => 'XX']))
            ->assertStatus(422);
    }

    // --- list ----------------------------------------------------------------

    public function test_list_is_scoped_to_the_requested_encounter_type(): void
    {
        $ed = $this->makeCareCoordination($this->makeTracker($this->uniquePatient(), EncounterTypeEnum::ED, HospitalizationTrackerStatusEnum::IN_PROGRESS));
        $ip = $this->makeCareCoordination($this->makeTracker($this->uniquePatient(), EncounterTypeEnum::IP, HospitalizationTrackerStatusEnum::IN_PROGRESS));

        $edIds = $this->listIds('ED');
        $ipIds = $this->listIds('IP');

        $this->assertContains($ed->id, $edIds);
        $this->assertNotContains($ip->id, $edIds);
        $this->assertContains($ip->id, $ipIds);
        $this->assertNotContains($ed->id, $ipIds);
    }

    // --- create --------------------------------------------------------------

    public function test_create_for_an_in_progress_ed_tracker_succeeds_and_links_to_its_encounter(): void
    {
        $tracker = $this->makeTracker($this->uniquePatient(), EncounterTypeEnum::ED, HospitalizationTrackerStatusEnum::IN_PROGRESS);

        $this->actingAs($this->user)
            ->postJson(route('care-coordination.store'), $this->payload($tracker) + ['encounter_type' => 'ED'])
            ->assertCreated()
            ->assertJsonPath('data.hospitalization_tracker_id', $tracker->id)
            ->assertJsonPath('data.is_editable', true);

        $this->assertDatabaseHas('care_coordinations', ['encounter_id' => $tracker->encounter_id]);
    }

    public function test_create_rejects_an_ed_tracker_that_is_not_in_progress(): void
    {
        $tracker = $this->makeTracker($this->uniquePatient(), EncounterTypeEnum::ED, HospitalizationTrackerStatusEnum::CLOSED);

        $this->actingAs($this->user)
            ->postJson(route('care-coordination.store'), $this->payload($tracker) + ['encounter_type' => 'ED'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('hospitalization_tracker_id');
    }

    public function test_create_rejects_an_ip_tracker_when_the_ed_type_is_requested(): void
    {
        $tracker = $this->makeTracker($this->uniquePatient(), EncounterTypeEnum::IP, HospitalizationTrackerStatusEnum::IN_PROGRESS);

        $this->actingAs($this->user)
            ->postJson(route('care-coordination.store'), $this->payload($tracker) + ['encounter_type' => 'ED'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('hospitalization_tracker_id');
    }

    public function test_create_requires_the_coordination_type(): void
    {
        $tracker = $this->makeTracker($this->uniquePatient(), EncounterTypeEnum::ED, HospitalizationTrackerStatusEnum::IN_PROGRESS);

        $this->actingAs($this->user)
            ->postJson(route('care-coordination.store'), ['hospitalization_tracker_id' => $tracker->id, 'encounter_type' => 'ED'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('care_coordination_type_id');
    }

    /** BUG-1: the type is mandatory, so a request without it can't slip an IP tracker through the ED flow. */
    public function test_create_requires_the_encounter_type(): void
    {
        $tracker = $this->makeTracker($this->uniquePatient(), EncounterTypeEnum::IP, HospitalizationTrackerStatusEnum::IN_PROGRESS);

        $this->actingAs($this->user)
            ->postJson(route('care-coordination.store'), $this->payload($tracker))
            ->assertStatus(422)
            ->assertJsonValidationErrors('encounter_type');
        $this->assertDatabaseMissing('care_coordinations', ['encounter_id' => $tracker->encounter_id]);
    }

    /** BUG-2: same exclusion as the ED search — an active IP tracker blocks ED creation too. */
    public function test_create_for_an_ed_tracker_is_rejected_when_the_patient_has_an_active_ip_tracker(): void
    {
        $patient = $this->uniquePatient();
        $ed = $this->makeTracker($patient, EncounterTypeEnum::ED, HospitalizationTrackerStatusEnum::IN_PROGRESS);
        $this->makeTracker($patient, EncounterTypeEnum::IP, HospitalizationTrackerStatusEnum::READY_FOR_REVIEW);

        $this->actingAs($this->user)
            ->postJson(route('care-coordination.store'), $this->payload($ed) + ['encounter_type' => 'ED'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('hospitalization_tracker_id');
    }

    public function test_create_for_an_ed_tracker_is_accepted_when_the_patients_ip_tracker_is_closed(): void
    {
        $patient = $this->uniquePatient();
        $ed = $this->makeTracker($patient, EncounterTypeEnum::ED, HospitalizationTrackerStatusEnum::IN_PROGRESS);
        $this->makeTracker($patient, EncounterTypeEnum::IP, HospitalizationTrackerStatusEnum::CLOSED);

        $this->actingAs($this->user)
            ->postJson(route('care-coordination.store'), $this->payload($ed) + ['encounter_type' => 'ED'])
            ->assertCreated();
    }

    public function test_update_requires_the_encounter_type(): void
    {
        $activity = $this->makeCareCoordination($this->makeTracker($this->uniquePatient(), EncounterTypeEnum::IP, HospitalizationTrackerStatusEnum::IN_PROGRESS));

        $this->actingAs($this->user)
            ->patchJson(route('care-coordination.update', ['id' => $activity->id]), [
                'care_coordination_type_id' => $this->typeId(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('encounter_type');
    }

    // --- update (server-side read-only guard) ----------------------------------

    public function test_update_succeeds_while_the_ed_tracker_is_in_progress(): void
    {
        $activity = $this->makeCareCoordination($this->makeTracker($this->uniquePatient(), EncounterTypeEnum::ED, HospitalizationTrackerStatusEnum::IN_PROGRESS));

        $this->actingAs($this->user)
            ->patchJson(route('care-coordination.update', ['id' => $activity->id]), [
                'care_coordination_type_id' => $this->typeId(),
                'notes' => 'edited',
                'encounter_type' => 'ED',
            ])
            ->assertOk()
            ->assertJsonPath('data.notes', 'edited');
    }

    public function test_update_is_rejected_once_the_ed_tracker_is_no_longer_in_progress(): void
    {
        $activity = $this->makeCareCoordination($this->makeTracker($this->uniquePatient(), EncounterTypeEnum::ED, HospitalizationTrackerStatusEnum::COMPLETED));

        $this->actingAs($this->user)
            ->patchJson(route('care-coordination.update', ['id' => $activity->id]), [
                'care_coordination_type_id' => $this->typeId(),
                'notes' => 'edited',
                'encounter_type' => 'ED',
            ])
            ->assertStatus(422);

        $this->assertNotSame('edited', $activity->fresh()->notes);
    }

    public function test_update_is_rejected_when_the_encounter_type_does_not_match_the_activity(): void
    {
        $activity = $this->makeCareCoordination($this->makeTracker($this->uniquePatient(), EncounterTypeEnum::IP, HospitalizationTrackerStatusEnum::IN_PROGRESS));

        $this->actingAs($this->user)
            ->patchJson(route('care-coordination.update', ['id' => $activity->id]), [
                'care_coordination_type_id' => $this->typeId(),
                'encounter_type' => 'ED',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('encounter_type');
    }

    public function test_update_of_an_unknown_activity_is_still_a_404(): void
    {
        $this->actingAs($this->user)
            ->patchJson(route('care-coordination.update', ['id' => 999999999]), [
                'care_coordination_type_id' => $this->typeId(),
                'encounter_type' => 'ED',
            ])
            ->assertNotFound();
    }

    // --- fixtures -----------------------------------------------------------

    /** @return int[] tracker ids returned by the eligible-client search for this patient */
    private function searchTrackerIds(MssqlPatients $patient, string $type): array
    {
        return collect(
            $this->actingAs($this->user)
                ->getJson(route('care-coordination.clients.index', [
                    'search' => $patient->PatientName,
                    'encounter_type' => $type,
                ]))
                ->assertOk()
                ->json('data')
        )->where('patient_id', $patient->id)->pluck('hospitalization_tracker_id')->all();
    }

    /** @return int[] */
    private function listIds(string $type): array
    {
        return $this->actingAs($this->user)
            ->getJson(route('care-coordination.index', ['encounter_type' => $type, 'per_page' => 100]))
            ->assertOk()
            ->json('data.*.id');
    }

    private function payload(HospitalizationTrackerModel $tracker): array
    {
        return [
            'hospitalization_tracker_id' => $tracker->id,
            'care_coordination_type_id' => $this->typeId(),
        ];
    }

    private function makeTracker(MssqlPatients $patient, EncounterTypeEnum $type, HospitalizationTrackerStatusEnum $status): HospitalizationTrackerModel
    {
        $encounter = EncounterModel::create([
            'patient_id' => $patient->id,
            'type' => $type,
            'status' => EncounterStatusEnum::ADMITTED,
            'admission_at' => now(),
        ]);

        return HospitalizationTrackerModel::create([
            'encounter_id' => $encounter->id,
            'status' => $status,
            'assigned_to_id' => $this->user->id,
        ]);
    }

    private function makeCareCoordination(HospitalizationTrackerModel $tracker): CareCoordinationModel
    {
        return CareCoordinationModel::create([
            'encounter_id' => $tracker->encounter_id,
            'care_coordination_type_id' => $this->typeId(),
            'assigned_to_id' => $this->user->id,
            'due_at' => now()->addDay(),
        ]);
    }

    private function typeId(): int
    {
        return (new CareCoordinationTypeFixture())->create()->id;
    }

    /** A fresh patient with no encounters, distinct per call. */
    private function uniquePatient(): MssqlPatients
    {
        return (new VbcPatientScenario())->create();
    }
}
