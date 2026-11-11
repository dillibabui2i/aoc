<?php

declare(strict_types=1);

namespace Tests\Unit\VBC\Rules;

use App\VBC\Rules\ValidDoseAgeTargets;
use Tests\UnitTestCase;

/**
 * Pure logic, no container or I/O, so Unit/bare. Start is inclusive and end is exclusive (0–3 and 3–6 months are
 * valid); ages are compared in months. The expected messages are pinned to literals — they are what the modal shows.
 *
 * @coversDefaultClass \App\VBC\Rules\ValidDoseAgeTargets
 */
class ValidDoseAgeTargetsTest extends UnitTestCase
{
    /**
     * @test
     * @covers ::passes
     * @covers ::checkPairs
     * @covers ::pairError
     * @covers ::pointError
     * @dataProvider validSetsProvider
     */
    public function it_should_accept_the_set(array $targets): void
    {
        $this->assertSame([], $this->errors($targets));
    }

    public function validSetsProvider(): array
    {
        return [
            'ranges sharing a boundary (0–3, 3–6)' => [[$this->range(0, 3), $this->range(3, 6)]],
            'a single value on the end of a range' => [[$this->single(3), $this->range(0, 3)]],
            'the last valid month as a start and as an end (22–23)' => [[$this->range(22, 23)]],
            'months 0, 1 and 23 as single values' => [[$this->single(0), $this->single(1), $this->single(23)]],
            'months and years are compared in months' => [[$this->range(6, 12), $this->range(2, 3, 'YEAR')]],
            'zero as a start value' => [[$this->single(0)]],
            'the end is ignored when to_range is off' => [[array_merge($this->single(2), ['to_age' => 1, 'to_age_unit' => 'MONTH'])]],
        ];
    }

    /**
     * @test
     * @covers ::passes
     * @covers ::message
     * @covers ::checkPairs
     * @covers ::pairError
     * @covers ::pointError
     * @dataProvider conflictingSetsProvider
     */
    public function it_should_reject_the_set_with_the_message(array $targets, string $message): void
    {
        $this->assertSame([$message], $this->errors($targets));
    }

    public function conflictingSetsProvider(): array
    {
        $contained = 'Dose targets #1 and #2 are not allowed: one range is contained in the other.';

        return [
            'overlap (0–3, 1–4)' => [[$this->range(0, 3), $this->range(1, 4)], 'Dose targets #1 and #2 overlap.'],
            'contained (1–7, 2–5)' => [[$this->range(1, 7), $this->range(2, 5)], $contained],
            'contained sharing the end (0–3, 1–3)' => [[$this->range(0, 3), $this->range(1, 3)], $contained],
            'duplicates' => [[$this->range(1, 6), $this->range(1, 6)], 'Dose targets #1 and #2 are duplicates.'],
            'a single value inside a range' => [[$this->single(2), $this->range(0, 3)], $contained],
            'a single value on the start of a range' => [[$this->single(3), $this->range(3, 6)], $contained],
        ];
    }

    /**
     * @test
     * @covers ::passes
     * @covers ::validateTarget
     * @covers ::unitRangeError
     * @covers ::finish
     * @dataProvider invalidRowsProvider
     */
    public function it_should_reject_the_row_with_the_message(array $targets, string $message): void
    {
        $this->assertSame([$message], $this->errors($targets));
    }

    public function invalidRowsProvider(): array
    {
        return [
            '24 months as a start' => [[$this->single(24)], 'Dose target #1: Start value in months must be 23 or lower.'],
            '25 months as a start' => [[$this->single(25)], 'Dose target #1: Start value in months must be 23 or lower.'],
            '24 months as an end' => [[$this->range(1, 24)], 'Dose target #1: End value in months must be 23 or lower.'],
            'years below two' => [[$this->single(1, 'YEAR')], 'Dose target #1: Start value in years must be 2 or higher.'],
            'zero as an end value' => [[$this->range(1, 0)], 'Dose target #1: End value must be greater than 0.'],
            'end not above the start' => [[$this->range(15, 10)], 'Dose target #1: End value must be greater than the start value.'],
            'units that do not match' => [
                [array_merge($this->range(10, 4), ['to_age_unit' => 'YEAR'])],
                'Dose target #1: Start and end units must match.',
            ],
            'a range without an end' => [
                [array_merge($this->range(1, 3), ['to_age' => null, 'to_age_unit' => null])],
                'Dose target #1: End value and end unit are required when the dose is a range.',
            ],
            'an invalid row is not compared with the others' => [
                [$this->range(1, 6), $this->single(1, 'YEAR')],
                'Dose target #2: Start value in years must be 2 or higher.',
            ],
        ];
    }

    /** @return array<int, string> */
    private function errors(array $targets): array
    {
        $rule = new ValidDoseAgeTargets();

        return $rule->passes('dose_age_targets', $targets) ? [] : $rule->message();
    }

    private function single(int $age, string $unit = 'MONTH'): array
    {
        return [
            'from_age' => $age,
            'from_age_unit' => $unit,
            'to_range' => false,
            'to_age' => null,
            'to_age_unit' => null,
        ];
    }

    private function range(int $from, int $to, string $unit = 'MONTH'): array
    {
        return [
            'from_age' => $from,
            'from_age_unit' => $unit,
            'to_range' => true,
            'to_age' => $to,
            'to_age_unit' => $unit,
        ];
    }
}
