<?php

namespace App\VBC\Http\Resources\HospitalizationTrackerTraining;

use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerTrainingModel;
use Illuminate\Http\Resources\Json\JsonResource;

class HospitalizationTrackerTrainingResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var HospitalizationTrackerTrainingModel $training */
        $training = $this->resource;

        return [
            'id' => $training->id,
            'training_type_id' => $training->training_type_id,
            'training_type_name' => $training->type->name,
            'training_source_id' => $training->training_source_id,
            'training_source_name' => $training->source->name,
            'notes' => $training->notes,
            'is_completed' => $training->is_completed,
        ];
    }
}
