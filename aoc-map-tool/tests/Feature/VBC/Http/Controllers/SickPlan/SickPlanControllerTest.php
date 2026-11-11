<?php

namespace Tests\Feature\VBC\Http\Controllers\SickPlan;

use App\Helpers\ActivityNotes;
use App\User;
use App\VBC\Enum\Encounter\EncounterStatusEnum;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Http\Middleware\EnsurePermission;
use App\VBC\Models\Encounter\EncounterModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use App\VBC\Models\SickPlan\SickPlanItemModel;
use App\VBC\Models\SickPlan\SickPlanModel;
use App\VBC\Repositories\SickPlan\SickPlanWriteRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\Fixtures\SickPlanCatalogFixture;
use Tests\Support\Fixtures\UserFixture;
use Tests\Support\Scenarios\VbcPatientScenario;
use Tests\TestCase;

/**
 * PD-5450: `PATCH /vbc/hospitalization/trackers/{trackerId}/sick-plan/items/{itemId}` writes one Activity Log
 * note per save that actually changed Completed? or Notes. Note texts are pinned as literals.
 */
class SickPlanControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsurePermission::class);
        $this->user = User::query()->findOrFail((new UserFixture())->create()->id);
    }

    public function test_completing_an_item_logs_completed_only(): void
    {
        [$tracker, $item] = $this->makeScenario();

        $this->save($tracker, $item, true, null)->assertOk();

        $this->assertSame(
            ["Sick Plan (Hydration): Completed? updated from No to Yes"
                . " by {$this->user->name}"],
            $this->notesFor($tracker)
        );
    }

    public function test_changing_only_notes_logs_set_to_then_updated_from(): void
    {
        [$tracker, $item] = $this->makeScenario();

        $this->save($tracker, $item, false, 'First')->assertOk();
        $this->save($tracker, $item, false, 'Second')->assertOk();

        $this->assertSame([
            "Sick Plan (Hydration): Notes set to 'First' by {$this->user->name}",
            "Sick Plan (Hydration): Notes updated from 'First' to 'Second' by {$this->user->name}",
        ], $this->notesFor($tracker));
    }

    public function test_changing_both_fields_logs_a_single_note(): void
    {
        [$tracker, $item] = $this->makeScenario(['notes' => 'Old']);

        $this->save($tracker, $item, true, 'New')->assertOk();

        $this->assertSame(
            ["Sick Plan (Hydration): Completed? updated from No to Yes"
                . "; Notes updated from 'Old' to 'New' by {$this->user->name}"],
            $this->notesFor($tracker)
        );
    }

    public function test_unchanged_save_writes_no_note(): void
    {
        [$tracker, $item] = $this->makeScenario(['notes' => 'Same']);

        $this->save($tracker, $item, false, 'Same')->assertOk();

        $this->assertSame([], $this->notesFor($tracker));
    }

    public function test_null_and_empty_notes_are_treated_as_equal(): void
    {
        [$tracker, $item] = $this->makeScenario(['notes' => '']);

        $this->save($tracker, $item, false, null)->assertOk();

        $this->assertSame([], $this->notesFor($tracker));
    }

    public function test_clearing_notes_renders_empty_marker(): void
    {
        [$tracker, $item] = $this->makeScenario(['notes' => 'Old']);

        $this->save($tracker, $item, false, null)->assertOk();

        $this->assertSame(
            ["Sick Plan (Hydration): Notes updated from 'Old' to (empty) by {$this->user->name}"],
            $this->notesFor($tracker)
        );
    }

    public function test_rejected_save_on_a_tracker_not_in_progress_writes_no_note_and_changes_nothing(): void
    {
        [$tracker, $item] = $this->makeScenario([], ['status' => HospitalizationTrackerStatusEnum::OPEN]);

        $this->save($tracker, $item, true, 'X')->assertStatus(409);

        $this->assertSame([], $this->notesFor($tracker));
        $this->assertFalse($item->refresh()->completed);
        $this->assertNull($item->notes);
    }

    public function test_failed_note_write_rolls_the_item_update_back(): void
    {
        [$tracker, $item] = $this->makeScenario();
        $this->mock(ActivityNotes::class, function ($mock) {
            $mock->shouldReceive('setActivityNotes')->andReturn(false);
        });

        $this->save($tracker, $item, true, 'X')->assertStatus(500);

        $this->assertFalse($item->refresh()->completed);
        $this->assertNull($item->notes);
    }

    public function test_ed_tracker_logs_the_same_way(): void
    {
        [$tracker, $item] = $this->makeScenario([], [], EncounterTypeEnum::ED);

        $this->save($tracker, $item, false, 'Hi')->assertOk();

        $this->assertSame(
            ["Sick Plan (Hydration): Notes set to 'Hi' by {$this->user->name}"],
            $this->notesFor($tracker)
        );
    }

    private function save(
        HospitalizationTrackerModel $tracker,
        SickPlanItemModel $item,
        bool $completed,
        ?string $notes
    ) {
        return $this->actingAs($this->user)->patchJson(
            route('hospitalization.trackers.sick-plan.items.update', [
                'trackerId' => $tracker->id,
                'itemId' => $item->id,
            ]),
            ['sickPlanType' => 'Hydration', 'completed' => $completed, 'notes' => $notes]
        );
    }

    /** @return list<string> */
    private function notesFor(HospitalizationTrackerModel $tracker): array
    {
        return DB::table('notes')
            ->where('entity', 'hospitalization_tracker')
            ->where('entity_id', $tracker->id)
            ->orderBy('id')
            ->pluck('notes')
            ->values()
            ->all();
    }

    /** @return array{0: HospitalizationTrackerModel, 1: SickPlanItemModel} */
    private function makeScenario(
        array $itemAttributes = [],
        array $trackerAttributes = [],
        EncounterTypeEnum $encounterType = EncounterTypeEnum::IP
    ): array {
        (new SickPlanCatalogFixture())->create(['domain' => 'Hydration']);
        $patient = (new VbcPatientScenario())->create();

        $encounter = EncounterModel::create([
            'patient_id' => $patient->id,
            'type' => $encounterType,
            'status' => EncounterStatusEnum::ADMITTED,
            'admission_at' => now(),
        ]);
        $tracker = HospitalizationTrackerModel::create(array_merge([
            'encounter_id' => $encounter->id,
            'status' => HospitalizationTrackerStatusEnum::IN_PROGRESS,
            'assigned_to_id' => $this->user->id,
        ], $trackerAttributes));

        $plan = app(SickPlanWriteRepository::class)->createBlankForPatient($patient->id, $this->user->id);
        /** @var SickPlanItemModel $item */
        $item = $plan->items()->whereHas('catalog', fn ($q) => $q->where('domain', 'Hydration'))->firstOrFail();
        $item->update($itemAttributes);

        return [$tracker, $item];
    }
}
