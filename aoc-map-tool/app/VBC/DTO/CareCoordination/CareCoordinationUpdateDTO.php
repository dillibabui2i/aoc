<?php

namespace App\VBC\DTO\CareCoordination;

use Carbon\CarbonInterface;

/** One submit of the "Care Coordination" modal's edit flow — the encounter link never changes. */
final readonly class CareCoordinationUpdateDTO
{
    public function __construct(
        public int $careCoordinationTypeId,
        public ?int $assignedToId,
        public ?CarbonInterface $dueAt,
        public ?CarbonInterface $completedAt,
        public ?string $notes,
    ) {
    }
}
