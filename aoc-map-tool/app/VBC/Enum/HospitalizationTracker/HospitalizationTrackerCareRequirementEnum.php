<?php

namespace App\VBC\Enum\HospitalizationTracker;

/** What the client's care involves. A tracker can carry any number of these, hence the collection cast. */
enum HospitalizationTrackerCareRequirementEnum: string
{
    case GT_GB = 'GT_GB';
    case NEBULIZER = 'NEBULIZER';
    case SUCTION = 'SUCTION';
    case TRACH_AND_VENT = 'TRACH_AND_VENT';
    case VENT = 'VENT';
    case WOUND_CARE = 'WOUND_CARE';
    case ADLS = 'ADLS';
    case MEDICATION_ADMINISTRATION = 'MEDICATION_ADMINISTRATION';

    public function label(): string
    {
        return match ($this) {
            self::GT_GB => 'GT/GB',
            self::NEBULIZER => 'Nebulizer',
            self::SUCTION => 'Suction',
            self::TRACH_AND_VENT => 'Trach & Vent',
            self::VENT => 'Vent',
            self::WOUND_CARE => 'Wound Care',
            self::ADLS => 'ADLs',
            self::MEDICATION_ADMINISTRATION => 'Medication Administration',
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
