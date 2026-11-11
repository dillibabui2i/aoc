<?php

namespace App\Http\Requests\TrachVentClass;

use App\Http\Requests\CoreRequest;

class RescheduleRegistrationRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'target_occurrence_id' => ['required', 'integer'],
        ];
    }
}
