<?php

declare(strict_types=1);

namespace Tests\Unit\VBC\Enum;

use App\VBC\Enum\ImmunizationDoseAge\ImmunizationDoseAgeUnitEnum;
use Tests\UnitTestCase;

/**
 * @coversDefaultClass \App\VBC\Enum\ImmunizationDoseAge\ImmunizationDoseAgeUnitEnum
 */
class ImmunizationDoseAgeUnitEnumTest extends UnitTestCase
{
    /**
     * @test
     * @covers ::toMonths
     */
    public function it_should_convert_an_age_to_months(): void
    {
        $this->assertSame(5, ImmunizationDoseAgeUnitEnum::MONTH->toMonths(5));
        $this->assertSame(24, ImmunizationDoseAgeUnitEnum::YEAR->toMonths(2));
        $this->assertSame(0, ImmunizationDoseAgeUnitEnum::YEAR->toMonths(0));
    }
}
