<?php

namespace App\VBC\Http\Requests\Followup;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\Hospitalization\HospitalizationClientSearchDTO;

/** Autocomplete for the Follow-up modal's "Hospitalized Client Name" field (AC4). */
class SearchFollowupClientsRequest extends CoreRequest
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
