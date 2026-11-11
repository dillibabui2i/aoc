<?php

namespace App\VBC\Http\Requests\Hospitalization;

use App\Http\Requests\CoreRequest;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use Illuminate\Validation\Rule;

class GetHospitalizationAssigneesRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'encounter_type' => ['sometimes', 'string', Rule::enum(EncounterTypeEnum::class)],
        ];
    }
}
