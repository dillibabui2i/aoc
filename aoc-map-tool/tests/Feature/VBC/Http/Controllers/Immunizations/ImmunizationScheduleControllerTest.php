<?php

namespace Tests\Feature\VBC\Http\Controllers\Immunizations;

use App\User;
use App\VBC\Http\Middleware\EnsurePermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\Fixtures\CdcVaccinesFixture;
use Tests\Support\Fixtures\UserFixture;
use Tests\TestCase;

/**
 * Create / show / update (incl. deactivate and reactivate) of an immunization schedule template.
 * Validation is asserted here, through the endpoints, since Requests have no tests of their own.
 *
 * @coversDefaultClass \App\VBC\Http\Controllers\Immunizations\ImmunizationScheduleController
 */
class ImmunizationScheduleControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private object $vaccine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsurePermission::class);
        $this->user = User::query()->findOrFail((new UserFixture())->create()->id);
        $this->vaccine = (new CdcVaccinesFixture())->create();
    }

    /**
     * @test
     * @covers ::store
     */
    public function it_should_create_a_schedule_with_its_dose_targets_and_recommended_for(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('immunization.schedule.store'), $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.vaccine_cvx_code', $this->vaccine->cvx_code)
            ->assertJsonPath('data.cvx_group_code', $this->vaccine->cvx_group_code)
            ->assertJsonPath('data.state', 'TX')
            ->assertJsonPath('data.recommended_for', 'ALL_CHILDREN')
            ->assertJsonPath('data.frequency', 'TWO_PER_SERIES')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.dose_age_targets', [
                ['from_age' => 2, 'from_age_unit' => 'MONTH', 'to_range' => false, 'to_age' => null, 'to_age_unit' => null],
                ['from_age' => 4, 'from_age_unit' => 'MONTH', 'to_range' => true, 'to_age' => 6, 'to_age_unit' => 'MONTH'],
            ]);

        $id = $response->json('data.id');
        $this->assertDatabaseHas('immunization_recommendations', [
            'id' => $id,
            'vaccine_cvx_code' => $this->vaccine->cvx_code,
            'originating_state' => 'TX',
            'frequency' => 'TWO_PER_SERIES',
            'status' => 'ACTIVE',
        ]);
        $this->assertSame(2, DB::table('immunization_dose_ages')->where('recommendation_id', $id)->count());
        $this->assertDatabaseHas('immunization_recommended_for', [
            'recommendation_id' => $id,
            'display_name' => 'All Children',
        ]);
    }

    /**
     * @test
     * @covers ::store
     */
    public function it_should_store_an_inactive_schedule_when_active_is_off(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('immunization.schedule.store'), $this->payload(['is_active' => false]))
            ->assertCreated()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('immunization_recommendations', [
            'vaccine_cvx_code' => $this->vaccine->cvx_code,
            'status' => 'INACTIVE',
        ]);
    }

    /**
     * @test
     * @covers ::store
     */
    public function it_should_require_every_field_and_at_least_one_dose_target(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('immunization.schedule.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'vaccine_cvx_code', 'state', 'recommended_for', 'frequency', 'is_active', 'dose_age_targets',
            ]);

        $this->actingAs($this->user)
            ->postJson(route('immunization.schedule.store'), $this->payload(['dose_age_targets' => []]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dose_age_targets']);
    }

    /**
     * @test
     * @covers ::store
     */
    public function it_should_reject_values_the_modal_does_not_offer(): void
    {
        $withoutCategory = (new CdcVaccinesFixture())->create(['cvx_group_code' => null]);

        $this->actingAs($this->user)
            ->postJson(route('immunization.schedule.store'), $this->payload([
                'state' => 'ZZ',
                'frequency' => 'ONCE',
                'recommended_for' => 'EVERYONE',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['state', 'frequency', 'recommended_for']);

        $this->actingAs($this->user)
            ->postJson(route('immunization.schedule.store'), $this->payload(['vaccine_cvx_code' => $withoutCategory->cvx_code]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['vaccine_cvx_code']);
    }

    /**
     * @test
     * @covers ::store
     */
    public function it_should_reject_a_second_schedule_for_the_same_vaccine_and_state_even_when_the_first_is_inactive(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('immunization.schedule.store'), $this->payload(['is_active' => false]))
            ->assertCreated();

        $this->actingAs($this->user)
            ->postJson(route('immunization.schedule.store'), $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'vaccine_cvx_code' => 'An immunization schedule for this vaccine and state already exists.',
            ]);

        $this->actingAs($this->user)
            ->postJson(route('immunization.schedule.store'), $this->payload(['state' => 'AZ']))
            ->assertCreated();
    }

    /**
     * @test
     * @covers ::store
     */
    public function it_should_accept_ranges_that_share_a_boundary_and_reject_overlapping_ones(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('immunization.schedule.store'), $this->payload(['dose_age_targets' => [
                $this->range(0, 3),
                $this->range(3, 6),
            ]]))
            ->assertCreated();

        $this->actingAs($this->user)
            ->postJson(route('immunization.schedule.store'), $this->payload([
                'state' => 'AZ',
                'dose_age_targets' => [$this->range(0, 3), $this->range(1, 4)],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dose_age_targets' => 'Dose targets #1 and #2 overlap.']);
    }

    /**
     * @test
     * @covers ::store
     */
    public function it_should_reject_months_above_23_and_accept_23(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('immunization.schedule.store'), $this->payload(['dose_age_targets' => [$this->range(24, 64)]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dose_age_targets' => 'Dose target #1: Start value in months must be 23 or lower.']);

        $this->actingAs($this->user)
            ->postJson(route('immunization.schedule.store'), $this->payload(['dose_age_targets' => [$this->range(0, 23)]]))
            ->assertCreated();
    }

    /**
     * @test
     * @covers ::show
     */
    public function it_should_return_the_raw_values_the_edit_modal_needs(): void
    {
        $id = $this->createSchedule();

        $this->actingAs($this->user)
            ->getJson(route('immunization.schedule.show', ['scheduleId' => $id]))
            ->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.cvx_group_code', $this->vaccine->cvx_group_code)
            ->assertJsonPath('data.vaccine_cvx_code', $this->vaccine->cvx_code)
            ->assertJsonPath('data.state', 'TX')
            ->assertJsonPath('data.recommended_for', 'ALL_CHILDREN')
            ->assertJsonPath('data.frequency', 'TWO_PER_SERIES')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonCount(2, 'data.dose_age_targets');
    }

    /**
     * @test
     * @covers ::show
     */
    public function it_should_open_a_schedule_whose_vaccine_was_soft_deleted_without_a_category(): void
    {
        $id = $this->createSchedule();
        DB::table('cdc_vaccines')->where('cvx_code', $this->vaccine->cvx_code)->update(['deleted_at' => now()]);

        $this->actingAs($this->user)
            ->getJson(route('immunization.schedule.show', ['scheduleId' => $id]))
            ->assertOk()
            ->assertJsonPath('data.cvx_group_code', null);
    }

    /**
     * @test
     * @covers ::show
     * @covers ::update
     */
    public function it_should_answer_not_found_for_an_unknown_schedule(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('immunization.schedule.show', ['scheduleId' => 999999999]))
            ->assertNotFound();

        $this->actingAs($this->user)
            ->putJson(route('immunization.schedule.update', ['scheduleId' => 999999999]), $this->payload())
            ->assertNotFound();
    }

    /**
     * @test
     * @covers ::update
     */
    public function it_should_replace_dose_targets_frequency_and_recommended_for_on_update(): void
    {
        $id = $this->createSchedule();

        $this->actingAs($this->user)
            ->putJson(route('immunization.schedule.update', ['scheduleId' => $id]), $this->payload([
                'frequency' => 'YEARLY',
                'recommended_for' => 'SHARED_CLINICAL_DECISION_MAKING',
                'dose_age_targets' => [$this->range(7, 9)],
            ]))
            ->assertOk()
            ->assertJsonPath('data.frequency', 'YEARLY')
            ->assertJsonPath('data.recommended_for', 'SHARED_CLINICAL_DECISION_MAKING')
            ->assertJsonPath('data.dose_age_targets', [
                ['from_age' => 7, 'from_age_unit' => 'MONTH', 'to_range' => true, 'to_age' => 9, 'to_age_unit' => 'MONTH'],
            ]);

        $this->assertSame(1, DB::table('immunization_dose_ages')->where('recommendation_id', $id)->whereNull('deleted_at')->count());
        $this->assertSame(2, DB::table('immunization_dose_ages')->where('recommendation_id', $id)->whereNotNull('deleted_at')->count());
        $this->assertSame(
            ['Shared Clinical Decision-Making'],
            DB::table('immunization_recommended_for')->where('recommendation_id', $id)->whereNull('deleted_at')->pluck('display_name')->all()
        );
    }

    /**
     * @test
     * @covers ::update
     */
    public function it_should_deactivate_and_reactivate_a_schedule_without_deleting_it(): void
    {
        $id = $this->createSchedule();

        $this->actingAs($this->user)
            ->putJson(route('immunization.schedule.update', ['scheduleId' => $id]), $this->payload(['is_active' => false]))
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
        $this->assertDatabaseHas('immunization_recommendations', ['id' => $id, 'status' => 'INACTIVE', 'deleted_at' => null]);

        $this->actingAs($this->user)
            ->putJson(route('immunization.schedule.update', ['scheduleId' => $id]), $this->payload(['is_active' => true]))
            ->assertOk()
            ->assertJsonPath('data.is_active', true);
        $this->assertDatabaseHas('immunization_recommendations', ['id' => $id, 'status' => 'ACTIVE']);
    }

    /**
     * @test
     * @covers ::update
     */
    public function it_should_not_count_the_schedule_itself_as_a_duplicate_but_reject_another_ones_vaccine_and_state(): void
    {
        $id = $this->createSchedule();
        $otherId = $this->createSchedule(['state' => 'AZ']);

        $this->actingAs($this->user)
            ->putJson(route('immunization.schedule.update', ['scheduleId' => $id]), $this->payload(['frequency' => 'YEARLY']))
            ->assertOk();

        $this->actingAs($this->user)
            ->putJson(route('immunization.schedule.update', ['scheduleId' => $otherId]), $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'vaccine_cvx_code' => 'An immunization schedule for this vaccine and state already exists.',
            ]);
    }

    private function createSchedule(array $overrides = []): int
    {
        return $this->actingAs($this->user)
            ->postJson(route('immunization.schedule.store'), $this->payload($overrides))
            ->assertCreated()
            ->json('data.id');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'vaccine_cvx_code' => $this->vaccine->cvx_code,
            'state' => 'TX',
            'recommended_for' => 'ALL_CHILDREN',
            'frequency' => 'TWO_PER_SERIES',
            'is_active' => true,
            'dose_age_targets' => [
                ['from_age' => 2, 'from_age_unit' => 'MONTH', 'to_range' => false, 'to_age' => null, 'to_age_unit' => null],
                $this->range(4, 6),
            ],
        ], $overrides);
    }

    private function range(int $from, int $to): array
    {
        return ['from_age' => $from, 'from_age_unit' => 'MONTH', 'to_range' => true, 'to_age' => $to, 'to_age_unit' => 'MONTH'];
    }
}
