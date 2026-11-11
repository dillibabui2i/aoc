<?php

namespace App\VBC\Http\Requests\HospitalizationTrackerDiagnosis;

use App\Http\Requests\CoreRequest;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use Illuminate\Validation\Rule;

/** "Was the diagnosis made?" toggle (AC3-AC8/AC19) — not a HospitalizationTrackerFieldEnum case, see
 * HospitalizationTrackerDiagnosisService. */
class UpdateWasDiagnosisMadeRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'was_diagnosis_made' => ['required', 'boolean'],
            // Which viewer is asking (IP when omitted) — see HospitalizationTrackerDiagnosisController::findTracker().
            'encounter_type' => ['sometimes', 'string', Rule::enum(EncounterTypeEnum::class)],
        ];
    }

    public function value(): bool
    {
        return (bool) $this->input('was_diagnosis_made');
    }
}
