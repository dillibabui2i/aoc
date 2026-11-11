<?php

namespace App\VBC\Enum\HospitalizationTracker;

/** Whether the client was hospitalised again in the 30 days around this tracker. */
enum HospitalizationTrackerThirtyDayOutcomeEnum: string
{
    case CLIENT_HOSPITALIZATION_WITHIN_30_DAYS = 'CLIENT_HOSPITALIZATION_WITHIN_30_DAYS';
    case NO_HOSPITALIZATION_IN_PAST_30_DAYS = 'NO_HOSPITALIZATION_IN_PAST_30_DAYS';

    public function label(): string
    {
        return match ($this) {
            self::CLIENT_HOSPITALIZATION_WITHIN_30_DAYS => 'Client Hospitalization within 30 days',
            self::NO_HOSPITALIZATION_IN_PAST_30_DAYS => 'No hospitalization in past 30 days',
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
