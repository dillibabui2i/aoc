<?php

namespace App\VBC\Enum\HospitalizationTracker;

enum HospitalizationTrackerStatusEnum: string
{
    case NEW = 'NEW';
    case OPEN = 'OPEN';
    case IN_PROGRESS = 'IN_PROGRESS';
    case DISCHARGED = 'DISCHARGED';
    case READY_FOR_REVIEW = 'READY_FOR_REVIEW';
    case READY_FOR_NEXT_STEPS = 'READY_FOR_NEXT_STEPS';
    case COMPLETED = 'COMPLETED';
    case CLOSED = 'CLOSED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'New',
            self::OPEN => 'Open',
            self::IN_PROGRESS => 'In Progress',
            self::DISCHARGED => 'Discharged',
            self::READY_FOR_REVIEW => 'Ready for Review',
            self::READY_FOR_NEXT_STEPS => 'Ready for Next Steps',
            self::COMPLETED => 'Completed',
            self::CLOSED => 'Closed',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Statuses of the ICM Hospitalization (IP) lifecycle. CANCELLED belongs to the ED Visit lifecycle only
     * (PD-3972), so it is kept out of the shared filter options the Hospitalization buckets read.
     *
     * @return self[]
     */
    public static function hospitalizationCases(): array
    {
        return array_values(array_filter(self::cases(), fn (self $case) => $case !== self::CANCELLED));
    }

    /**
     * Statuses the ICM dashboard "Hospitalizations" bucket shows (PD-5775): every tracker still in the
     * lifecycle, up to (not including) Completed/Closed. Both the bucket query and its Status filter read this.
     *
     * @return self[]
     */
    public static function bucketCases(): array
    {
        return [
            self::OPEN,
            self::IN_PROGRESS,
            self::DISCHARGED,
            self::READY_FOR_REVIEW,
            self::READY_FOR_NEXT_STEPS,
        ];
    }

    /**
     * Statuses the Triage "ED Visits" bucket shows (PD-4057 AC10, widened by PD-5771). Both the bucket query
     * and its Status filter read this.
     *
     * @return self[]
     */
    public static function edVisitsBucketCases(): array
    {
        return [
            self::IN_PROGRESS,
            self::READY_FOR_REVIEW,
            self::READY_FOR_NEXT_STEPS,
        ];
    }

    public static function options(): array
    {
        return self::toOptions(self::hospitalizationCases());
    }

    public static function bucketOptions(): array
    {
        return self::toOptions(self::bucketCases());
    }

    public static function edVisitsBucketOptions(): array
    {
        return self::toOptions(self::edVisitsBucketCases());
    }

    /** @param self[] $cases */
    private static function toOptions(array $cases): array
    {
        return array_map(
            fn (self $case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            $cases
        );
    }

    public static function list(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->toArray();
    }

    /**
     * Statuses that count as "active" for the Care Coordination patient search (AC5 of PD-3494): a
     * client is offered once their tracker enters In Progress, and stays offered through the rest of the
     * discharge lifecycle up to (not including) Completed/Closed. This is a narrower/different set than
     * HospitalizationReadRepository::findActiveTrackerForPatient()'s "not Completed/Closed" — scoped to
     * this one caller, not a general-purpose "active tracker" definition.
     *
     * @return self[]
     */
    public static function activeCareCoordinationStatuses(): array
    {
        return [
            self::IN_PROGRESS,
            self::DISCHARGED,
            self::READY_FOR_REVIEW,
            self::READY_FOR_NEXT_STEPS,
        ];
    }
}
