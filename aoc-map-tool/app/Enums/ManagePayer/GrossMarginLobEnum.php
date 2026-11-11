<?php

namespace App\Enums\ManagePayer;

enum GrossMarginLobEnum: int
{
    case RN_SP = 1;
    case RN = 2;
    case LVN_SP = 3;
    case LVN = 4;
    case CNA = 5;
    case HHA = 6;
    case ATT = 7;
    case PPEC = 8;
    case LHA = 9;
    case PT = 10;
    case OT = 11;
    case ST = 12;

    public function label(): string
    {
        return match ($this) {
            self::RN_SP => 'RN SP',
            self::RN => 'RN',
            self::LVN_SP => 'LVN SP',
            self::LVN => 'LVN',
            self::CNA => 'CNA',
            self::HHA => 'HHA',
            self::ATT => 'ATT',
            self::PPEC => 'PPEC',
            self::LHA => 'LHA',
            self::PT => 'PT',
            self::OT => 'OT',
            self::ST => 'ST',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn(self $case) => ['id' => $case->value, 'name' => $case->label()],
            self::cases()
        );
    }
}
