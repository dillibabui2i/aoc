<?php

namespace App\VBC\Enum\HospitalizationTracker;

enum HospitalizationTrackerIcmLevelEnum: string
{
    case LEVEL_I = 'LEVEL_I';
    case LEVEL_II = 'LEVEL_II';
    case LEVEL_III = 'LEVEL_III';
    case LEVEL_I_III = 'LEVEL_I_III';
    case LEVEL_II_III = 'LEVEL_II_III';

    public function label(): string
    {
        return match ($this) {
            self::LEVEL_I => 'Level I',
            self::LEVEL_II => 'Level II',
            self::LEVEL_III => 'Level III',
            self::LEVEL_I_III => 'Level I/III',
            self::LEVEL_II_III => 'Level II/III',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            self::cases()
        );
    }

    public static function list(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->toArray();
    }
}
