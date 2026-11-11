<?php

namespace App\Http\Requests\Candidates;

use App\Http\Requests\CoreRequest;
use App\Services\Candidate\CandidateService;

class UpdateCandidateRequest extends CoreRequest
{
    public function __construct(
        private CandidateService $service
    ) {
        parent::__construct();
    }

    public function rules(): array
    {
        $rules = [
            'candidate_id' => ['required', 'integer', 'exists:candidates,id'],
            'field' => ['required', 'string'],
            'value' => ['nullable'],
            'type' => ['required', 'string'],
        ];

        $customFields = $this->service
            ->getCustomFieldsForCandidate()
            ->pluck('input_type', 'db_field_name')
            ->toArray();

        $requiredFields = $this->service
            ->getCustomFieldsForCandidate()
            ->where('is_required', true)
            ->pluck('db_field_name')
            ->toArray();

        if ($this->has('field') && array_key_exists($this->field, $customFields)) {
            $isRequired = in_array($this->field, $requiredFields, true);
            $inputType = $customFields[$this->field];

            $fieldRules = match ($inputType) {
                'text', 'textarea', 'dropdown' => [$isRequired ? 'required' : 'nullable'],
                'mdropdown' => [$isRequired ? 'required' : 'nullable', 'array'],
                'date' => [$isRequired ? 'required' : 'nullable', 'date'],
                'boolean' => [$isRequired ? 'required' : 'nullable', 'boolean'],
                default => [$isRequired ? 'required' : 'nullable', 'string'],
            };

            $rules['value'] = $fieldRules;
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('value') && is_string($this->value)) {
            $decoded = json_decode($this->value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['value' => $decoded]);
            }
        }
    }
}
