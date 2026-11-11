<?php

namespace App\Http\Requests;

use App\DTO\PolicyListDTO;

class PolicyListRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'language' => ['required','string']
        ];
    }

    public function toDTO(): PolicyListDTO
    {
        return new PolicyListDTO(
            language: $this->input('language'),
        );
    }
}
