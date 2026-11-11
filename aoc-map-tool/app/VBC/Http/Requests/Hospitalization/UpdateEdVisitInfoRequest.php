<?php

namespace App\VBC\Http\Requests\Hospitalization;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\Hospitalization\HospitalizationTrackerFieldsDTO;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFieldEnum;

/**
 * The "ED Visit Record" form's "Hospitalization Info" tab (PD-3983), submitted as a whole — same
 * reasoning as UpdateCurrentHospitalizationInfoRequest, which this mirrors field-for-field except for
 * the field set itself (HospitalizationTrackerFieldEnum::edVisitInfoFields() instead of
 * currentInfoFields()).
 */
class UpdateEdVisitInfoRequest extends CoreRequest
{
    /**
     * Each vaccination date is only meaningful once its own vaccine question is answered "Yes" — the
     * form only shows it then, same `required_if` pattern as Hospitalization's
     * FIRST_BILLABLE_SHIFT_AT/FIRST_BILLABLE_SHIFT_COMPLETED pair.
     */
    private const CONDITIONAL_DATE_FIELDS = [
        HospitalizationTrackerFieldEnum::FLU_VACCINATION_DATE->value => HospitalizationTrackerFieldEnum::ANNUAL_FLU_VACCINE_STATUS->value,
        HospitalizationTrackerFieldEnum::COVID19_VACCINATION_DATE->value => HospitalizationTrackerFieldEnum::COVID19_VACCINE_STATUS->value,
        HospitalizationTrackerFieldEnum::RSV_VACCINATION_DATE->value => HospitalizationTrackerFieldEnum::RSV_VACCINE_STATUS->value,
    ];

    public function rules(): array
    {
        $rules = [];

        foreach (HospitalizationTrackerFieldEnum::edVisitInfoFields() as $field) {
            $rules[$field->value] = array_merge($this->presenceRules($field), $field->valueRules());
        }

        return $rules;
    }

    /** So a 422 names the field the way the form labels it. */
    public function attributes(): array
    {
        $attributes = [];

        foreach (HospitalizationTrackerFieldEnum::cases() as $field) {
            $attributes[$field->value] = $field->label();
        }

        return $attributes;
    }

    public function toDto(): HospitalizationTrackerFieldsDTO
    {
        $submitted = array_intersect_key(
            $this->validated(),
            array_flip(array_column(HospitalizationTrackerFieldEnum::edVisitInfoFields(), 'value'))
        );

        return HospitalizationTrackerFieldsDTO::cast($submitted);
    }

    private function presenceRules(HospitalizationTrackerFieldEnum $field): array
    {
        // Required only once the question it depends on is answered "Yes" — deliberately without
        // `sometimes`, which would skip the rule entirely when the field is omitted, exactly the case
        // required_if has to catch.
        if (array_key_exists($field->value, self::CONDITIONAL_DATE_FIELDS)) {
            return [
                'required_if:' . self::CONDITIONAL_DATE_FIELDS[$field->value] . ',YES',
                'nullable',
            ];
        }

        if (!$field->isRequired()) {
            return ['sometimes', 'nullable'];
        }

        return ['required'];
    }
}
