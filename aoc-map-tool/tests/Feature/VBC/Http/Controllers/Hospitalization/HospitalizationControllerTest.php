<?php

namespace Tests\Feature\VBC\Http\Controllers\Hospitalization;

use App\User;
use App\VBC\Enum\Encounter\EncounterStatusEnum;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerIcmLevelEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Http\Middleware\EnsurePermission;
use App\VBC\Models\Encounter\EncounterModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\Fixtures\UserFixture;
use Tests\Support\Scenarios\VbcPatientScenario;
use Tests\Support\Scenarios\HospitalizationTrackerPropertyDefinitionsScenario;
use Tests\TestCase;

/**
 * Feature tests for the "Create Notes" endpoint (AC5-AC12): a note is stored through the shared
 * `notes` table (App\Notes) via HospitalizationTrackerActivityLog::write(), the same mechanism the
 * field-level Activity Log uses, and comes back out through the same GET .../notes response shape.
 *
 * Also covers the "ED Visit Record" creation endpoint (PD-3983): it reuses `storeTracker()`'s creation
 * path, parameterized to produce an ED/"In Progress" tracker instead of an IP/"Open" one.
 *
 * Permission enforcement (the 'n' route middleware) is a separate concern from this endpoint's own
 * logic and isn't wired to any role/permission fixture in aoc_test, so it's bypassed here.
 */
class HospitalizationControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $assignee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsurePermission::class);
        (new HospitalizationTrackerPropertyDefinitionsScenario())->create();
        $this->assignee = User::query()->findOrFail((new UserFixture())->create()->id);
    }

    public function test_a_valid_note_is_stored_and_returned_newest_first(): void
    {
        $tracker = $this->makeTracker();

        $response = $this->actingAs($this->assignee)->postJson(
            route('hospitalization.trackers.notes.store', ['trackerId' => $tracker->id]),
            ['notes' => 'Called the facility for a status update.']
        );

        $response->assertOk();
        $response->assertJsonPath('data.0.notes', 'Called the facility for a status update.');
        $response->assertJsonPath('data.0.author_name', $this->assignee->name);

        $this->assertDatabaseHas('notes', [
            'entity' => 'hospitalization_tracker',
            'entity_id' => $tracker->id,
            'notes' => 'Called the facility for a status update.',
            'created_by' => $this->assignee->id,
        ]);
    }

    public function test_a_blank_note_is_rejected(): void
    {
        $tracker = $this->makeTracker();

        $response = $this->actingAs($this->assignee)->postJson(
            route('hospitalization.trackers.notes.store', ['trackerId' => $tracker->id]),
            ['notes' => '']
        );

        $response->assertJsonValidationErrors('notes');
        $this->assertDatabaseMissing('notes', [
            'entity' => 'hospitalization_tracker',
            'entity_id' => $tracker->id,
        ]);
    }

    public function test_a_note_is_rejected_for_a_closed_tracker(): void
    {
        $tracker = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::CLOSED]);

        $response = $this->actingAs($this->assignee)->postJson(
            route('hospitalization.trackers.notes.store', ['trackerId' => $tracker->id]),
            ['notes' => 'Too late.']
        );

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('notes', [
            'entity' => 'hospitalization_tracker',
            'entity_id' => $tracker->id,
        ]);
    }

    public function test_a_note_is_rejected_for_a_completed_tracker(): void
    {
        $tracker = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::COMPLETED]);

        $response = $this->actingAs($this->assignee)->postJson(
            route('hospitalization.trackers.notes.store', ['trackerId' => $tracker->id]),
            ['notes' => 'Too late.']
        );

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('notes', [
            'entity' => 'hospitalization_tracker',
            'entity_id' => $tracker->id,
        ]);
    }

    public function test_an_ed_visit_note_is_rejected_for_a_completed_tracker(): void
    {
        $tracker = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::COMPLETED], EncounterTypeEnum::ED);

        $response = $this->actingAs($this->assignee)->postJson(
            route('hospitalization.trackers.notes.store', ['trackerId' => $tracker->id]),
            ['notes' => 'Too late.']
        );

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('notes', [
            'entity' => 'hospitalization_tracker',
            'entity_id' => $tracker->id,
        ]);
    }

    public function test_an_ed_visit_note_is_rejected_for_a_cancelled_tracker(): void
    {
        $tracker = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::CANCELLED], EncounterTypeEnum::ED);

        $this->actingAs($this->assignee)->postJson(
            route('hospitalization.trackers.notes.store', ['trackerId' => $tracker->id]),
            ['notes' => 'Too late.']
        )->assertUnprocessable();
    }

    public function test_an_ed_visit_note_is_rejected_for_a_closed_tracker(): void
    {
        $tracker = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::CLOSED], EncounterTypeEnum::ED);

        $response = $this->actingAs($this->assignee)->postJson(
            route('hospitalization.trackers.notes.store', ['trackerId' => $tracker->id]),
            ['notes' => 'Too late.']
        );

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('notes', [
            'entity' => 'hospitalization_tracker',
            'entity_id' => $tracker->id,
        ]);
    }

    public function test_a_note_is_rejected_for_an_unknown_tracker(): void
    {
        $missingId = (int) DB::table('hospitalization_trackers')->max('id') + 1000;

        $response = $this->actingAs($this->assignee)->postJson(
            route('hospitalization.trackers.notes.store', ['trackerId' => $missingId]),
            ['notes' => 'Anything.']
        );

        $response->assertNotFound();
    }

    /**
     * "ED Visit Record" form submit (PD-3983 AC7): the created tracker is an ED encounter saved straight
     * to "In Progress", not an IP encounter saved to "Open" like the Hospitalization Record form.
     */
    public function test_an_ed_visit_tracker_is_created_as_ed_and_in_progress(): void
    {
        $patient = (new VbcPatientScenario())->create();

        $response = $this->actingAs($this->assignee)->postJson(
            route('hospitalization.ed-visits.trackers.store'),
            [
                'patient_id' => $patient->id,
                'assigned_to_id' => $this->assignee->id,
                'level' => HospitalizationTrackerIcmLevelEnum::LEVEL_I->value,
            ]
        );

        $response->assertCreated();
        $response->assertJsonPath('data.status', HospitalizationTrackerStatusEnum::IN_PROGRESS->label());

        $trackerId = $response->json('data.tracker_id');
        $tracker = HospitalizationTrackerModel::query()->findOrFail($trackerId);

        $this->assertSame(HospitalizationTrackerStatusEnum::IN_PROGRESS, $tracker->status);
        $this->assertSame(EncounterTypeEnum::ED, $tracker->encounter->type);
    }

    /**
     * AC5/AC18: a client with an active tracker of either kind cannot be given a second one — the ED
     * form 409s the same way the Hospitalization Record form does, since the duplicate check is
     * encounter-type-agnostic.
     */
    public function test_an_ed_visit_tracker_is_rejected_when_an_active_tracker_already_exists(): void
    {
        $tracker = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::OPEN]);
        $patientId = $tracker->encounter->patient_id;
        $before = HospitalizationTrackerModel::query()->count();

        $response = $this->actingAs($this->assignee)->postJson(
            route('hospitalization.ed-visits.trackers.store'),
            [
                'patient_id' => $patientId,
                'assigned_to_id' => $this->assignee->id,
                'level' => HospitalizationTrackerIcmLevelEnum::LEVEL_I->value,
            ]
        );

        $response->assertStatus(409);
        $response->assertJsonPath('tracker_id', $tracker->id);
        $response->assertJsonPath('encounter_type', EncounterTypeEnum::IP->value);
        $this->assertSame($before, HospitalizationTrackerModel::query()->count());
    }

    /** AC5/AC18: ED-vs-ED duplicate is rejected too. */
    public function test_an_ed_visit_tracker_is_rejected_when_an_active_ed_tracker_already_exists(): void
    {
        $tracker = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS], EncounterTypeEnum::ED);

        $response = $this->actingAs($this->assignee)->postJson(
            route('hospitalization.ed-visits.trackers.store'),
            [
                'patient_id' => $tracker->encounter->patient_id,
                'assigned_to_id' => $this->assignee->id,
                'level' => HospitalizationTrackerIcmLevelEnum::LEVEL_I->value,
            ]
        );

        $response->assertStatus(409);
        $response->assertJsonPath('tracker_id', $tracker->id);
        $response->assertJsonPath('encounter_type', EncounterTypeEnum::ED->value);
    }

    /** The client lookup behind both forms flags an active tracker of any kind, ED included. */
    public function test_client_lookup_flags_an_active_ed_tracker(): void
    {
        $tracker = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS], EncounterTypeEnum::ED);

        $response = $this->actingAs($this->assignee)->getJson(
            route('hospitalization.clients.show', ['patientId' => $tracker->encounter->patient_id])
        );

        $response->assertOk();
        $response->assertJsonPath('meta.has_active_tracker', true);
        $response->assertJsonPath('meta.active_tracker_id', $tracker->id);
        $response->assertJsonPath('meta.active_tracker_encounter_type', EncounterTypeEnum::ED->value);
    }

    /** The client lookup reports the kind of the active tracker, so the ED form can block on an active IP. */
    public function test_client_lookup_reports_the_encounter_type_of_an_active_ip_tracker(): void
    {
        $tracker = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::OPEN]);

        $response = $this->actingAs($this->assignee)->getJson(
            route('hospitalization.clients.show', ['patientId' => $tracker->encounter->patient_id])
        );

        $response->assertOk();
        $response->assertJsonPath('meta.active_tracker_id', $tracker->id);
        $response->assertJsonPath('meta.active_tracker_encounter_type', EncounterTypeEnum::IP->value);
    }

    /** Regression: the Hospitalization Record form still creates an IP tracker as Open. */
    public function test_a_hospitalization_tracker_is_created_as_ip_and_open(): void
    {
        $patient = (new VbcPatientScenario())->create();

        $response = $this->actingAs($this->assignee)->postJson(
            route('hospitalization.trackers.store'),
            [
                'patient_id' => $patient->id,
                'assigned_to_id' => $this->assignee->id,
                'level' => HospitalizationTrackerIcmLevelEnum::LEVEL_I->value,
            ]
        );

        $response->assertCreated();
        $tracker = HospitalizationTrackerModel::query()->findOrFail($response->json('data.tracker_id'));
        $this->assertSame(HospitalizationTrackerStatusEnum::OPEN, $tracker->status);
        $this->assertSame(EncounterTypeEnum::IP, $tracker->encounter->type);
    }

    /**
     * The ED Visit Record's "Hospitalization Info" tab (PD-3983): a field set distinct from
     * Hospitalization's Current Hospitalization Info, written through its own endpoint
     * (`hospitalization.trackers.ed-info.update`) against the same four storage targets — an encounter
     * column (`ed_visit_type` -> `admission_type`), a tracker column reused as-is (`source`), and new
     * tracker custom properties (EAV) for fields unique to this tab.
     */
    public function test_ed_visit_info_fields_are_saved_to_their_declared_storage_targets(): void
    {
        $tracker = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS], EncounterTypeEnum::ED);

        $response = $this->actingAs($this->assignee)->putJson(
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
                'staffing_percentage_during_week_of_ed_visit' => '20%',
                'annual_flu_vaccine_status' => 'YES',
                'flu_vaccination_date' => '2026-03-03',
                'covid19_vaccine_status' => 'NO',
                'rsv_vaccine_status' => 'UNABLE_TO_ASSESS',
            ]
        );

        $response->assertOk();
        $response->assertJsonPath('data.facility_name', 'Child Care Hospital');
        $response->assertJsonPath('data.ed_visit_type', 'AVOIDABLE');
        $response->assertJsonPath('data.source', 'BAMBOO_HEALTH');
        $response->assertJsonPath('data.ed_visit_reason', 'Fell at home.');
        $response->assertJsonPath('data.sent_2067_form_to_payer', true);
        $response->assertJsonPath('data.staffing_percentage_during_week_of_ed_visit', '20%');
        $response->assertJsonPath('data.annual_flu_vaccine_status', 'YES');
        $response->assertJsonPath('data.covid19_vaccine_status', 'NO');
        $response->assertJsonPath('data.rsv_vaccine_status', 'UNABLE_TO_ASSESS');

        $tracker->refresh();
        $this->assertSame('AVOIDABLE', $tracker->encounter->admission_type->value);
        $this->assertSame('Fell at home.', $tracker->encounter->admission_reason);
    }

    /** A required ED Visit Info field left out is rejected (AC10), same as Hospitalization's Current Info section. */
    public function test_ed_visit_info_rejects_a_missing_required_field(): void
    {
        $tracker = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS]);

        $response = $this->actingAs($this->assignee)->putJson(
            route('hospitalization.trackers.ed-info.update', ['trackerId' => $tracker->id]),
            ['facility_name' => 'Child Care Hospital']
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ed_visit_type', 'source', 'ed_visit_occurred_at']);
    }

    /**
     * `hospitalization.trackers.show` takes `encounterType` as a path segment and must reject a mismatch (PD-3983
     * follow-up): an IP tracker id can't be opened through the ED viewer's `/ED` URL.
     */
    public function test_show_tracker_rejects_an_ip_tracker_requested_as_ed(): void
    {
        $tracker = $this->makeTracker();

        $response = $this->actingAs($this->assignee)->getJson(
            route('hospitalization.trackers.show', ['trackerId' => $tracker->id, 'encounterType' => 'ED'])
        );

        $response->assertNotFound();
    }

    /** Same guard, the other direction: an ED tracker id can't be opened through the IP viewer. */
    public function test_show_tracker_rejects_an_ed_tracker_requested_as_ip(): void
    {
        $tracker = $this->makeTracker([], EncounterTypeEnum::ED);

        $response = $this->actingAs($this->assignee)->getJson(
            route('hospitalization.trackers.show', ['trackerId' => $tracker->id, 'encounterType' => 'IP'])
        );

        $response->assertNotFound();
    }

    /** The matching encounter_type still resolves normally. */
    public function test_show_tracker_returns_the_tracker_when_encounter_type_matches(): void
    {
        $tracker = $this->makeTracker([], EncounterTypeEnum::ED);

        $response = $this->actingAs($this->assignee)->getJson(
            route('hospitalization.trackers.show', ['trackerId' => $tracker->id, 'encounterType' => 'ED'])
        );

        $response->assertOk();
        $response->assertJsonPath('data.tracker_id', $tracker->id);
    }

    /** The `{encounterType}` URL segment only accepts IP/ED: anything else doesn't match the route. */
    public function test_show_tracker_rejects_unknown_encounter_type(): void
    {
        $tracker = $this->makeTracker();

        $response = $this->actingAs($this->assignee)->getJson(
            "/vbc/hospitalization/trackers/{$tracker->id}/XX"
        );

        $response->assertNotFound();
    }

    /** PD-5800 AC2-AC5: one entry per changed ED field, none for unchanged ones, short Reason label, full text. */
    public function test_ed_visit_info_save_logs_one_entry_per_changed_field_and_none_when_unchanged(): void
    {
        $tracker = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS], EncounterTypeEnum::ED);
        $reason = str_repeat('r', 500);
        $payload = $this->edInfoPayload(['ed_visit_reason' => $reason]);
        $url = route('hospitalization.trackers.ed-info.update', ['trackerId' => $tracker->id]);

        $this->actingAs($this->assignee)->putJson($url, $payload)->assertOk();

        $texts = $this->trackerNoteTexts($tracker);
        $this->assertCount(6, $texts);
        $this->assertContains('Facility Name set to Child Care Hospital by ' . $this->assignee->name, $texts);
        $this->assertContains('Reason for ED Visit set to ' . $reason . ' by ' . $this->assignee->name, $texts);

        // Saving the same values again writes nothing new.
        $this->actingAs($this->assignee)->putJson($url, $payload)->assertOk();
        $this->assertCount(6, $this->trackerNoteTexts($tracker));

        // A change is logged on its own, as "updated from ... to ...".
        $this->actingAs($this->assignee)->putJson($url, $this->edInfoPayload(['ed_visit_reason' => $reason, 'facility_name' => 'Other']))->assertOk();
        $texts = $this->trackerNoteTexts($tracker);
        $this->assertCount(7, $texts);
        $this->assertContains('Facility Name updated from Child Care Hospital to Other by ' . $this->assignee->name, $texts);
    }

    /** The Reason limit is 500 characters on the server (PD-5800 AC6). */
    public function test_ed_visit_reason_over_500_characters_is_rejected(): void
    {
        $tracker = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS], EncounterTypeEnum::ED);

        $this->actingAs($this->assignee)->putJson(
            route('hospitalization.trackers.ed-info.update', ['trackerId' => $tracker->id]),
            $this->edInfoPayload(['ed_visit_reason' => str_repeat('r', 501)])
        )->assertStatus(422)->assertJsonValidationErrors('ed_visit_reason');
    }

    /** PD-5800 AC11: Facility Name is still not logged for a Hospitalization (IP) tracker. */
    public function test_ed_visit_info_facility_name_is_not_logged_for_an_ip_tracker(): void
    {
        $tracker = $this->makeTracker(['status' => HospitalizationTrackerStatusEnum::IN_PROGRESS]);

        $this->actingAs($this->assignee)->putJson(
            route('hospitalization.trackers.ed-info.update', ['trackerId' => $tracker->id]),
            $this->edInfoPayload()
        )->assertOk();

        foreach ($this->trackerNoteTexts($tracker) as $text) {
            $this->assertStringStartsNotWith('Facility Name', $text);
        }
    }

    /** @return array<int, string> */
    private function trackerNoteTexts(HospitalizationTrackerModel $tracker): array
    {
        return array_column(
            $this->actingAs($this->assignee)
                ->getJson(route('hospitalization.trackers.notes.index', ['trackerId' => $tracker->id]))
                ->assertOk()
                ->json('data'),
            'notes'
        );
    }

    private function edInfoPayload(array $override = []): array
    {
        return array_merge([
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
        ], $override);
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
}
