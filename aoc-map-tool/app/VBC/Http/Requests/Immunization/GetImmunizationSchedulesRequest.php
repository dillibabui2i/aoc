<?php

namespace App\VBC\Http\Requests\Immunization;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\Immunization\ImmunizationScheduleFilterDTO;
use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendationStatusEnum;
use Illuminate\Validation\Rule;

class GetImmunizationSchedulesRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', Rule::in(ImmunizationScheduleFilterDTO::ALLOWED_PER_PAGE)],
            'sort_by' => ['sometimes', 'string', Rule::in(ImmunizationScheduleFilterDTO::ALL_SORT_COLUMNS)],
            'sort_dir' => ['sometimes', 'in:asc,desc'],
            'vaccine_group_code' => ['sometimes', 'integer'],
            'vaccine_cvx_code' => ['sometimes', 'integer'],
            'states' => ['sometimes', 'array'],
            'states.*' => ['string', Rule::in(array_keys(config('constant.states')))],
            'status' => ['sometimes', 'string', Rule::enum(ImmunizationRecommendationStatusEnum::class)],
            'search' => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function toDto(): ImmunizationScheduleFilterDTO
    {
        return new ImmunizationScheduleFilterDTO(
            page: $this->filled('page') ? (int) $this->input('page') : 1,
            perPage: $this->filled('per_page') ? (int) $this->input('per_page') : ImmunizationScheduleFilterDTO::DEFAULT_PER_PAGE,
            sortBy: $this->filled('sort_by') ? $this->input('sort_by') : ImmunizationScheduleFilterDTO::SORT_VACCINE_CATEGORY,
            sortDir: $this->filled('sort_dir') ? $this->input('sort_dir') : 'asc',
            vaccineGroupCode: $this->filled('vaccine_group_code') ? (int) $this->input('vaccine_group_code') : null,
            vaccineCvxCode: $this->filled('vaccine_cvx_code') ? (int) $this->input('vaccine_cvx_code') : null,
            states: $this->filled('states') ? $this->input('states') : [],
            status: $this->filled('status') ? ImmunizationRecommendationStatusEnum::from($this->input('status')) : null,
            search: $this->filled('search') ? $this->input('search') : null,
        );
    }
}
