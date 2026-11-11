<?php

namespace App\Enums;

enum MssqlPatientStatus: int
{
    case ACTIVE = 1;
    case INACTIVE = 2;
    case NON_ADMIT = 3;
    case INTAKE = 4;
    case DISCHARGED = 5;
    case ON_HOLD = 6;
    case PENDING_SOC = 7;
    case TRANSFERRED = 8;
    case EMPTY = 9;
    case EOE = 10;

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'InActive',
            self::NON_ADMIT => 'Non-Admit',
            self::INTAKE => 'Intake',
            self::DISCHARGED => 'Discharged',
            self::ON_HOLD => 'On Hold',
            self::PENDING_SOC => 'Pending SOC',
            self::TRANSFERRED => 'Transferred',
            self::EMPTY => '',
            self::EOE => 'EOE',
        };
    }
}
