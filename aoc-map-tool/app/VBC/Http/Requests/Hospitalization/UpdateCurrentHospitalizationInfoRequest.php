<?php

namespace App\VBC\Http\Requests\Hospitalization;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\Hospitalization\HospitalizationTrackerFieldsDTO;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFieldEnum;

/**
 * The "Current Hospitalization Info" section, submitted as a whole. Its fields carry required rules that
 * only make sense checked together, so there is no per-field endpoint for them — the section is either
 * valid and saved, or rejected with the fields to fix.
 */
class UpdateCurrentHospitalizationInfoRequest extends CoreRequest
{
    /**
     * Fields the form only shows once the tracker has been discharged, or once another field turns them
     * on. They are required in the same sense as the rest — but only when the form actually shows them,
     * which is why they are `sometimes` here rather than plain `required`.
     */
    private const CONDITIONAL_FIELDS = [
        HospitalizationTrackerFieldEnum::EXPECTED_DISCHARGE_AT->value,
        HospitalizationTrackerFieldEnum::DISCHARGED_AT->value,
    ];

    public function rules(): array
    {
        $rules = [];

        foreach (HospitalizationTrackerFieldEnum::currentInfoFields() as $field) {
            $rules[$field->value] = array_merge($this->presenceRules($field), $field->valueRules());

            $elementRules = $field->elementRules();
            if (!empty($elementRules)) {
                $rules[$field->value . '.*'] = $elementRules;
            }
        }

        return $rules;
    }

    /** So a 422 names the field the way the form labels it. */
    public function attributes(): array
    {
        $attributes = [];

        foreach (HospitalizationTrackerFieldEnum::cases() as $field) {
            $attributes[$field->value] = $field->label();
            // Otherwise a bad element of a multi-value field is reported as "aoc_care_requirements.1".
            $attributes[$field->value . '.*'] = $field->label();
        }

        return $attributes;
    }

    public function toDto(): HospitalizationTrackerFieldsDTO
    {
        $submitted = array_intersect_key(
            $this->validated(),
            array_flip(array_column(HospitalizationTrackerFieldEnum::currentInfoFields(), 'value'))
        );

        return HospitalizationTrackerFieldsDTO::cast($submitted);
    }

    private function presenceRules(HospitalizationTrackerFieldEnum $field): array
    {
        if (!$field->isRequired()) {
            return ['sometimes', 'nullable'];
        }

        if (in_array($field->value, self::CONDITIONAL_FIELDS, true)) {
            return ['sometimes', 'required'];
        }

        // Only relevant while the toggle above it is on, and the form only shows it then. Deliberately
        // without `sometimes`: that would skip the whole rule set when the field is absent, which is
        // exactly the case required_if has to catch.
        if ($field === HospitalizationTrackerFieldEnum::FIRST_BILLABLE_SHIFT_AT) {
            return [
                'required_if:' . HospitalizationTrackerFieldEnum::FIRST_BILLABLE_SHIFT_COMPLETED->value . ',true',
                'nullable',
            ];
        }

        return ['required'];
    }
}
