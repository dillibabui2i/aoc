<?php

namespace Tests\Feature\VBC\Http\Controllers\HospitalizationTrackerDiagnosis;

use App\User;
use App\VBC\Enum\Diagnosis\DiagnosisClinicalStatusEnum;
use App\VBC\Enum\Diagnosis\DiagnosisRankEnum;
use App\VBC\Enum\Diagnosis\DiagnosisSystemEnum;
use App\VBC\Enum\Diagnosis\DiagnosisVerificationStatusEnum;
use App\VBC\Enum\Encounter\EncounterStatusEnum;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Http\Middleware\EnsurePermission;
use App\VBC\Models\Encounter\EncounterModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\Fixtures\UserFixture;
use Tests\Support\Scenarios\VbcPatientScenario;
use Tests\Support\Scenarios\HospitalizationTrackerPropertyDefinitionsScenario;
use Tests\TestCase;

/** Diagnosis endpoints shared by the Hospitalization (IP) and ED Visit (ED) viewers (PD-3498 / PD-3970). */
class HospitalizationTrackerDiagnosisControllerTest extends TestCase
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

    public function test_ed_diagnosis_is_created_and_updated_on_an_ed_tracker(): void
    {
        $tracker = $this->makeTracker(EncounterTypeEnum::ED);

        $created = $this->actingAs($this->user)
            ->postJson($this->storeUrl($tracker), $this->payload(['encounter_type' => 'ED']))
            ->assertCreated();

        $this->assertDatabaseHas('encounter_diagnoses', [
            'id' => $created->json('data.id'),
            'encounter_id' => $tracker->encounter_id,
        ]);

        $this->actingAs($this->user)
            ->patchJson(
                route('hospitalization.trackers.diagnoses.update', [
                    'trackerId' => $tracker->id,
                    'diagnosisId' => $created->json('data.id'),
                ]),
                $this->payload(['encounter_type' => 'ED', 'notes' => 'Changed'])
            )
            ->assertOk()
            ->assertJsonPath('data.notes', 'Changed');
    }

    public function test_ed_toggle_is_saved_on_an_ed_tracker(): void
    {
        $tracker = $this->makeTracker(EncounterTypeEnum::ED);

        $this->actingAs($this->user)
            ->patchJson($this->toggleUrl($tracker), ['was_diagnosis_made' => true, 'encounter_type' => 'ED'])
            ->assertOk()
            ->assertJsonPath('data.was_diagnosis_made', true);
    }

    public function test_ed_requests_against_an_ip_tracker_are_not_found(): void
    {
        $tracker = $this->makeTracker(EncounterTypeEnum::IP);

        $this->actingAs($this->user)
            ->postJson($this->storeUrl($tracker), $this->payload(['encounter_type' => 'ED']))
            ->assertNotFound();
        $this->actingAs($this->user)
            ->patchJson($this->toggleUrl($tracker), ['was_diagnosis_made' => true, 'encounter_type' => 'ED'])
            ->assertNotFound();
    }

    public function test_request_without_encounter_type_defaults_to_ip_and_rejects_an_ed_tracker(): void
    {
        $ip = $this->makeTracker(EncounterTypeEnum::IP);
        $ed = $this->makeTracker(EncounterTypeEnum::ED);

        $this->actingAs($this->user)->postJson($this->storeUrl($ip), $this->payload())->assertCreated();
        $this->actingAs($this->user)->postJson($this->storeUrl($ed), $this->payload())->assertNotFound();
        $this->actingAs($this->user)
            ->patchJson($this->toggleUrl($ed), ['was_diagnosis_made' => true])
            ->assertNotFound();
    }

    public function test_ed_diagnosis_cannot_be_written_when_tracker_is_not_in_progress(): void
    {
        $tracker = $this->makeTracker(EncounterTypeEnum::ED, HospitalizationTrackerStatusEnum::READY_FOR_REVIEW);

        $this->actingAs($this->user)
            ->postJson($this->storeUrl($tracker), $this->payload(['encounter_type' => 'ED']))
            ->assertStatus(409);
        $this->actingAs($this->user)
            ->patchJson($this->toggleUrl($tracker), ['was_diagnosis_made' => true, 'encounter_type' => 'ED'])
            ->assertStatus(409);
    }

    public function test_ed_diagnosis_update_is_rejected_when_tracker_is_not_in_progress(): void
    {
        $tracker = $this->makeTracker(EncounterTypeEnum::ED);
        $created = $this->actingAs($this->user)
            ->postJson($this->storeUrl($tracker), $this->payload(['encounter_type' => 'ED']))
            ->assertCreated();
        $tracker->update(['status' => HospitalizationTrackerStatusEnum::READY_FOR_REVIEW]);

        $this->actingAs($this->user)
            ->patchJson(
                $this->updateUrl($tracker, $created->json('data.id')),
                $this->payload(['encounter_type' => 'ED'])
            )
            ->assertStatus(409);
    }

    public function test_ed_diagnosis_requires_all_list_fields(): void
    {
        $tracker = $this->makeTracker(EncounterTypeEnum::ED);

        $this->actingAs($this->user)
            ->postJson($this->storeUrl($tracker), ['encounter_type' => 'ED'])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'icd_10_code',
                'description',
                'system',
                'rank',
                'clinical_status',
                'verification_status',
            ]);
    }

    public function test_diagnosis_of_another_encounter_is_not_found(): void
    {
        $tracker = $this->makeTracker(EncounterTypeEnum::ED);
        $other = $this->makeTracker(EncounterTypeEnum::ED);
        $created = $this->actingAs($this->user)
            ->postJson($this->storeUrl($other), $this->payload(['encounter_type' => 'ED']))
            ->assertCreated();

        $this->actingAs($this->user)
            ->patchJson(
                $this->updateUrl($tracker, $created->json('data.id')),
                $this->payload(['encounter_type' => 'ED'])
            )
            ->assertNotFound();
    }

    private function storeUrl(HospitalizationTrackerModel $tracker): string
    {
        return route('hospitalization.trackers.diagnoses.store', ['trackerId' => $tracker->id]);
    }

    private function updateUrl(HospitalizationTrackerModel $tracker, int $diagnosisId): string
    {
        return route('hospitalization.trackers.diagnoses.update', [
            'trackerId' => $tracker->id,
            'diagnosisId' => $diagnosisId,
        ]);
    }

    private function toggleUrl(HospitalizationTrackerModel $tracker): string
    {
        return route('hospitalization.trackers.diagnosis-toggle.update', ['trackerId' => $tracker->id]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'icd_10_code' => 'R94.5',
            'description' => 'Abnormal results of liver function studies',
            'system' => DiagnosisSystemEnum::GASTROINTESTINAL->value,
            'rank' => DiagnosisRankEnum::PRIMARY->value,
            'clinical_status' => DiagnosisClinicalStatusEnum::ACTIVE->value,
            'verification_status' => DiagnosisVerificationStatusEnum::CONFIRMED->value,
            'notes' => 'Some notes',
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
