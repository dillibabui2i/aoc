<?php

namespace App\PrivateAPI\Http\Requests;

use App\PrivateAPI\Rules\AllowedProviderCustomFieldOption;
use App\PrivateAPI\Rules\ProviderEligibleForPreferenceUpdate;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class UpdateProviderPreferenceRequest extends ApiCoreRequest
{
    private const OPTIONS_FIELDS = [
        'providerSkills',
        'providerPreferredSchedule',
        'providerEnvironment',
        'providerLiftingRequirements',
    ];
    private const FREE_TEXT_FIELDS = [
        'providerLanguage',
    ];
    private const MAP = [
        'providerMileRadius' => 'aoc_cf_mile_radius',
        'providerEnvironment' => 'aoc_cf_environment',
        'providerLiftingRequirements' => 'aoc_cf_lifting_requirements',
        'providerLanguage' => 'aoc_cf_language',
        'providerSkills' => 'aoc_cf_skills',
        'providerPreferredSchedule' => 'aoc_cf_schedule',
    ];
    private const LABELS = [
        'providerMileRadius' => 'Mile Radius',
        'providerEnvironment' => 'Environment',
        'providerLiftingRequirements' => 'Lifting Requirements',
        'providerLanguage' => 'Language',
        'providerSkills' => 'Skills',
        'providerPreferredSchedule' => 'Preferred Schedule',
    ];
    private const ALLOWED_FIELDS = [
        'providerMileRadius',
        'providerEnvironment',
        'providerLiftingRequirements',
        'providerLanguage',
        'providerSkills',
        'providerPreferredSchedule',
    ];

    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id')]);
    }

    public function rules(): array
    {
        $rules = [
            'id' => ['required', 'integer', new ProviderEligibleForPreferenceUpdate()],
            'field' => ['required', 'string', Rule::in(self::ALLOWED_FIELDS)],
            'value' => ['required'],
        ];

        $field = $this->input('field');

        if ($field === 'providerMileRadius') {
            $rules['value'] = ['required', 'numeric', 'gte:0'];
        } elseif ($field === 'providerSkills') {
            $rules['value'][] = new AllowedProviderCustomFieldOption(self::MAP[$field], ['dropdown', 'mdropdown']);
        } elseif (in_array($field, self::OPTIONS_FIELDS, true)) {
            $rules['value'][] = new AllowedProviderCustomFieldOption(self::MAP[$field]);
        } elseif (in_array($field, self::FREE_TEXT_FIELDS, true)) {
            $rules['value'][] = 'string';
            $rules['value'][] = 'max:255';
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

        $field = $this->input('field');
        $value = $this->input('value');

        $this->merge([
            'table' => 'mssql_providers',
            'pk' => $this->input('id'),
            'name' => self::MAP[$field],
            'value' => is_array($value) ? implode(',', $value) : $value,
            'label' => self::LABELS[$field],
            'customfields' => true,
        ]);
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors();

        if ($errors->has('id')) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => "providerId {$this->input('id')} not found",
            ], Response::HTTP_NOT_FOUND));
        }

        if ($errors->has('field')) {
            $status = $this->filled('field') ? Response::HTTP_CONFLICT : Response::HTTP_BAD_REQUEST;
            $message = $this->filled('field') ? 'Update Not Allowed' : $errors->first('field');

            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $errors,
            ], $status));
        }

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $errors->first(),
            'errors' => $errors,
        ], Response::HTTP_BAD_REQUEST));
    }
}
