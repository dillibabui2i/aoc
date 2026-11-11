<?php

namespace App\VBC\Http\Requests\Diagnosis;

use App\Http\Requests\CoreRequest;

/** Mirrors HospitalizationClientSearchDTO::MIN_SEARCH_LENGTH's 3-character minimum for search fields. */
class SearchIcd10CodesRequest extends CoreRequest
{
    public const MIN_SEARCH_LENGTH = 3;

    public function rules(): array
    {
        return [
            'terms' => ['required', 'string', 'min:' . self::MIN_SEARCH_LENGTH],
        ];
    }

    public function terms(): string
    {
        return $this->input('terms');
    }
}
