<?php

namespace App\VBC\Enum\ImmunizationDoseAge;

enum ImmunizationDoseAgeUnitEnum: string
{
    case MONTH = 'MONTH';
    case YEAR = 'YEAR';

    public function label(): string
    {
        return match ($this) {
            self::MONTH => 'Month',
            self::YEAR => 'Year',
        };
    }

    /** Common unit for comparing ages given in different units. */
    public function toMonths(int $age): int
    {
        return match ($this) {
            self::MONTH => $age,
            self::YEAR => $age * 12,
        };
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
