<?php

namespace App\Http\Requests\TrachVentClass;

use App\Http\Requests\CoreRequest;

class CalendarSearchRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'term' => ['nullable', 'string', 'max:255'],
        ];
    }
}
