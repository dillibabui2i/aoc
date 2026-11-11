<?php

namespace App\Http\Requests;

use App\DTO\PolicyStatusDTO;

class PolicyStatusRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'numeric', 'exists:external_users,id'],
            'language' => ['required', 'string']
        ];
    }

    public function toDTO(): PolicyStatusDTO
    {
        return new PolicyStatusDTO(
            userId: $this->input('id'),
            language: $this->input('language')
        );
    }
}
