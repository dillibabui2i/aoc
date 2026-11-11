<?php

declare(strict_types=1);

namespace App\Enums;

enum DisciplineEnum: string
{
    case ABA = 'ABA';
    case AIDE = 'AIDE';
    case BCBA = 'BCBA';
    case CNA = 'CNA';
    case EMT = 'EMT';
    case IHSS_ATTN = 'IHSS ATTN';
    case LHA = 'LHA';
    case LPN = 'LPN';
    case LVN = 'LVN';
    case MSW = 'MSW';
    case OT = 'OT';
    case OTA = 'OTA';
    case PPEC = 'PPEC';
    case PT = 'PT';
    case PTA = 'PTA';
    case RN = 'RN';
    case ST = 'ST';
    case STA = 'STA';

    public static function zeroRates(): array
    {
        return [
            DisciplineEnum::AIDE,
            DisciplineEnum::CNA,
            DisciplineEnum::IHSS_ATTN,
            DisciplineEnum::LHA,
            DisciplineEnum::LPN,
            DisciplineEnum::LVN,
            DisciplineEnum::RN,
        ];
    }
}
