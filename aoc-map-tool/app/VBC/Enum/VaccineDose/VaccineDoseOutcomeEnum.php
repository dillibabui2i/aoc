<?php

namespace App\VBC\Enum\VaccineDose;

/**
 * Display status of a vaccine dose in the Immunizations dashboard bucket. Not the outcome stored in the DB:
 * it is derived from the dose's earliest / due date relative to today.
 */
enum VaccineDoseOutcomeEnum: string
{
    /** A dose whose earliest date is within this many days from today is "Upcoming". */
    public const UPCOMING_WINDOW_DAYS = 60;

    case OVERDUE = 'OVERDUE';
    case MISSING = 'MISSING';
    case UPCOMING = 'UPCOMING';
    case PLANNED = 'PLANNED';

    public function label(): string
    {
        return match ($this) {
            self::OVERDUE => 'Overdue',
            self::MISSING => 'Missing',
            self::UPCOMING => 'Upcoming',
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
