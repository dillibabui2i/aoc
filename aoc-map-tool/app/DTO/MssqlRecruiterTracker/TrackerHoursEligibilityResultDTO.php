<?php

namespace App\DTO\MssqlRecruiterTracker;

final readonly class TrackerHoursEligibilityResultDTO
{
    public const STATUS_ALLOWED = 'allowed';
    public const STATUS_BLOCKED = 'blocked';

    public function __construct(
        public string $status,
        public ?float $highestWeeklyHours = null,
        public ?int $existingTrackerId = null,
    ) {
    }

    public static function allowed(): self
    {
        return new self(self::STATUS_ALLOWED);
    }

    public static function blocked(float $highestWeeklyHours, ?int $existingTrackerId): self
    {
        return new self(self::STATUS_BLOCKED, $highestWeeklyHours, $existingTrackerId);
    }
}
