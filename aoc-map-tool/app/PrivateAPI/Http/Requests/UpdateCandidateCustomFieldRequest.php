<?php

namespace App\PrivateAPI\Http\Requests;

use App\CustomFields;
use App\Enums\EntityTypeEnum;
use App\PrivateAPI\Rules\AllowedCandidateCustomFieldOption;
use App\PrivateAPI\Rules\CandidateEligibleForPreferenceUpdate;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class UpdateCandidateCustomFieldRequest extends ApiCoreRequest
{
    private const OPTIONS_FIELDS = [
        'skills',
        'preferredSchedule',
        'lifting',
        'environment',
    ];
    private const FREE_TEXT_FIELDS = [
        'language',
        'comments',
    ];
    private const map = [
        'language' => 'aoc_cf_language',
        'skills' => 'aoc_cf_skills',
        'preferredSchedule' => 'aoc_cf_schedule',
        'mileRadius' => 'aoc_cf_mile_radius',
        'lifting' => 'aoc_cf_lifting_requirements',
        'environment' => 'aoc_cf_environment',
        'comments' => 'comments',
    ];
    private const ALLOWED_FIELDS = [
        'language',
        'skills',
        'preferredSchedule',
        'mileRadius',
        'lifting',
        'environment',
        'comments',
    ];

    public function rules(): array
    {
        $rules = [
            'candidate_id' => [
                'required',
                'integer',
                'exists:candidates,id',
                new CandidateEligibleForPreferenceUpdate()
            ],
            'field' => ['required', 'string', Rule::in(self::ALLOWED_FIELDS)],
            'value' => ['required'],
            'type' => ['required', 'string'],
        ];

        $field = $this->input('field');

        if ($field === 'mileRadius') {
            $rules['value'] = ['required', 'numeric', 'gt:0'];
        } elseif (in_array($field, self::OPTIONS_FIELDS, true)) {
            $rules['value'][] = new AllowedCandidateCustomFieldOption(self::map[$field]);
        } elseif (in_array($field, self::FREE_TEXT_FIELDS, true)) {
            $rules['value'][] = 'string';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'field.in' => 'Unknown field',
        ];
    }

    protected function passedValidation(): void
    {
        parent::passedValidation();

        $field = self::map[$this->input('field')];

        $this->merge([
            'field' => $field,
            'type' => $this->resolveInputType($field) ?? $this->input('type'),
        ]);
    }

    private function resolveInputType(string $dbFieldName): ?string
    {
        return CustomFields::query()
            ->where('is_deleted', 0)
            ->where('db_field_name', $dbFieldName)
            ->where('entity', EntityTypeEnum::CANDIDATE->value)
            ->value('input_type');
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], Response::HTTP_BAD_REQUEST));
    }
}
