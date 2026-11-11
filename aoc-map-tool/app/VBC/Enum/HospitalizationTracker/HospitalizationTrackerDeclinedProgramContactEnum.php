<?php

namespace App\VBC\Enum\HospitalizationTracker;

/** Who reached out to the client about the program when they declined it. */
enum HospitalizationTrackerDeclinedProgramContactEnum: string
{
    case ICM_AND_TRIAGE_TEAM = 'ICM_AND_TRIAGE_TEAM';
    case ICM_TEAM = 'ICM_TEAM';
    case TRIAGE_TEAM = 'TRIAGE_TEAM';

    public function label(): string
    {
        return match ($this) {
            self::ICM_AND_TRIAGE_TEAM => 'ICM & Triage Team',
            self::ICM_TEAM => 'ICM Team',
            self::TRIAGE_TEAM => 'Triage Team',
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
