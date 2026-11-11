<?php

namespace App\VBC\Enum\ImmunizationRecommendation;

enum ImmunizationRecommendationFrequencyEnum: string
{
    // Legacy value (seeded rows): no longer offered by the schedule modal, kept so existing rows still cast.
    case ONCE = 'ONCE';
    case ONE_PER_SERIES = 'ONE_PER_SERIES';
    case TWO_PER_SERIES = 'TWO_PER_SERIES';
    case THREE_PER_SERIES = 'THREE_PER_SERIES';
    case FOUR_PER_SERIES = 'FOUR_PER_SERIES';
    case YEARLY = 'YEARLY';

    public function label(): string
    {
        return match ($this) {
            self::ONCE => 'Once',
            self::ONE_PER_SERIES => 'Once per series',
            self::TWO_PER_SERIES => 'Up to 2 doses per series',
            self::THREE_PER_SERIES => 'Up to 3 doses per series',
            self::FOUR_PER_SERIES => 'Up to 4 doses per series',
            self::YEARLY => '1 dose annually',
        };
    }

    /** @return array<int, self> the values the Add/Edit Immunization Schedule modal offers */
    public static function scheduleCases(): array
    {
        return array_values(array_filter(self::cases(), fn (self $case) => $case !== self::ONCE));
    }

    public static function options(): array
    {
        return array_map(
            fn(self $case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            self::cases()
        );
    }

    public static function list(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $case) => [$case->value => $case->label()])
            ->toArray();
    }
}
