<?php

namespace App\VBC\DTO\Hospitalization;

/** One submit of the "Training / Education" modal — shared shape for create and edit. */
final readonly class HospitalizationTrackerTrainingDTO
{
    public function __construct(
        public int $trainingTypeId,
        public int $trainingSourceId,
        public ?string $notes,
        public bool $completed,
    ) {
    }
}
