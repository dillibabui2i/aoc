<?php

namespace App\VBC\Http\Requests\Hospitalization;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\Hospitalization\HospitalizationClientSearchDTO;

class SearchHospitalizationClientsRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'search' => ['required', 'string', 'min:' . HospitalizationClientSearchDTO::MIN_SEARCH_LENGTH, 'max:255'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:' . HospitalizationClientSearchDTO::MAX_LIMIT],
        ];
    }

    public function toDto(): HospitalizationClientSearchDTO
    {
        return new HospitalizationClientSearchDTO(
            search: (string) $this->input('search'),
            limit: $this->filled('limit') ? (int) $this->input('limit') : HospitalizationClientSearchDTO::DEFAULT_LIMIT,
        );
    }
}
