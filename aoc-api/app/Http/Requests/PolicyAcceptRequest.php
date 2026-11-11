<?php

namespace App\Http\Requests;

use App\DTO\PolicyAcceptDTO;

class PolicyAcceptRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'acceptedTC' => ['required','boolean'],
            'acceptedPP' => ['required','boolean'],
            'language' => ['required','string'],
            'id' => ['required','numeric','exists:external_users,id'],
        ];
    }

    public function toDTO(): PolicyAcceptDTO
    {
        return new PolicyAcceptDTO(
            termsAccepted: $this->boolean('acceptedTC'),
            privacyAccepted: $this->boolean('acceptedPP'),
            userId: $this->input('id'),
            language: $this->input('language')
        );
    }
}
