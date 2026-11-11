<?php

namespace App\VBC\Http\Requests\HospitalizationTrackerTraining;

use App\Http\Requests\CoreRequest;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use Illuminate\Validation\Rule;

/** "Staff / Family Training/Education required?" toggle — not a HospitalizationTrackerFieldEnum case,
 * see HospitalizationTrackerTrainingService. */
class UpdateStaffFamilyTrainingRequiredRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'staff_family_training_required' => ['required', 'boolean'],
            // Which viewer is asking (IP when omitted) — see HospitalizationTrackerTrainingController::findTracker().
            'encounter_type' => ['sometimes', 'string', Rule::enum(EncounterTypeEnum::class)],
        ];
    }

    public function value(): bool
    {
        return (bool) $this->input('staff_family_training_required');
    }
}
