<?php

declare(strict_types=1);

namespace Tests\Unit\VBC\Enum;

use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendedForEnum;
use Tests\UnitTestCase;

/**
 * @coversDefaultClass \App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendedForEnum
 */
class ImmunizationRecommendedForEnumTest extends UnitTestCase
{
    /**
     * @test
     * @covers ::list
     * @covers ::label
     */
    public function it_should_keep_the_value_to_label_contract(): void
    {
        $this->assertSame([
            'ALL_CHILDREN' => 'All Children',
            'HIGH_RISK_GROUPS' => 'High risk groups or some populations',
            'SHARED_CLINICAL_DECISION_MAKING' => 'Shared Clinical Decision-Making',
        ], ImmunizationRecommendedForEnum::list());
    }

    /**
     * @test
     * @covers ::fromLabel
     */
    public function it_should_map_a_stored_label_back_to_its_case(): void
    {
        $this->assertSame(
            ImmunizationRecommendedForEnum::HIGH_RISK_GROUPS,
            ImmunizationRecommendedForEnum::fromLabel('High risk groups or some populations')
        );
    }

    /**
     * @test
     * @covers ::fromLabel
     */
    public function it_should_not_map_an_unknown_or_missing_label(): void
    {
        $this->assertNull(ImmunizationRecommendedForEnum::fromLabel('Everyone'));
        $this->assertNull(ImmunizationRecommendedForEnum::fromLabel(null));
    }
}
