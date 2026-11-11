<?php

namespace App\Enums\Adjustments;

enum AdjustmentEventTypeEnum: string
{

    case ASSIGNED = 'assigned';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::ASSIGNED => 'Assigned',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::COMPLETED => 'Completed',
        };
    }

    public static function casesWithoutAssigned(): array
    {
        return [
            self::APPROVED,
            self::REJECTED,
            self::COMPLETED,
        ];
    }

}
