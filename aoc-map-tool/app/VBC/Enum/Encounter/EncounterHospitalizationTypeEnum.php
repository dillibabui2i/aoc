<?php

namespace App\VBC\Enum\Encounter;

enum EncounterHospitalizationTypeEnum: string
{
    case ADMISSION = 'ADMISSION';
    case READMISSION = 'READMISSION';

    public function label(): string
    {
        return match ($this) {
            self::ADMISSION => 'Admission',
            self::READMISSION => 'Readmission',
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
