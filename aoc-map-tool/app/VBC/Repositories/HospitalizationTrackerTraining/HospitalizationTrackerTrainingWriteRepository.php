<?php

namespace App\VBC\Repositories\HospitalizationTrackerTraining;

use App\VBC\DTO\Hospitalization\HospitalizationTrackerTrainingDTO;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerTrainingModel;

class HospitalizationTrackerTrainingWriteRepository
{
    public function __construct(private HospitalizationTrackerTrainingModel $trainingModel)
    {
    }

    public function create(
        int $trackerId,
        HospitalizationTrackerTrainingDTO $dto,
        ?int $completedById,
    ): HospitalizationTrackerTrainingModel {
        /** @var HospitalizationTrackerTrainingModel $training */
        $training = $this->trainingModel->newQuery()->create([
            'hospitalization_tracker_id' => $trackerId,
            'training_type_id' => $dto->trainingTypeId,
            'training_source_id' => $dto->trainingSourceId,
            'is_completed' => $dto->completed,
            // `completed_by_id`/`completed_at` are only stamped when the record is actually marked
            // completed — unlike Contacts' `received_by_id`, this flag can be toggled back off later,
            // so both are recomputed on every write rather than set once.
            'completed_by_id' => $dto->completed ? $completedById : null,
            'completed_at' => $dto->completed ? now() : null,
            'notes' => $dto->notes,
        ]);

        return $training;
    }

    public function update(
        HospitalizationTrackerTrainingModel $training,
        HospitalizationTrackerTrainingDTO $dto,
        ?int $completedById,
    ): HospitalizationTrackerTrainingModel {
        $training->update([
            'training_type_id' => $dto->trainingTypeId,
            'training_source_id' => $dto->trainingSourceId,
            'is_completed' => $dto->completed,
            'completed_by_id' => $dto->completed ? $completedById : null,
            'completed_at' => $dto->completed ? now() : null,
            'notes' => $dto->notes,
        ]);

        return $training;
    }
}
