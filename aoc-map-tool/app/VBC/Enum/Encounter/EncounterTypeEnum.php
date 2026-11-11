<?php

namespace App\VBC\Enum\Encounter;

enum EncounterTypeEnum: string
{
    case IP = 'IP';
    case ED = 'ED';

    public function label(): string
    {
        return match ($this) {
            self::IP => 'IP',
            self::ED => 'ED',
        };
    }

    /** What the encounter is called as an entry of the patient's history ("Hospitalization History" tab). */
    public function eventLabel(): string
    {
        return match ($this) {
            self::IP => 'Hospitalization',
            self::ED => 'ED Visit',
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
