<?php

namespace Tests\Integration\VBC\Repositories\Immunization;

use App\VBC\DTO\Immunization\ImmunizationScheduleDTO;
use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendationFrequencyEnum;
use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendationStatusEnum;
use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendedForEnum;
use App\VBC\Repositories\Immunization\ImmunizationScheduleWriteRepository;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\Fixtures\CdcVaccinesFixture;
use Tests\TestCase;

/**
 * @coversDefaultClass \App\VBC\Repositories\Immunization\ImmunizationScheduleWriteRepository
 */
class ImmunizationScheduleWriteRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private ImmunizationScheduleWriteRepository $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = $this->app->make(ImmunizationScheduleWriteRepository::class);
    }

    /**
     * @test
     * @covers ::create
     */
    public function it_should_let_the_database_refuse_a_second_schedule_for_the_same_vaccine_and_state(): void
    {
        $cvx = (new CdcVaccinesFixture())->create()->cvx_code;
        $this->sut->create($this->dto($cvx, 'TX'));

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('uq_imm_recommendations_vaccine_state');

        $this->sut->create($this->dto($cvx, 'TX'));
    }

    /**
     * @test
     * @covers ::create
     */
    public function it_should_allow_the_same_vaccine_in_another_state(): void
    {
        $cvx = (new CdcVaccinesFixture())->create()->cvx_code;

        $first = $this->sut->create($this->dto($cvx, 'TX'));
        $second = $this->sut->create($this->dto($cvx, 'AZ'));

        $this->assertNotSame($first->id, $second->id);
    }

    private function dto(int $cvx, string $state): ImmunizationScheduleDTO
    {
        return new ImmunizationScheduleDTO(
            vaccineCvxCode: $cvx,
            state: $state,
            recommendedFor: ImmunizationRecommendedForEnum::ALL_CHILDREN,
            frequency: ImmunizationRecommendationFrequencyEnum::ONE_PER_SERIES,
            status: ImmunizationRecommendationStatusEnum::ACTIVE,
            doseAgeTargets: [],
        );
    }
}
