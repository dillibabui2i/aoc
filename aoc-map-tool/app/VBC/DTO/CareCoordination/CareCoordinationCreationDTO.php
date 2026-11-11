<?php

namespace App\VBC\DTO\CareCoordination;

use Carbon\CarbonInterface;

/** One submit of the "Care Coordination" modal's create flow. */
final readonly class CareCoordinationCreationDTO
{
    public function __construct(
        public int $encounterId,
        public int $careCoordinationTypeId,
        public ?int $assignedToId,
        public ?CarbonInterface $dueAt,
        public ?CarbonInterface $completedAt,
        public ?string $notes,
    ) {
    }
}
