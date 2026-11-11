<?php

namespace App\VBC\Enum\HospitalizationTracker;

enum HospitalizationTrackerClosingReasonEnum: string
{
    case COMPLETED = 'COMPLETED';
    case ED_VISIT = 'ED_VISIT';
    case TRANSFERRED = 'TRANSFERRED';
    case OPENED_BY_MISTAKE = 'OPENED_BY_MISTAKE';
    case DUPLICATE = 'DUPLICATE';
    case OTHER = 'OTHER';
    // ED Visit's own Close reason (PD-4069 AC12) — a plain label, no cross-linking to an actual
    // Hospitalization tracker.
    case HOSPITALIZATION = 'HOSPITALIZATION';

    public function label(): string
    {
        return match ($this) {
            self::COMPLETED => 'Completed',
            self::ED_VISIT => 'ED Visit',
            self::TRANSFERRED => 'Transferred',
            self::OPENED_BY_MISTAKE => 'Opened by Mistake',
            self::DUPLICATE => 'Duplicate',
            self::OTHER => 'Other',
            self::HOSPITALIZATION => 'Hospitalization',
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
