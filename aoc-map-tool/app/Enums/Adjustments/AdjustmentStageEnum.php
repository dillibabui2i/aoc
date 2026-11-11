<?php

namespace App\Enums\Adjustments;

enum AdjustmentStageEnum: string
{
    case LEVEL_1 = 'level_1';
    case LEVEL_2 = 'level_2';
    case LEVEL_3 = 'level_3';
    case OPERATIONS = 'operations';
    case AR_SUPERVISOR = 'ar_supervisor';


    public function label(): string
    {
        return match ($this) {
            self::LEVEL_1 => 'Pending Approval Level 1',
            self::LEVEL_2 => 'Pending Approval Level 2',
            self::LEVEL_3 => 'Pending Approval Level 3',
            self::OPERATIONS => 'Pending Ops',
            self::AR_SUPERVISOR => 'Pending AR Supervisor',
        };
    }


}
