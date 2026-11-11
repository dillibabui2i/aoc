<?php

namespace App\Enums;

enum ScheduleTypes: int
{
    case IPEA = 1;
    case CCR = 2;
    case PEPI = 3;
    case WEPI = 4;
    case TRAINING_SHIFT = 5;
    case CANDIDATE_START = 6;
    case SCHOOL_SET_UP = 7;
    case NEEDS_TO_BE_SCHEDULED_BY_RA = 8;

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::IPEA => 'IPEA',
            self::CCR => 'CCR',
            self::PEPI => 'PEPI',
            self::WEPI => 'WEPI',
            self::TRAINING_SHIFT => 'Training Shift',
            self::CANDIDATE_START => 'Candidate Start',
            self::SCHOOL_SET_UP => 'School Set Up',
            self::NEEDS_TO_BE_SCHEDULED_BY_RA => 'Needs to be Scheduled by RA',
        };
    }

    /**
     * @return array
     */
    public static function getListGMFilter(): array
    {
        $filtered = array_filter(self::cases(), fn($t) => in_array($t, [
            self::IPEA,
            self::CANDIDATE_START,
            self::PEPI,
        ]));

        return array_map(fn($type) => [
            'value' => $type->value,
            'text' => $type->label(),
        ], $filtered);
    }
}
