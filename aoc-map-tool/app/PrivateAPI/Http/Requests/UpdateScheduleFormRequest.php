<?php

namespace App\PrivateAPI\Http\Requests;

use App\PrivateAPI\Rules\ActiveNthAccount;
use App\PrivateAPI\Rules\ShiftsRemovableFromNth;
use App\PrivateAPI\Support\ScheduleRemoval;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class UpdateScheduleFormRequest extends ApiCoreRequest
{
    private const CATEGORY_NOT_ALLOWED_MSG = 'Category /Description is not allowed';

    public function rules(): array
    {
        return [
            'nthId' => ['required', 'integer', new ActiveNthAccount()],
            'shiftIds' => ['required', 'array', 'min:1', new ShiftsRemovableFromNth($this->input('nthId'))],
            'shiftIds.*' => ['required', 'integer', 'distinct'],
            'reasonCategory' => ['required', Rule::in([ScheduleRemoval::REASON_CATEGORY])],
            'reason' => ['required', Rule::in([ScheduleRemoval::REASON])],
        ];
    }

    public function messages(): array
    {
        return [
            'reasonCategory.in' => self::CATEGORY_NOT_ALLOWED_MSG,
            'reason.in' => self::CATEGORY_NOT_ALLOWED_MSG,
            'shiftIds.*.distinct' => 'The same shift is listed more than once.',
        ];
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
