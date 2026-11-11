<?php

namespace App\DTO\Microsoft;

class AttendeesDTO
{
    /** @var AttendeeDTO[] */
    private array $attendees;

    public function __construct(AttendeeDTO ...$attendees)
    {
        $this->attendees = $attendees;
    }

    public function getAttendees(): array
    {
        return $this->attendees;
    }
}
