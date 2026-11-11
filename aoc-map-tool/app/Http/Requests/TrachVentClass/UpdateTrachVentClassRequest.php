<?php

namespace App\Http\Requests\TrachVentClass;

use App\DTO\TrachVentClass\UpdateTrachVentClassDTO;
use App\Http\Requests\CoreRequest;

class UpdateTrachVentClassRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'class_id' => ['required', 'integer'],
            'type' => ['required'],
            'location_id' => ['required', 'integer'],
            'instructor_id' => ['nullable', 'integer'],
            'class_date' => ['required', 'date'],
            'class_time' => ['required'],
            'end_time' => ['required', 'after:class_time'],
            'capacity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'class_type' => ['nullable', 'string'],
            'repeat_until' => ['nullable', 'date', 'after_or_equal:class_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_time.required' => 'Select an end time.',
            'end_time.after' => 'End time must be later than the start time.',
        ];
    }

    public function toDTO(): UpdateTrachVentClassDTO
    {
        $data = $this->validated();

        return new UpdateTrachVentClassDTO(
            classId: (int) $data['class_id'],
            type: $data['type'] ?? null,
            locationId: isset($data['location_id']) ? (int) $data['location_id'] : null,
            instructorId: isset($data['instructor_id']) ? (int) $data['instructor_id'] : null,
            classDate: date('Y-m-d', strtotime($data['class_date'])),
            classTime: date('H:i', strtotime($data['class_time'])),
            endTime: date('H:i', strtotime($data['end_time'])),
            capacity: (int) $data['capacity'],
            notes: $data['notes'] ?? null,
            classType: $data['class_type'] ?? null,
            repeatUntil: isset($data['repeat_until']) && $data['repeat_until'] !== ''
                ? date('Y-m-d', strtotime($data['repeat_until']))
                : null,
        );
    }
}
