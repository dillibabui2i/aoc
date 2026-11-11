<?php

namespace App\Http\Requests\TrachVentClass;

use App\DTO\TrachVentClass\ScheduleClassOptionsDTO;
use App\Http\Requests\CoreRequest;

class ScheduleClassOptionsRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'location_id' => ['required', 'integer'],
            'trach_vent_class_type' => ['required', 'integer'],
            'class_type' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toDto(): ScheduleClassOptionsDTO
    {
        $data = $this->validated();

        return new ScheduleClassOptionsDTO(
            locationId: (int) $data['location_id'],
            trachVentClassType: (int) $data['trach_vent_class_type'],
            classTypeLabel: $data['class_type'] ?? null,
        );
    }
}
