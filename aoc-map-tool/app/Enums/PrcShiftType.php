<?php

namespace App\Enums;

enum PrcShiftType: string
{
    case Training = 'training';
    case Coverage = 'coverage';
    case CCCRequest = 'ccc_request';

    public function label(): string
    {
        return match ($this) {
            self::Training => 'Training',
            self::Coverage => 'Coverage',
            self::CCCRequest => 'CCC Request',
        };
    }

    /**
     * @param array<mixed> $values Raw request values; unknown/empty entries are dropped.
     * @return array<self>
     */
    public static function fromValues(array $values): array
    {
        return array_values(array_filter(array_map(
            fn ($value) => self::tryFrom((string) $value),
            array_filter($values)
        )));
    }
}
