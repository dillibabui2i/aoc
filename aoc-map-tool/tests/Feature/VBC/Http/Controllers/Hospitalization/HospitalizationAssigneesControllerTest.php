<?php

namespace Tests\Feature\VBC\Http\Controllers\Hospitalization;

use App\User;
use App\VBC\Http\Middleware\EnsurePermission;
use App\VBC\Repositories\Hospitalization\HospitalizationReadRepository;
use Illuminate\Database\Eloquent\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * "Assigned To" options endpoint: the ICM list by default (Hospitalization tracker), the
 * "Manage ED Visits" list for encounter_type=ED (ED Visit tracker / ED Care Coordination).
 * The repository is mocked, so no database is needed.
 */
class HospitalizationAssigneesControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsurePermission::class);
        $this->actingAs(new User());
    }

    public function test_it_returns_the_icm_list_when_encounter_type_is_omitted(): void
    {
        $this->mockRepository('getIcmAssigneeOptions', 'getEdAssigneeOptions', ['id' => 5, 'name' => 'Icm User']);

        $this->getJson(route('hospitalization.assignees.index'))
            ->assertOk()
            ->assertExactJson([['value' => 5, 'label' => 'Icm User']]);
    }

    public function test_it_returns_the_icm_list_for_ip(): void
    {
        $this->mockRepository('getIcmAssigneeOptions', 'getEdAssigneeOptions', ['id' => 5, 'name' => 'Icm User']);

        $this->getJson(route('hospitalization.assignees.index', ['encounter_type' => 'IP']))
            ->assertOk()
            ->assertExactJson([['value' => 5, 'label' => 'Icm User']]);
    }

    public function test_it_returns_the_manage_ed_visits_list_for_ed(): void
    {
        $this->mockRepository('getEdAssigneeOptions', 'getIcmAssigneeOptions', ['id' => 7, 'name' => 'Ed User']);

        $this->getJson(route('hospitalization.assignees.index', ['encounter_type' => 'ED']))
            ->assertOk()
            ->assertExactJson([['value' => 7, 'label' => 'Ed User']]);
    }

    public function test_it_rejects_an_unknown_encounter_type(): void
    {
        $this->getJson(route('hospitalization.assignees.index', ['encounter_type' => 'XX']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('encounter_type');
    }

    private function mockRepository(string $expected, string $notExpected, array $user): void
    {
        $this->mock(HospitalizationReadRepository::class, function (MockInterface $mock) use ($expected, $notExpected, $user) {
            $mock->shouldReceive($expected)->once()->andReturn(new Collection([(new User())->forceFill($user)]));
            $mock->shouldNotReceive($notExpected);
        });
    }
}
