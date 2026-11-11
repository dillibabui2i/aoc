<?php

namespace App\Enums;

enum RecruitingBucket: string
{
    case PEPI = 'PEPI';
    case WEPI = 'WEPI';
    case TRAINING_SHIFT = 'TRAINING SHIFT';
    case NTH_LEADS_AND_INQUIRIES = 'NTH_LEADS_AND_INQUIRIES';

    public static function supportsPendingOutcomes(string $bucket): bool
    {
        return in_array($bucket, [
            self::PEPI->value,
            self::WEPI->value,
            self::TRAINING_SHIFT->value,
        ]);
    }
}
