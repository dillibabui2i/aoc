<?php

namespace App\Enums;

enum TrachVentClassClinicianEmailEnum: string
{
    case REGISTERED = 'registered';
    case CANCELLED = 'cancelled';
    case RESCHEDULED = 'rescheduled';

    public function subjectStatus(): string
    {
        return match ($this) {
            self::REGISTERED => 'Confirmed',
            self::CANCELLED => 'Canceled',
            self::RESCHEDULED => 'Rescheduled',
        };
    }

    /** Name of the email as it appears in Activity Log notes. */
    public function noteLabel(): string
    {
        return match ($this) {
            self::REGISTERED => 'Confirmation email',
            self::CANCELLED => 'Cancellation email',
            self::RESCHEDULED => 'Rescheduled email',
        };
    }
}
