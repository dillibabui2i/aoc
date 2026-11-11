<?php

namespace Tests\Feature\VBC\Http\Controllers\HospitalizationTrackerTraining;

use App\User;
use App\VBC\Enum\Encounter\EncounterStatusEnum;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Http\Middleware\EnsurePermission;
use App\VBC\Models\Encounter\EncounterModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\Fixtures\HospitalizationTrackerTrainingSourceFixture;
use Tests\Support\Fixtures\HospitalizationTrackerTrainingTypeFixture;
use Tests\Support\Fixtures\UserFixture;
use Tests\Support\Scenarios\VbcPatientScenario;
use Tests\Support\Scenarios\HospitalizationTrackerPropertyDefinitionsScenario;
use Tests\TestCase;

/** Training/Education endpoints shared by the Hospitalization (IP) and ED Visit (ED) viewers (PD-3697 / PD-3973). */
class HospitalizationTrackerTrainingControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsurePermission::class);
        (new HospitalizationTrackerPropertyDefinitionsScenario())->create();
        $this->user = User::query()->findOrFail((new UserFixture())->create()->id);
    }

    public function test_ed_education_is_created_and_updated_on_an_ed_tracker(): void
    {
        $tracker = $this->makeTracker(EncounterTypeEnum::ED);

        $created = $this->actingAs($this->user)
            ->postJson(route('hospitalization.trackers.educations.store', ['trackerId' => $tracker->id]), $this->payload(['encounter_type' => 'ED']))
            ->assertCreated();

        $this->assertDatabaseHas('hospitalization_tracker_trainings', [
            'id' => $created->json('data.id'),
            'hospitalization_tracker_id' => $tracker->id,
        ]);

        $this->actingAs($this->user)
            ->patchJson(
                route('hospitalization.trackers.educations.update', ['trackerId' => $tracker->id, 'educationId' => $created->json('data.id')]),
                $this->payload(['encounter_type' => 'ED', 'is_completed' => true])
            )
            ->assertOk()
            ->assertJsonPath('data.is_completed', true);
    }

    public function test_ed_toggle_is_saved_on_an_ed_tracker(): void
    {
        $tracker = $this->makeTracker(EncounterTypeEnum::ED);

        $this->actingAs($this->user)
            ->patchJson(
                route('hospitalization.trackers.education-toggle.update', ['trackerId' => $tracker->id]),
                ['staff_family_training_required' => true, 'encounter_type' => 'ED']
            )
            ->assertOk()
            ->assertJsonPath('data.staff_family_training_required', true);
    }

    public function test_ed_requests_against_an_ip_tracker_are_not_found(): void
    {
        $tracker = $this->makeTracker(EncounterTypeEnum::IP);

        $this->actingAs($this->user)
            ->postJson(route('hospitalization.trackers.educations.store', ['trackerId' => $tracker->id]), $this->payload(['encounter_type' => 'ED']))
            ->assertNotFound();
        $this->actingAs($this->user)
            ->patchJson(
                route('hospitalization.trackers.education-toggle.update', ['trackerId' => $tracker->id]),
                ['staff_family_training_required' => true, 'encounter_type' => 'ED']
            )
            ->assertNotFound();
    }

    public function test_request_without_encounter_type_defaults_to_ip_and_rejects_an_ed_tracker(): void
    {
        $ip = $this->makeTracker(EncounterTypeEnum::IP);
        $ed = $this->makeTracker(EncounterTypeEnum::ED);

        $this->actingAs($this->user)
            ->postJson(route('hospitalization.trackers.educations.store', ['trackerId' => $ip->id]), $this->payload())
            ->assertCreated();
        $this->actingAs($this->user)
            ->postJson(route('hospitalization.trackers.educations.store', ['trackerId' => $ed->id]), $this->payload())
            ->assertNotFound();
    }

    public function test_ed_education_cannot_be_created_or_toggled_when_tracker_is_not_in_progress(): void
    {
        $tracker = $this->makeTracker(EncounterTypeEnum::ED, HospitalizationTrackerStatusEnum::READY_FOR_REVIEW);

        $this->actingAs($this->user)
            ->postJson(route('hospitalization.trackers.educations.store', ['trackerId' => $tracker->id]), $this->payload(['encounter_type' => 'ED']))
            ->assertStatus(409);
        $this->actingAs($this->user)
            ->patchJson(
                route('hospitalization.trackers.education-toggle.update', ['trackerId' => $tracker->id]),
                ['staff_family_training_required' => true, 'encounter_type' => 'ED']
            )
            ->assertStatus(409);
    }

    public function test_ed_education_requires_type_and_source(): void
    {
        $tracker = $this->makeTracker(EncounterTypeEnum::ED);

        $this->actingAs($this->user)
            ->postJson(route('hospitalization.trackers.educations.store', ['trackerId' => $tracker->id]), ['encounter_type' => 'ED'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['training_type_id', 'training_source_id', 'is_completed']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'training_type_id' => (new HospitalizationTrackerTrainingTypeFixture())->create()->id,
            'training_source_id' => (new HospitalizationTrackerTrainingSourceFixture())->create()->id,
            'notes' => 'Some notes',
            'is_completed' => false,
        ], $overrides);
    }

    private function makeTracker(
        EncounterTypeEnum $type,
        HospitalizationTrackerStatusEnum $status = HospitalizationTrackerStatusEnum::IN_PROGRESS,
    ): HospitalizationTrackerModel {
        $patient = (new VbcPatientScenario())->create();

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
}
