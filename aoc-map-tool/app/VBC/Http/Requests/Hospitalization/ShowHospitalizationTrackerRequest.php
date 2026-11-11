<?php

namespace App\VBC\Http\Requests\Hospitalization;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\Hospitalization\ShowHospitalizationTrackerDTO;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use Illuminate\Validation\Rule;

/**
 * `hospitalization.trackers.show` is the single by-id lookup shared by both the Hospitalization (IP)
 * and ED Visit viewers, and a tracker id alone doesn't say which viewer is asking for it. `encounter_type`
 * closes that gap (PD-3983 follow-up): the caller states which kind it expects, and the controller 404s
 * instead of handing back a tracker of the other kind — otherwise an ED tracker id could be opened as
 * IP, or vice versa, bypassing that type's field set/logic.
 */
class ShowHospitalizationTrackerRequest extends CoreRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['encounter_type' => $this->route('encounterType')]);
    }

    public function rules(): array
    {
        return [
            'encounter_type' => ['required', 'string', Rule::enum(EncounterTypeEnum::class)],
        ];
    }

    public function toDto(): ShowHospitalizationTrackerDTO
    {
        return new ShowHospitalizationTrackerDTO(
            encounterType: EncounterTypeEnum::from($this->input('encounter_type')),
        );
    }
}
