<?php

namespace App\VBC\Repositories\HospitalizationTrackerTraining;

use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerTrainingModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerTrainingSourceModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerTrainingTypeModel;
use Illuminate\Database\Eloquent\Collection;

class HospitalizationTrackerTrainingReadRepository
{
    public function __construct(
        private HospitalizationTrackerTrainingModel $trainingModel,
        private HospitalizationTrackerTrainingTypeModel $trainingTypeModel,
        private HospitalizationTrackerTrainingSourceModel $trainingSourceModel,
    ) {}

    public function findById(int $id): ?HospitalizationTrackerTrainingModel
    {
        /** @var HospitalizationTrackerTrainingModel|null $training */
        $training = $this->trainingModel->newQuery()->with(['type', 'source'])->find($id);

        return $training;
    }

    /** Active (non-archived) options for the "Training Type" dropdown. */
    public function getActiveTypeOptions(): Collection
    {
        return $this->trainingTypeModel->newQuery()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /** Active (non-archived) options for the "Education Source" dropdown. */
    public function getActiveSourceOptions(): Collection
    {
        return $this->trainingSourceModel->newQuery()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
