<?php

namespace App\Http\Requests\CCRAvailability;

class GetAvailabilityRequest extends AvailabilityRequest
{
    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'facility_id' => ['required', 'integer'],
        ];
    }
}
