<?php

namespace App\DTO\Microsoft;

use Carbon\Carbon;
use Microsoft\Graph\Generated\Models\DateTimeTimeZone;

class OutlookEventDTO
{
    public function __construct(
        private string $subject,
        private string $description,
        private Carbon $startDate,
        private Carbon $endDate,
        private string $timezone,
        private string $location,
        private bool $isOnline,
        private string $msEventId,
        private AttendeesDTO $attendees
    ) {
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStartDate(): DateTimeTimeZone
    {
        $start = new DateTimeTimeZone();
        $start->setDateTime($this->startDate);
        $start->setTimeZone($this->getTimezone());

        return $start;
    }

    public function getEndDate(): DateTimeTimeZone
    {
        $end = new DateTimeTimeZone();
        $end->setDateTime($this->endDate);
        $end->setTimeZone($this->getTimezone());

        return $end;
    }

    public function hasLocation(): bool
    {
        return !empty($this->location);
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function hasTimezone(): bool
    {
        return !empty($this->timezone);
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function isOnlineMeeting(): bool
    {
        return $this->isOnline;
    }

    public function getAttendeesDTO(): AttendeesDTO
    {
        return $this->attendees;
    }

    public function hasMSEventId(): bool
    {
        return !empty($this->msEventId);
    }

    public function getMSEventId(): string
    {
        return $this->msEventId;
    }
}
