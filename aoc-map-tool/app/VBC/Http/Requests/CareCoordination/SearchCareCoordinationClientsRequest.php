<?php

namespace App\VBC\Http\Requests\CareCoordination;

use App\Http\Requests\CoreRequest;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use Illuminate\Validation\Rule;

class SearchCareCoordinationClientsRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'search' => ['required', 'string', 'min:1', 'max:255'],
            'encounter_type' => ['sometimes', 'nullable', Rule::enum(EncounterTypeEnum::class)],
        ];
    }

    public function encounterType(): ?EncounterTypeEnum
    {
        return $this->filled('encounter_type') ? EncounterTypeEnum::from($this->input('encounter_type')) : null;
    }
}
