<?php

namespace App\PrivateAPI\Http\Requests\Trackers;

use App\PrivateAPI\Http\Requests\ApiCoreRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractTrackerRequest extends ApiCoreRequest
{
    /**
     * Optional metadata fields shared by every mode (POST add, PATCH metadata-only,
     * and layered on top of any PATCH scheduling/outcome submode).
     */
    protected const METADATA_FIELDS_MAP = [
        'notes' => 'note',
        'totalHours' => 'total_hours',
        'nthScheduleIds' => 'nth_schedule_ids',
        'agreedPayRate' => 'pay_rate',
        'gp' => 'gp',
        'approvedBy' => 'gp_approved_by',
    ];

    protected function metadataRules(): array
    {
        return [
            'notes' => ['sometimes', 'nullable', 'string', 'max:255'],
            'totalHours' => ['sometimes', 'nullable', 'numeric', 'between:-999.99,999.99'],
            'nthScheduleIds' => ['sometimes', 'nullable', 'array'],
            'nthScheduleIds.*' => ['integer'],
            'agreedPayRate' => ['sometimes', 'nullable', 'numeric', 'between:-999.99,999.99'],
            // GP is sent/stored on the same percentage scale the legacy UI uses (e.g. "36" or "36%"), not a 0-1 fraction.
            'gp' => ['sometimes', 'nullable', 'regex:/^-?\d{1,3}(\.\d{1,2})?%?$/'],
            'approvedBy' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    protected function mergeMetadataFields(): void
    {
        $mapped = [];

        foreach (self::METADATA_FIELDS_MAP as $external => $legacy) {
            if ($this->has($external)) {
                $mapped[$legacy] = $this->input($external);
            }
        }

        $this->merge($mapped);
    }

    /**
     * Every failure is reported before saveScheduleNext ever runs (this is a pre-validation
     * layer). The HTTP status varies by failure kind: 404 when a referenced tracker/schedule
     * row doesn't exist, 409 when the request conflicts with existing state, 400 otherwise.
     */
    protected function failedValidation(ValidatorContract $validator): void
    {
        $status = match (true) {
            $validator->errors()->has('_not_found') => Response::HTTP_NOT_FOUND,
            $validator->errors()->has('_conflict') => Response::HTTP_CONFLICT,
            default => Response::HTTP_BAD_REQUEST,
        };

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], $status));
    }
}
