<?php

namespace App\Enums;

/**
 * Payer rates mirroring DB column names.
 */
enum PayerRates : string
{
    case HHA_PRIMARY_GROUP = 'hhagroupprimaryrate';
    case CNA_PRIMARY_GROUP = 'cnagroupprimaryrate';
    case LVN_LPN = 'lvnlpnrate';
    case LVN_LPN_SPECIAL = 'lvnlpnspecrate';

    case RN = 'rnrate';
    case RN_SPECIAL = 'rnspecrate';
}
