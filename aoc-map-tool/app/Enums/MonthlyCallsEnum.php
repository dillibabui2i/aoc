<?php

namespace App\Enums;

enum MonthlyCallsEnum: int
{
    case NOT_SELECTED = 0;
    case WEEKLY = 1;
    case BI_WEEKLY = 2;
    case MONTHLY = 3;
    case EVERY_OTHER_MONTH = 4;
    case QUARTERLY = 5;
    case NEVER = 6;

    public static function fromDb(null|int|string $value): self
    {
        if ($value === null || $value === '') {
            return self::NEVER;
        }

        $i = (int)$value;

        return match ($i) {
            0 => self::NOT_SELECTED,
            1 => self::WEEKLY,
            2 => self::BI_WEEKLY,
            3 => self::MONTHLY,
            4 => self::EVERY_OTHER_MONTH,
            5 => self::QUARTERLY,
            6 => self::NEVER,
            default => self::NOT_SELECTED,
        };
    }

    public function isNever(): bool
    {
        return $this === self::NEVER;
    }

    public function isInactive(): bool
    {
        return $this === self::NOT_SELECTED || $this === self::NEVER;
    }

    public function isWeeklyCadence(): bool
    {
        return $this === self::WEEKLY;
    }

    public function isBiWeeklyCadence(): bool
    {
        return $this === self::BI_WEEKLY;
    }

    public function isMonthlyCadence(): bool
    {
        return $this === self::MONTHLY;
    }

    public function isEveryOtherMonthCadence(): bool
    {
        return $this === self::EVERY_OTHER_MONTH;
    }

    public function isQuarterlyCadence(): bool
    {
        return $this === self::QUARTERLY;
    }

    public function label(): string
    {
        return match ($this) {
            self::NOT_SELECTED => 'Not selected',
            self::WEEKLY => 'Weekly',
            self::BI_WEEKLY => 'Bi-Weekly',
            self::MONTHLY => 'Monthly',
            self::EVERY_OTHER_MONTH => 'Every Other Month',
            self::QUARTERLY => 'Quarterly',
            self::NEVER => 'Never',
        };
    }

    public static function getFrequencyList(): array
    {
        return [
            self::NOT_SELECTED->value,
            self::WEEKLY->value,
            self::BI_WEEKLY->value,
            self::MONTHLY->value,
            self::EVERY_OTHER_MONTH->value,
            self::QUARTERLY->value,
            self::NEVER->value,
        ];
    }
}
