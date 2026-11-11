<?php

namespace Tests\Feature\VBC\Http\Controllers\HospitalizationTrackerContact;

use App\User;
use App\VBC\Enum\Encounter\EncounterStatusEnum;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Http\Middleware\EnsurePermission;
use App\VBC\Models\Encounter\EncounterModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\Fixtures\ContactTypeFixture;
use Tests\Support\Fixtures\UserFixture;
use Tests\Support\Scenarios\VbcPatientScenario;
use Tests\TestCase;

/** Contacts endpoints shared by the Hospitalization (IP) and ED Visit (ED) viewers (PD-3693 / PD-3655). */
class HospitalizationTrackerContactControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsurePermission::class);
        $this->user = User::query()->findOrFail((new UserFixture())->create()->id);
    }

    public function test_ed_contact_is_created_and_updated_on_an_ed_tracker(): void
    {
        $tracker = $this->makeTracker(EncounterTypeEnum::ED);

        $created = $this->actingAs($this->user)
            ->postJson(route('hospitalization.trackers.contacts.store', ['trackerId' => $tracker->id]), $this->payload(['encounter_type' => 'ED']))
            ->assertCreated()
            ->assertJsonPath('data.contactName', 'Jane Doe');

        $this->assertDatabaseHas('hospitalization_tracker_contacts', [
            'id' => $created->json('data.id'),
            'hospitalization_tracker_id' => $tracker->id,
        ]);

        $this->actingAs($this->user)
            ->patchJson(
                route('hospitalization.trackers.contacts.update', ['trackerId' => $tracker->id, 'contactId' => $created->json('data.id')]),
                $this->payload(['encounter_type' => 'ED', 'contact_name' => 'Jane Roe'])
            )
            ->assertOk()
            ->assertJsonPath('data.contactName', 'Jane Roe');
    }

    public function test_ed_request_against_an_ip_tracker_is_not_found(): void
    {
        $tracker = $this->makeTracker(EncounterTypeEnum::IP);

        $this->actingAs($this->user)
            ->postJson(route('hospitalization.trackers.contacts.store', ['trackerId' => $tracker->id]), $this->payload(['encounter_type' => 'ED']))
            ->assertNotFound();
    }

    public function test_request_without_encounter_type_defaults_to_ip_and_rejects_an_ed_tracker(): void
    {
        $ip = $this->makeTracker(EncounterTypeEnum::IP);
        $ed = $this->makeTracker(EncounterTypeEnum::ED);

        $this->actingAs($this->user)
            ->postJson(route('hospitalization.trackers.contacts.store', ['trackerId' => $ip->id]), $this->payload())
            ->assertCreated();
        $this->actingAs($this->user)
            ->postJson(route('hospitalization.trackers.contacts.store', ['trackerId' => $ed->id]), $this->payload())
            ->assertNotFound();
    }

    public function test_ed_contact_cannot_be_created_when_tracker_is_not_in_progress(): void
    {
        $tracker = $this->makeTracker(EncounterTypeEnum::ED, HospitalizationTrackerStatusEnum::READY_FOR_REVIEW);

        $this->actingAs($this->user)
            ->postJson(route('hospitalization.trackers.contacts.store', ['trackerId' => $tracker->id]), $this->payload(['encounter_type' => 'ED']))
            ->assertStatus(409);
    }

    public function test_ed_contact_requires_name_relationship_and_phone_or_email(): void
    {
        $tracker = $this->makeTracker(EncounterTypeEnum::ED);

        $this->actingAs($this->user)
            ->postJson(route('hospitalization.trackers.contacts.store', ['trackerId' => $tracker->id]), ['encounter_type' => 'ED'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['contact_name', 'contact_type_id', 'phone_number', 'email']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'contact_type_id' => (new ContactTypeFixture())->create()->id,
            'contact_name' => 'Jane Doe',
            'email' => 'jane@example.com',
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
