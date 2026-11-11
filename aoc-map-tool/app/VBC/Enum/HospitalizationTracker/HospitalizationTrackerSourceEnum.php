<?php

namespace App\VBC\Enum\HospitalizationTracker;

enum HospitalizationTrackerSourceEnum: string
{
    case POINT_CLICK_CARE = 'POINT_CLICK_CARE';
    case BAMBOO_HEALTH = 'BAMBOO_HEALTH';
    case INFORMED_BY_CONTACT = 'INFORMED_BY_CONTACT';

    public function label(): string
    {
        return match ($this) {
            self::POINT_CLICK_CARE => 'PointClickCare',
            self::BAMBOO_HEALTH => 'Bamboo Health',
            self::INFORMED_BY_CONTACT => 'Informed by Contact',
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
