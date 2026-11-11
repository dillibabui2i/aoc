<?php

namespace App\VBC\Enum\Encounter;

enum EncounterAdmissionTypeEnum: string
{
    case AVOIDABLE = 'AVOIDABLE';
    case UNAVOIDABLE = 'UNAVOIDABLE';
    case PLANNED = 'PLANNED';

    public function label(): string
    {
        return match ($this) {
            self::AVOIDABLE => 'Avoidable',
            self::UNAVOIDABLE => 'Unavoidable',
            self::PLANNED => 'Planned',
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
