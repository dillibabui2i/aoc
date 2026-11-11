<?php

namespace Tests\Feature\VBC\Http\Controllers\Hospitalization;

use App\User;
use App\VBC\Enum\Encounter\EncounterStatusEnum;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Http\Middleware\EnsurePermission;
use App\VBC\Models\Encounter\EncounterModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\Fixtures\UserFixture;
use Tests\Support\Scenarios\VbcPatientScenario;
use Tests\TestCase;

/**
 * Feature tests for `PATCH /vbc/hospitalization/trackers/{id}/ed-follow-up` (PD-4073): the ED Visit
 * follow-up workflow Not Started -> First -> Second -> Third Attempt, with Completed before the third.
 */
class EdVisitFollowupControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsurePermission::class);
        $this->user = User::query()->findOrFail((new UserFixture())->create()->id);
    }

    /** AC1: no follow-up row yet -> the tracker reports Not Started. */
    public function test_tracker_without_a_follow_up_reports_not_started(): void
    {
        $tracker = $this->makeTracker();

        $this->actingAs($this->user)
            ->getJson(route('hospitalization.trackers.show', ['trackerId' => $tracker->id, 'encounterType' => 'ED']))
            ->assertOk()
            ->assertJsonPath('data.ed_follow_up_status', 'NOT_STARTED')
            ->assertJsonPath('data.ed_follow_up_status_label', 'Not Started');
    }

    /** The follow-up status keys are ED-only: an IP tracker's payload must not carry them. */
    public function test_ip_tracker_does_not_expose_the_ed_follow_up_status(): void
    {
        $tracker = $this->makeTracker([], EncounterTypeEnum::IP);

        $data = $this->actingAs($this->user)
            ->getJson(route('hospitalization.trackers.show', ['trackerId' => $tracker->id, 'encounterType' => 'IP']))
            ->assertOk()
            ->json('data');

        $this->assertArrayNotHasKey('ed_follow_up_status', $data);
        $this->assertArrayNotHasKey('ed_follow_up_status_label', $data);
    }

    /** AC3/AC5/AC13: first submit lazily creates the follow-up, persists the status and logs the notes. */
    public function test_first_attempt_creates_the_follow_up_and_logs_the_notes(): void
    {
        $tracker = $this->makeTracker();

        $this->submit($tracker, 'FIRST_ATTEMPT', 'Called, no answer')
            ->assertOk()
            ->assertJsonPath('data.ed_follow_up_status', 'FIRST_ATTEMPT')
            ->assertJsonPath('data.ed_follow_up_status_label', 'First Attempt');

        $this->assertSame(1, $tracker->followups()->count());
        $this->assertSame('Called, no answer', $tracker->followups()->first()->notes);

        $notes = $this->actingAs($this->user)
            ->getJson(route('hospitalization.trackers.notes.index', ['trackerId' => $tracker->id]))
            ->assertOk()
            ->json('data');
        $this->assertStringContainsString('Follow up notes: Called, no answer', json_encode($notes));

        $this->actingAs($this->user)
            ->getJson(route('hospitalization.trackers.show', ['trackerId' => $tracker->id, 'encounterType' => 'ED']))
            ->assertJsonPath('data.ed_follow_up_status', 'FIRST_ATTEMPT');
    }

    /** AC7/AC9: First -> Second -> Third Attempt, reusing the same follow-up row. */
    public function test_attempts_progress_in_order(): void
    {
        $tracker = $this->makeTracker();

        $this->submit($tracker, 'FIRST_ATTEMPT', 'one')->assertOk();
        $this->submit($tracker, 'SECOND_ATTEMPT', 'two')->assertOk()
            ->assertJsonPath('data.ed_follow_up_status', 'SECOND_ATTEMPT');
        $this->submit($tracker, 'THIRD_ATTEMPT', 'three')->assertOk()
            ->assertJsonPath('data.ed_follow_up_status', 'THIRD_ATTEMPT');

        $this->assertSame(1, $tracker->followups()->count());
    }

    /** AC3: Complete is allowed straight from Not Started. */
    public function test_complete_from_not_started(): void
    {
        $tracker = $this->makeTracker();

        $this->submit($tracker, 'COMPLETED', 'Reached the client')->assertOk()
            ->assertJsonPath('data.ed_follow_up_status', 'COMPLETED');
        $this->assertNotNull($tracker->followups()->first()->completed_at);
    }

    public function test_cannot_skip_attempts_or_move_past_a_terminal_status(): void
    {
        $tracker = $this->makeTracker();

        $this->submit($tracker, 'SECOND_ATTEMPT', 'skip')->assertStatus(409);
        $this->assertSame(0, $tracker->followups()->count());

        $this->submit($tracker, 'FIRST_ATTEMPT', 'one')->assertOk();
        $this->submit($tracker, 'THIRD_ATTEMPT', 'skip')->assertStatus(409);
        $this->submit($tracker, 'COMPLETED', 'done')->assertOk();
        $this->submit($tracker, 'FIRST_ATTEMPT', 'again')->assertStatus(409);
    }

    /** AC10: notes are required. */
    public function test_notes_are_required(): void
    {
        $tracker = $this->makeTracker();

        $this->submit($tracker, 'FIRST_ATTEMPT', '')->assertStatus(422)->assertJsonValidationErrors('notes');
        $this->assertSame(0, $tracker->followups()->count());
    }

    public function test_rejects_outcomes_outside_the_ed_workflow(): void
    {
        $tracker = $this->makeTracker();

        $this->submit($tracker, 'SCHEDULED', 'x')->assertStatus(422)->assertJsonValidationErrors('outcome');
    }

    /** AC11/AC12: no follow-up updates once the ED Visit is Completed or Closed. */
    public function test_completed_or_closed_ed_visit_rejects_follow_up_updates(): void
    {
        foreach ([HospitalizationTrackerStatusEnum::COMPLETED, HospitalizationTrackerStatusEnum::CLOSED] as $status) {
            $tracker = $this->makeTracker(['status' => $status]);

            $this->submit($tracker, 'FIRST_ATTEMPT', 'x')->assertStatus(409);
        }
    }

    public function test_non_ed_tracker_is_not_found(): void
    {
        $tracker = $this->makeTracker([], EncounterTypeEnum::IP);

        $this->submit($tracker, 'FIRST_ATTEMPT', 'x')->assertNotFound();
    }

    private function submit(HospitalizationTrackerModel $tracker, string $outcome, string $notes)
    {
        return $this->actingAs($this->user)->patchJson(
            route('hospitalization.trackers.ed-follow-up.update', ['trackerId' => $tracker->id]),
            ['outcome' => $outcome, 'notes' => $notes]
        );
    }

    private function makeTracker(array $tracker = [], EncounterTypeEnum $type = EncounterTypeEnum::ED): HospitalizationTrackerModel
    {
        $patient = (new VbcPatientScenario())->create();

        $encounter = EncounterModel::create([
            'patient_id' => $patient->id,
            'type' => $type,
            'status' => EncounterStatusEnum::ADMITTED,
            'admission_at' => now(),
        ]);

        return HospitalizationTrackerModel::create(array_merge([
            'encounter_id' => $encounter->id,
            'status' => HospitalizationTrackerStatusEnum::IN_PROGRESS,
            'assigned_to_id' => $this->user->id,
        ], $tracker));
    }
}
