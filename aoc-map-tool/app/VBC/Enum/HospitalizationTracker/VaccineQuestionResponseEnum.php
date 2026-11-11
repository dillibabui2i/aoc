<?php

namespace App\VBC\Enum\HospitalizationTracker;

/**
 * The three-way answer to the ED Visit Record's "Annual flu vaccine? / COVID-19 vaccine? / RSV
 * vaccine?" questions (PD-3983). Distinct from VaccineDoseStatusEnum (App\VBC\Enum\VaccineDose), which
 * models a client's actual dose administration record elsewhere in the app — these three questions are
 * a simple self-reported Yes/No/Unable to assess on the tracker itself, not a dose history lookup.
 */
enum VaccineQuestionResponseEnum: string
{
    case YES = 'YES';
    case NO = 'NO';
    case UNABLE_TO_ASSESS = 'UNABLE_TO_ASSESS';

    public function label(): string
    {
        return match ($this) {
            self::YES => 'Yes',
            self::NO => 'No',
            self::UNABLE_TO_ASSESS => 'Unable to assess',
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
