<?php

namespace App\PrivateAPI\Http\Requests;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpFoundation\Response;

class UpdateCandidateRequest extends ApiCoreRequest
{
    private const TABLE = 'candidatesDetails';
    private const PHONE_REGEX = '/^\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}$/';
    private const ONLY_ONE_FIELD_MSG = 'Only one field per request is allowed.';
    private const RESERVED_FIELDS = ['fullName'];
    private const FIELDS_MAP = [
        'firstName' => 'firstName',
        'lastName' => 'lastName',
        'homePhone' => 'homePhone',
        'mobile' => 'mobile',
        'email' => 'email',
        'address1' => 'address',
        'address2' => 'address2',
        'city' => 'city',
        'state' => 'stateCode',
        'zip' => 'postalCode',
    ];

    public function rules(): array
    {
        $rules = [
            'pk' => ['required', 'integer', Rule::exists('candidates', 'id')],
            'name' => ['required', 'string', Rule::in(array_keys(self::FIELDS_MAP))],
            'value' => ['required', 'string', 'max:255'],
        ];

        switch ($this->input('name')) {
            case 'email':
                $rules['value'] = ['required', 'email', 'max:255'];
                break;
            case 'homePhone':
            case 'mobile':
                $rules['value'] = ['required', 'string', 'regex:' . self::PHONE_REGEX];
                break;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.in' => 'Unknown field',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (!empty($this->conflictingFields())) {
                $validator->errors()->add('fields', self::ONLY_ONE_FIELD_MSG);
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pk' => $this->route('id'),
        ]);
    }

    protected function passedValidation(): void
    {
        parent::passedValidation();

        $this->merge([
            'table' => self::TABLE,
            'name' => self::FIELDS_MAP[$this->input('name')],
        ]);
    }

    protected function failedValidation(ValidatorContract $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->has('fields')
                ? $validator->errors()->first('fields')
                : $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], Response::HTTP_BAD_REQUEST));
    }

    private function conflictingFields(): array
    {
        $reservedKeys = array_merge(array_keys(self::FIELDS_MAP), self::RESERVED_FIELDS);

        return array_values(array_intersect($reservedKeys, array_keys($this->all())));
    }
}
