<?php

namespace App\Http\Requests\TrachVentClass;

use App\Http\Requests\CoreRequest;

class SearchClientsRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function term(): string
    {
        $data = $this->validated();

        return (string) ($data['q'] ?? '');
    }
}
