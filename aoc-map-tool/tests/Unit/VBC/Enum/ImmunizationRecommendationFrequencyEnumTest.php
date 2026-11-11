<?php

declare(strict_types=1);

namespace Tests\Unit\VBC\Enum;

use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendationFrequencyEnum;
use Tests\UnitTestCase;

/**
 * @coversDefaultClass \App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendationFrequencyEnum
 */
class ImmunizationRecommendationFrequencyEnumTest extends UnitTestCase
{
    /**
     * @test
     * @covers ::scheduleCases
     */
    public function it_should_offer_the_schedule_modal_every_frequency_except_the_legacy_once(): void
    {
        $this->assertSame(
            ['ONE_PER_SERIES', 'TWO_PER_SERIES', 'THREE_PER_SERIES', 'FOUR_PER_SERIES', 'YEARLY'],
            array_map(fn ($case) => $case->value, ImmunizationRecommendationFrequencyEnum::scheduleCases())
        );
    }

    /**
     * @test
     * @covers ::label
     */
    public function it_should_keep_the_business_labels(): void
    {
        $this->assertSame([
            'Once per series',
            'Up to 2 doses per series',
            'Up to 3 doses per series',
            'Up to 4 doses per series',
            '1 dose annually',
        ], array_map(fn ($case) => $case->label(), ImmunizationRecommendationFrequencyEnum::scheduleCases()));
    }

    /**
     * @test
     * @coversNothing
     */
    public function it_should_still_cast_the_legacy_once_so_old_rows_load(): void
    {
        $this->assertSame(ImmunizationRecommendationFrequencyEnum::ONCE, ImmunizationRecommendationFrequencyEnum::from('ONCE'));
    }
}
