<?php

namespace App\VBC\Enum\Diagnosis;

enum DiagnosisVerificationStatusEnum: string
{
    case UNCONFIRMED = 'UNCONFIRMED';
    case PROVISIONAL = 'PROVISIONAL';
    case DIFFERENTIAL = 'DIFFERENTIAL';
    case CONFIRMED = 'CONFIRMED';
    case REFUTED = 'REFUTED';
    case ENTERED_IN_ERROR = 'ENTERED_IN_ERROR';

    public function label(): string
    {
        return match ($this) {
            self::UNCONFIRMED => 'Unconfirmed / Suspected',
            self::PROVISIONAL => 'Provisional',
            self::DIFFERENTIAL => 'Differential',
            self::CONFIRMED => 'Confirmed',
            self::REFUTED => 'Refuted',
            self::ENTERED_IN_ERROR => 'Entered in Error',
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
