<?php

namespace Tests\Feature\VBC\Http\Controllers\Common;

use App\User;
use App\VBC\Http\Middleware\EnsurePermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\Fixtures\CdcVaccinesFixture;
use Tests\Support\Fixtures\UserFixture;
use Tests\TestCase;

/**
 * GET /vbc/filters — the `bucket` param narrows `hospitalizationStatuses` per page (PD-5771) and the
 * IMMUNIZATION_SCHEDULE bucket returns the option lists of the Add/Edit Immunization Schedule modal.
 *
 * @coversDefaultClass \App\VBC\Http\Controllers\Common\FiltersController
 */
class FiltersControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsurePermission::class);
        $this->user = User::query()->findOrFail((new UserFixture())->create()->id);
    }

    public function test_ed_visits_bucket_returns_only_the_ed_visits_statuses(): void
    {
        $user = User::query()->findOrFail((new UserFixture())->create()->id);

        $response = $this->actingAs($user)->getJson(route('filters.index', ['bucket' => 'ED_VISITS']));

        $response->assertOk();
        $this->assertSame(
            ['IN_PROGRESS', 'READY_FOR_REVIEW', 'READY_FOR_NEXT_STEPS'],
            array_column($response->json('hospitalizationStatuses'), 'value')
        );
    }

    public function test_unknown_bucket_is_rejected(): void
    {
        $user = User::query()->findOrFail((new UserFixture())->create()->id);

        $this->actingAs($user)->getJson(route('filters.index', ['bucket' => 'NOPE']))->assertStatus(422);
    }

    /**
     * @test
     * @covers ::index
     */
    public function it_should_return_the_schedule_modal_option_lists(): void
    {
        $response = $this->scheduleOptions();

        $response->assertOk()->assertJsonStructure([
            'states', 'vaccineCategories', 'vaccines', 'recommendedFor', 'frequencies', 'doseAgeUnits',
        ]);
        $this->assertSame([
            ['value' => 'ALL_CHILDREN', 'label' => 'All Children'],
            ['value' => 'HIGH_RISK_GROUPS', 'label' => 'High risk groups or some populations'],
            ['value' => 'SHARED_CLINICAL_DECISION_MAKING', 'label' => 'Shared Clinical Decision-Making'],
        ], $response->json('recommendedFor'));
        $this->assertSame([
            ['value' => 'ONE_PER_SERIES', 'label' => 'Once per series'],
            ['value' => 'TWO_PER_SERIES', 'label' => 'Up to 2 doses per series'],
            ['value' => 'THREE_PER_SERIES', 'label' => 'Up to 3 doses per series'],
            ['value' => 'FOUR_PER_SERIES', 'label' => 'Up to 4 doses per series'],
            ['value' => 'YEARLY', 'label' => '1 dose annually'],
        ], $response->json('frequencies'));
        $this->assertSame([
            ['value' => 'MONTH', 'label' => 'Month(s)'],
            ['value' => 'YEAR', 'label' => 'Years'],
        ], $response->json('doseAgeUnits'));
    }

    /**
     * @test
     * @covers ::index
     */
    public function it_should_offer_every_us_state_by_its_abbreviation(): void
    {
        $states = $this->scheduleOptions()->json('states');

        $this->assertCount(51, $states);
        $this->assertSame(['value' => 'AL', 'text' => 'Alabama'], $states[0]);
        $this->assertContains(['value' => 'TX', 'text' => 'Texas'], $states);
        $this->assertContains(['value' => 'DC', 'text' => 'District Of Columbia'], $states);
    }

    /**
     * @test
     * @covers ::index
     */
    public function it_should_offer_only_vaccines_that_belong_to_a_category(): void
    {
        $withCategory = (new CdcVaccinesFixture())->create(['cdc_full_name' => 'Vaccine With Category']);
        $withoutCategory = (new CdcVaccinesFixture())->create(['cdc_full_name' => 'Vaccine Without Category', 'cvx_group_code' => null]);

        $response = $this->scheduleOptions();

        $this->assertContains([
            'value' => $withCategory->cvx_code,
            'text' => 'Vaccine With Category',
            'category' => $withCategory->cvx_group_code,
        ], $response->json('vaccines'));
        $this->assertNotContains($withoutCategory->cvx_code, array_column($response->json('vaccines'), 'value'));
        $this->assertContains(
            ['value' => $withCategory->cvx_group_code, 'text' => 'Test Category'],
            $response->json('vaccineCategories')
        );
    }

    private function scheduleOptions()
    {
        return $this->actingAs($this->user)->getJson(route('filters.index', ['bucket' => 'IMMUNIZATION_SCHEDULE']));
    }
}
