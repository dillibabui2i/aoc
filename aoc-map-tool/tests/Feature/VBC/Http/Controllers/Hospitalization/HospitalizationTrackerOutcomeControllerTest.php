<?php

namespace Tests\Feature\VBC\Http\Controllers\Hospitalization;

use App\User;
use App\VBC\Enum\Encounter\EncounterStatusEnum;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerClosingReasonEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Http\Middleware\EnsurePermission;
use App\VBC\Models\Encounter\EncounterModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\Scenarios\VbcPatientScenario;
use Tests\Support\Fixtures\UserFixture;
use Tests\Support\Scenarios\HospitalizationTrackerPropertyDefinitionsScenario;
use Tests\TestCase;

/**
 * Feature tests for `PATCH /vbc/hospitalization/trackers/{id}/outcome` (HospitalizationController::
 * updateOutcome -> HospitalizationTrackerOutcomeService::transition()), covering the ED Visit outcome
 * workflow added by PD-4069 and a couple of Hospitalization (IP) regression checks — the transition
 * map/closing reasons/completeness guard the two lifecycles share were all made encounter-type-aware by
 * this ticket, so both sides need coverage that neither one leaked into the other.
 */
class HospitalizationTrackerOutcomeControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $assignee;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsurePermission::class);
        (new HospitalizationTrackerPropertyDefinitionsScenario())->create();
        $this->assignee = User::query()->findOrFail((new UserFixture())->create()->id);
        $this->otherUser = User::query()->findOrFail((new UserFixture())->create()->id);
    }

    /** AC3: In Progress -> Ready for Review assigns the tracker to the selected reviewer and logs a note. */
    public function test_ed_tracker_moves_from_in_progress_to_ready_for_review(): void
    {
        $tracker = $this->makeEdTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS]);
        $this->fillEdInfo($tracker);

        $response = $this->actingAs($this->assignee)->patchJson(
            route('hospitalization.trackers.outcome.update', ['trackerId' => $tracker->id]),
            [
                'outcome' => HospitalizationTrackerStatusEnum::READY_FOR_REVIEW->value,
                'assignee_id' => $this->otherUser->id,
                'notes' => 'Please review.',
            ]
        );

        $response->assertOk();
        $response->assertJsonPath('data.status', HospitalizationTrackerStatusEnum::READY_FOR_REVIEW->label());
        $response->assertJsonPath('data.assigned_to_id', $this->otherUser->id);

        $tracker->refresh();
        $this->assertSame(HospitalizationTrackerStatusEnum::READY_FOR_REVIEW, $tracker->status);
        $this->assertSame($this->otherUser->id, $tracker->assigned_to_id);
    }

    /** PD-4069 AC6: a new ED tracker has no assignee, so the submitter is stored as sent_to_review_by_id. */
    public function test_ed_ready_for_review_stores_the_submitter_when_tracker_had_no_assignee(): void
    {
        $tracker = $this->makeEdTracker([
            'status' => HospitalizationTrackerStatusEnum::IN_PROGRESS,
            'assigned_to_id' => null,
        ]);
        $this->fillEdInfo($tracker);

        $response = $this->actingAs($this->assignee)->patchJson(
            route('hospitalization.trackers.outcome.update', ['trackerId' => $tracker->id]),
            [
                'outcome' => HospitalizationTrackerStatusEnum::READY_FOR_REVIEW->value,
                'assignee_id' => $this->otherUser->id,
                'notes' => 'Please review.',
            ]
        );

        $response->assertOk();
        $response->assertJsonPath('data.sent_to_review_by_id', $this->assignee->id);
        $this->assertSame($this->assignee->id, $tracker->refresh()->sent_to_review_by_id);
    }

    /** AC4-AC7: Ready for Review -> Ready for Next Steps, defaulting "Next steps to be made by" is a frontend concern (AC6), but the backend must persist sent_to_review_by_id so that default has data to read. */
    public function test_ed_tracker_moves_from_ready_for_review_to_ready_for_next_steps_and_remembers_the_reviewer(): void
    {
        $tracker = $this->makeEdTracker([
            'status' => HospitalizationTrackerStatusEnum::READY_FOR_REVIEW,
            'assigned_to_id' => $this->otherUser->id,
            'sent_to_review_by_id' => $this->otherUser->id,
        ]);
        $this->fillEdInfo($tracker);

        $response = $this->actingAs($this->assignee)->patchJson(
            route('hospitalization.trackers.outcome.update', ['trackerId' => $tracker->id]),
            [
                'outcome' => HospitalizationTrackerStatusEnum::READY_FOR_NEXT_STEPS->value,
                'assignee_id' => $this->assignee->id,
            ]
        );

        $response->assertOk();
        $response->assertJsonPath('data.status', HospitalizationTrackerStatusEnum::READY_FOR_NEXT_STEPS->label());
        $response->assertJsonPath('data.assigned_to_id', $this->assignee->id);
        $response->assertJsonPath('data.sent_to_review_by_id', $this->otherUser->id);
    }

    /** AC9/AC10: Ready for Next Steps -> Complete succeeds once the six required ED Hospitalization Info fields are filled (PD-5800); ED has no Current Hospitalization Info guard beyond them. */
    public function test_ed_tracker_completes_once_the_required_hospitalization_info_is_filled(): void
    {
        $tracker = $this->makeEdTracker(['status' => HospitalizationTrackerStatusEnum::READY_FOR_NEXT_STEPS]);
        $this->fillEdInfo($tracker);

        $response = $this->actingAs($this->assignee)->patchJson(
            route('hospitalization.trackers.outcome.update', ['trackerId' => $tracker->id]),
            [
                'outcome' => HospitalizationTrackerStatusEnum::COMPLETED->value,
                'assignee_id' => $this->assignee->id,
            ]
        );

        $response->assertOk();
        $response->assertJsonPath('data.status', HospitalizationTrackerStatusEnum::COMPLETED->label());
    }

    /** AC12/AC13: Close accepts "Opened by Mistake" and the new "Hospitalization" reason for an ED tracker. */
    public function test_ed_tracker_closes_with_the_hospitalization_reason(): void
    {
        $tracker = $this->makeEdTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS]);
        $this->fillEdInfo($tracker);

        $response = $this->actingAs($this->assignee)->patchJson(
            route('hospitalization.trackers.outcome.update', ['trackerId' => $tracker->id]),
            [
                'outcome' => HospitalizationTrackerStatusEnum::CLOSED->value,
                'reason' => HospitalizationTrackerClosingReasonEnum::HOSPITALIZATION->value,
                'notes' => 'Converted to a hospitalization.',
            ]
        );

        $response->assertOk();
        $response->assertJsonPath('data.status', HospitalizationTrackerStatusEnum::CLOSED->label());
        $response->assertJsonPath('data.closing_reason', HospitalizationTrackerClosingReasonEnum::HOSPITALIZATION->value);
    }

    /** Hospitalization's own "ED Visit" closing reason must not be accepted for an actual ED tracker (AC12 lists only two reasons, not three). */
    public function test_ed_tracker_close_rejects_the_hospitalization_only_ed_visit_reason(): void
    {
        $tracker = $this->makeEdTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS]);

        $response = $this->actingAs($this->assignee)->patchJson(
            route('hospitalization.trackers.outcome.update', ['trackerId' => $tracker->id]),
            [
                'outcome' => HospitalizationTrackerStatusEnum::CLOSED->value,
                'reason' => HospitalizationTrackerClosingReasonEnum::ED_VISIT->value,
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('reason');
    }

    /** ED's transition map disallows skipping straight from In Progress to Ready for Next Steps. */
    public function test_ed_tracker_cannot_skip_ready_for_review(): void
    {
        $tracker = $this->makeEdTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS]);

        $response = $this->actingAs($this->assignee)->patchJson(
            route('hospitalization.trackers.outcome.update', ['trackerId' => $tracker->id]),
            [
                'outcome' => HospitalizationTrackerStatusEnum::READY_FOR_NEXT_STEPS->value,
                'assignee_id' => $this->assignee->id,
            ]
        );

        $response->assertStatus(409);
    }

    /**
     * Regression: a Hospitalization (IP) tracker must still be rejected on the same transition ED now
     * allows (In Progress -> Ready for Review directly) — the per-encounter-type map must not have
     * widened IP's own rules while adding ED's.
     */
    public function test_ip_tracker_still_cannot_skip_discharged_to_reach_ready_for_review(): void
    {
        $tracker = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS]);

        $response = $this->actingAs($this->assignee)->patchJson(
            route('hospitalization.trackers.outcome.update', ['trackerId' => $tracker->id]),
            [
                'outcome' => HospitalizationTrackerStatusEnum::READY_FOR_REVIEW->value,
                'assignee_id' => $this->assignee->id,
            ]
        );

        $response->assertStatus(409);
    }

    /**
     * Regression: Hospitalization's own closing reasons are unaffected — "ED Visit" must still be
     * accepted and the new "Hospitalization" reason must still be rejected for an IP tracker.
     */
    public function test_ip_tracker_close_still_accepts_ed_visit_and_rejects_the_hospitalization_reason(): void
    {
        $tracker = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS]);

        $accepted = $this->actingAs($this->assignee)->patchJson(
            route('hospitalization.trackers.outcome.update', ['trackerId' => $tracker->id]),
            [
                'outcome' => HospitalizationTrackerStatusEnum::CLOSED->value,
                'reason' => HospitalizationTrackerClosingReasonEnum::ED_VISIT->value,
            ]
        );
        $accepted->assertOk();

        $otherTracker = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS]);
        $rejected = $this->actingAs($this->assignee)->patchJson(
            route('hospitalization.trackers.outcome.update', ['trackerId' => $otherTracker->id]),
            [
                'outcome' => HospitalizationTrackerStatusEnum::CLOSED->value,
                'reason' => HospitalizationTrackerClosingReasonEnum::HOSPITALIZATION->value,
            ]
        );
        $rejected->assertStatus(422);
        $rejected->assertJsonValidationErrors('reason');
    }

    /** PD-5800 AC7/AC8: every ED transition is blocked while a required field is empty, naming each missing one. */
    public function test_ed_transition_is_blocked_and_lists_every_missing_required_field(): void
    {
        $cases = [
            [HospitalizationTrackerStatusEnum::IN_PROGRESS, ['outcome' => 'READY_FOR_REVIEW', 'assignee_id' => $this->otherUser->id]],
            [HospitalizationTrackerStatusEnum::READY_FOR_REVIEW, ['outcome' => 'READY_FOR_NEXT_STEPS', 'assignee_id' => $this->assignee->id]],
            [HospitalizationTrackerStatusEnum::READY_FOR_NEXT_STEPS, ['outcome' => 'COMPLETED', 'assignee_id' => $this->assignee->id]],
            [HospitalizationTrackerStatusEnum::IN_PROGRESS, ['outcome' => 'CLOSED', 'reason' => HospitalizationTrackerClosingReasonEnum::HOSPITALIZATION->value]],
        ];

        foreach ($cases as [$status, $payload]) {
            $tracker = $this->makeEdTracker(['status' => $status]);
            // ED Visit "Date and time of occurrence" is stored as the encounter's admission_at, which the fixture fills.
            $tracker->encounter->update(['admission_at' => null]);

            $response = $this->actingAs($this->assignee)->patchJson(
                route('hospitalization.trackers.outcome.update', ['trackerId' => $tracker->id]),
                $payload
            );

            $response->assertStatus(409);
            $response->assertJsonPath('missing_fields', [
                'facility_name',
                'ed_visit_type',
                'source',
                'ed_visit_occurred_at',
                'ed_visit_report_filed_at',
                'ed_visit_reason',
            ]);
            $this->assertStringContainsString('Date Report filed', $response->json('message'));
            $this->assertStringContainsString('Reason for ED Visit.', $response->json('message'));
            $this->assertSame($status, $tracker->refresh()->status);
        }
    }

    /** PD-5800 AC9 (the same transition succeeds once the fields are saved) and AC8 (only the empty ones are listed). */
    public function test_ed_transition_lists_only_the_fields_still_empty_then_succeeds_once_filled(): void
    {
        $tracker = $this->makeEdTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS]);
        $this->fillEdInfo($tracker);
        // Legacy rows can hold a blank the whole-tab save (which requires all six) would now refuse.
        $tracker->encounter->update(['admission_reason' => null]);
        $payload = ['outcome' => 'READY_FOR_REVIEW', 'assignee_id' => $this->otherUser->id];
        $url = route('hospitalization.trackers.outcome.update', ['trackerId' => $tracker->id]);

        $this->actingAs($this->assignee)->patchJson($url, $payload)
            ->assertStatus(409)
            ->assertJsonPath('missing_fields', ['ed_visit_reason']);

        $this->fillEdInfo($tracker);
        $this->actingAs($this->assignee)->patchJson($url, $payload)->assertOk();
    }

    /** PD-5800 AC11: the ED guard does not touch Hospitalization (IP) trackers' Close. */
    public function test_ip_tracker_close_is_not_gated_by_the_ed_required_fields(): void
    {
        $tracker = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS]);

        $this->actingAs($this->assignee)->patchJson(
            route('hospitalization.trackers.outcome.update', ['trackerId' => $tracker->id]),
            ['outcome' => 'CLOSED', 'reason' => HospitalizationTrackerClosingReasonEnum::ED_VISIT->value]
        )->assertOk();
    }

    /** Saves the ED Hospitalization Info tab through its own endpoint, the way the UI does. */
    private function fillEdInfo(HospitalizationTrackerModel $tracker): void
    {
        $this->actingAs($this->assignee)->putJson(
            route('hospitalization.trackers.ed-info.update', ['trackerId' => $tracker->id]),
            [
                'facility_name' => 'Child Care Hospital',
                'ed_visit_type' => 'AVOIDABLE',
                'source' => 'BAMBOO_HEALTH',
                'ed_visit_occurred_at' => '2026-03-30 10:00:00',
                'ed_visit_report_filed_at' => '2026-03-30',
                'ed_visit_reason' => 'Fell at home.',
                'sent_2067_form_to_payer' => true,
                'ed_tt_contacted_prior_to_admit' => true,
                'did_tt_direct_ed_visit' => true,
                'staffing_concerns' => true,
                'dme_concerns' => false,
                'pdn_on_shift_during_ed_visit' => true,
                'incident_report_completed_in_kt' => true,
                'annual_flu_vaccine_status' => 'NO',
                'covid19_vaccine_status' => 'NO',
                'rsv_vaccine_status' => 'NO',
            ]
        )->assertOk();
    }

    private function makeTracker(array $tracker = [], EncounterTypeEnum $encounterType = EncounterTypeEnum::IP): HospitalizationTrackerModel
    {
        $patient = (new VbcPatientScenario())->create();

        $encounter = EncounterModel::create([
            'patient_id' => $patient->id,
            'type' => $encounterType,
            'status' => EncounterStatusEnum::ADMITTED,
            'admission_at' => now(),
        ]);

        return HospitalizationTrackerModel::create(array_merge([
            'encounter_id' => $encounter->id,
            'status' => HospitalizationTrackerStatusEnum::OPEN,
            'assigned_to_id' => $this->assignee->id,
        ], $tracker));
    }

    private function makeEdTracker(array $tracker = []): HospitalizationTrackerModel
    {
        return $this->makeTracker(array_merge(['assigned_to_id' => $this->assignee->id], $tracker), EncounterTypeEnum::ED);
    }
}
