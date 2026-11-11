<?php

namespace App\Http\Requests\CCRAvailability;

class UpdateAvailabilityBlockRequest extends AvailabilityRequest
{
    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'block_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required'],
            'end_time' => ['required', 'after:start_time'],
            'note' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_time.after' => 'End time must be later than Start time.',
            'block_date.after_or_equal' => 'Date cannot be in the past.',
        ];
    }
}
