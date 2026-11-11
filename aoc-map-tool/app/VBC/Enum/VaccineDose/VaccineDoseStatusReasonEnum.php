<?php

namespace App\VBC\Enum\VaccineDose;

enum VaccineDoseStatusReasonEnum: string
{
    case IMMUNE = 'IMMUNE';
    case MEDPRECM = 'MEDPRECM';
    case OSTOCK = 'OSTOCK';
    case PATOBJ = 'PATOBJ';
    case PHILISOP = 'PHILISOP';
    case RELIG = 'RELIG';
    case VACEF = 'VACEF';
    case VACSAF = 'VACSAF';
    case EIE = 'EIE';
    case ACCLOGBAR = '_ACCLOGBAR';
    case DEFDEL = '_DEFDEL';
    case OTHER = '_OTHER';

    public function label(): string
    {
        return match ($this) {
            self::IMMUNE => 'Immune',
            self::MEDPRECM => 'Medical precaution',
            self::OSTOCK => 'Out of stock',
            self::PATOBJ => 'Patient objection',
            self::PHILISOP => 'Philosophical objection',
            self::RELIG => 'Religious objection',
            self::VACEF => 'Vaccine efficacy concerns',
            self::VACSAF => 'Vaccine safety concerns',
            self::EIE => 'EIE',
            self::ACCLOGBAR => 'Access / logistical barrier',
            self::DEFDEL => 'Deferred / delayed',
            self::OTHER => 'Other',
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
