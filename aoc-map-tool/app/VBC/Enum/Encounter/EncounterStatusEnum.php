<?php

namespace App\VBC\Enum\Encounter;

enum EncounterStatusEnum: string
{
    case ADMITTED = 'ADMITTED';
    case TRANSFERRED = 'TRANSFERRED';
    case DISCHARGED = 'DISCHARGED';

    public function label(): string
    {
        return match ($this) {
            self::ADMITTED => 'Admitted',
            self::TRANSFERRED => 'Transferred',
            self::DISCHARGED => 'Discharged',
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
