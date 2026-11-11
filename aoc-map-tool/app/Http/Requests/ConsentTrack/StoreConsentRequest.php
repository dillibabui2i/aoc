<?php

namespace App\Http\Requests\ConsentTrack;

use App\DTO\ConsentTrack\ConsentTrackDTO;
use App\Enums\ConsentTemplates\StatusEnum;
use App\Http\Requests\CoreRequest;
use Illuminate\Validation\Rules\Enum;

class StoreConsentRequest extends CoreRequest
{
    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'type' => ['string', 'required'],
            'language' => ['string', 'required'],
            'consentContent' => ['string', 'required'],
            'id' => ['string', 'nullable'],
            'action' =>  [
                'required',
                'string',
                new Enum(StatusEnum::class)
            ],
        ];
    }

    /**
     * @return ConsentTrackDTO
     */
    public function toDTO(): ConsentTrackDTO
    {
        return new ConsentTrackDTO(
            type: $this->get('type'),
            language: $this->get('language'),
            content: $this->get('consentContent'),
            status: $this->get('action'),
            id: $this->get('id') ?? null
        );
    }
}
