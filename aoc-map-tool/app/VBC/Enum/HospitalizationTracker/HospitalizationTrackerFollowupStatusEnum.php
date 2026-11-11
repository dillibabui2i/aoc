<?php

namespace App\VBC\Enum\HospitalizationTracker;

enum HospitalizationTrackerFollowupStatusEnum: string
{
    case NOT_STARTED = 'NOT_STARTED';
    case FIRST_ATTEMPT = 'FIRST_ATTEMPT';
    case SECOND_ATTEMPT = 'SECOND_ATTEMPT';
    case THIRD_ATTEMPT = 'THIRD_ATTEMPT';
    case COMPLETED = 'COMPLETED';
    case NEW = 'NEW';
    case OPEN = 'OPEN';
    case SCHEDULED = 'SCHEDULED';
    case ATTEMPTED = 'ATTEMPTED';
    case CANCELED = 'CANCELED';

    public function label(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'Not Started',
            self::FIRST_ATTEMPT => 'First Attempt',
            self::SECOND_ATTEMPT => 'Second Attempt',
            self::THIRD_ATTEMPT => 'Third Attempt',
            self::COMPLETED => 'Completed',
            self::NEW => 'New',
            self::OPEN => 'Open',
            self::SCHEDULED => 'Scheduled',
            self::ATTEMPTED => 'Attempted',
            self::CANCELED => 'Canceled',
        };
    }

    /**
     * ED Visit follow-up workflow (PD-4073): the statuses a follow-up may be in, and what each one
     * allows next. Not Started -> First Attempt -> Second Attempt -> Third Attempt, with Completed
     * reachable from any status that is not itself terminal (Completed, Third Attempt).
     *
     * @return array<int, self>
     */
    public function edVisitNextOutcomes(): array
    {
        return match ($this) {
            self::NOT_STARTED => [self::COMPLETED, self::FIRST_ATTEMPT],
            self::FIRST_ATTEMPT => [self::COMPLETED, self::SECOND_ATTEMPT],
            self::SECOND_ATTEMPT => [self::COMPLETED, self::THIRD_ATTEMPT],
            default => [],
        };
    }

    /** @return array<int, self> */
    public static function edVisitStatuses(): array
    {
        return [self::NOT_STARTED, self::FIRST_ATTEMPT, self::SECOND_ATTEMPT, self::THIRD_ATTEMPT, self::COMPLETED];
    }

    public function isAttempt(): bool
    {
        return in_array($this, [self::FIRST_ATTEMPT, self::SECOND_ATTEMPT, self::THIRD_ATTEMPT], true);
    }

    public static function options(): array
    {
        return array_map(
            fn (self $case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            self::cases()
        );
    }

    public static function list(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->toArray();
    }
}
