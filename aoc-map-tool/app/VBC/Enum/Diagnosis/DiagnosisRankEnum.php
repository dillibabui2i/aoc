<?php

namespace App\VBC\Enum\Diagnosis;

enum DiagnosisRankEnum: string
{
    case PRIMARY = 'PRIMARY';
    case SECONDARY = 'SECONDARY';

    public function label(): string
    {
        return match ($this) {
            self::PRIMARY => 'Primary',
            self::SECONDARY => 'Secondary',
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
