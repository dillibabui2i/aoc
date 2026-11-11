<?php

namespace App\VBC\Http\Requests\Hospitalization;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\Hospitalization\HospitalizationTrackerFieldsDTO;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFieldEnum;
use Illuminate\Validation\Rule;

/**
 * One field of the header form at a time: which field is named in the body rather than the URL, so all
 * edits share a single endpoint. The accepted keys and the rules for each value both come from
 * HospitalizationTrackerFieldEnum, so a field can never be editable here without a declared storage
 * target — nor validated differently than it is on creation.
 */
class UpdateHospitalizationTrackerFieldRequest extends CoreRequest
{
    public function rules(): array
    {
        $field = $this->field();

        return [
            // Header fields only: the Current Hospitalization Info section is validated as a whole and
            // has its own endpoint, so its fields must not be reachable one at a time.
            'field' => ['required', 'string', Rule::in(HospitalizationTrackerFieldEnum::headerFieldValues())],
            // An unknown field is already rejected above, so there is nothing to validate the value
            // against — only its presence. A required field can't be cleared once set, hence 'required'
            // rather than 'present' for those.
            'value' => empty($field)
                ? ['present']
                : array_merge($field->isRequired() ? ['required'] : ['present'], $field->valueRules()),
        ];
    }

    /** A 422 here is about the field being edited, so "value" is named after it rather than after the key. */
    public function attributes(): array
    {
        return ['value' => $this->field()?->label() ?? 'value'];
    }

    /**
     * Not cast to string: an array/object "field" would blow up on the cast before validation could
     * reject it.
     */
    private function field(): ?HospitalizationTrackerFieldEnum
    {
        if (!is_string($this->input('field'))) {
            return null;
        }

        $field = HospitalizationTrackerFieldEnum::tryFrom($this->input('field'));

        return in_array($field, HospitalizationTrackerFieldEnum::HEADER_FIELDS, true) ? $field : null;
    }

    public function toDto(): HospitalizationTrackerFieldsDTO
    {
        return HospitalizationTrackerFieldsDTO::cast([
            (string) $this->input('field') => $this->input('value'),
        ]);
    }
}
