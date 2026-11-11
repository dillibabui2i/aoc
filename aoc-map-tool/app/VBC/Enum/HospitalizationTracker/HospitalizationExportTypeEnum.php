<?php

namespace App\VBC\Enum\HospitalizationTracker;

/** Values shown in the "Export Type" dropdown on All Hospitalizations. Display-only for now — see PD-4611 for the actual export/filter implementation. */
enum HospitalizationExportTypeEnum: string
{
    case HOSPITALIZATIONS = 'HOSPITALIZATIONS';
    case HIGH_UTILIZERS = 'HIGH_UTILIZERS';

    public function label(): string
    {
        return match ($this) {
            self::HOSPITALIZATIONS => 'Hospitalizations',
            self::HIGH_UTILIZERS => 'High Utilizers',
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
