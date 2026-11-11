<?php

namespace App\VBC\Http\Requests\HospitalizationTrackerTraining;

use App\Http\Requests\CoreRequest;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\DTO\Hospitalization\HospitalizationTrackerTrainingDTO;
use Illuminate\Validation\Rule;

class UpdateHospitalizationTrackerTrainingRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'training_type_id' => ['required', 'integer', 'exists:hospitalization_tracker_training_types,id'],
            'training_source_id' => ['required', 'integer', 'exists:hospitalization_tracker_training_sources,id'],
            'notes' => ['nullable', 'string'],
            'is_completed' => ['required', 'boolean'],
            // Which viewer is asking (IP when omitted) — see HospitalizationTrackerTrainingController::findTracker().
            'encounter_type' => ['sometimes', 'string', Rule::enum(EncounterTypeEnum::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'training_type_id' => 'Training Type',
            'training_source_id' => 'Education Source',
            'notes' => 'Education Notes',
            'is_completed' => 'Training Completed',
        ];
    }

    public function toDto(): HospitalizationTrackerTrainingDTO
    {
        return new HospitalizationTrackerTrainingDTO(
            trainingTypeId: (int) $this->input('training_type_id'),
            trainingSourceId: (int) $this->input('training_source_id'),
            notes: $this->filled('notes') ? $this->input('notes') : null,
            completed: (bool) $this->input('is_completed'),
        );
    }
}
