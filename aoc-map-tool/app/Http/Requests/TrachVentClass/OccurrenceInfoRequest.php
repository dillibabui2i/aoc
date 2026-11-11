<?php

namespace App\Http\Requests\TrachVentClass;

use App\Http\Requests\CoreRequest;

class OccurrenceInfoRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'occurrence_id' => ['required', 'integer'],
        ];
    }

    public function occurrenceId(): int
    {
        $data = $this->validated();

        return (int) $data['occurrence_id'];
    }
}
