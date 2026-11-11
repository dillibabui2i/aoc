<?php

namespace App\VBC\Http\Requests\Immunization;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\Immunization\ImmunizationFilterDTO;
use App\VBC\Enum\VaccineDose\VaccineDoseOutcomeEnum;
use App\VBC\Enum\VaccineDose\VaccineDoseStatusEnum;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class GetImmunizationsRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'state' => ['sometimes', 'array'],
            'state.*' => ['string'],
            'branch_ids' => ['sometimes', 'array'],
            'branch_ids.*' => ['integer'],
            'vaccine_cvx_codes' => ['sometimes', 'array'],
            'vaccine_cvx_codes.*' => ['integer'],
            'statuses' => ['sometimes', 'array'],
            'statuses.*' => ['string', Rule::enum(VaccineDoseStatusEnum::class)],
            'outcomes' => ['sometimes', 'array'],
            'outcomes.*' => ['string', Rule::enum(VaccineDoseOutcomeEnum::class)],
            'payers' => ['sometimes', 'array'],
            'payers.*' => ['string'],
            'me_mode' => ['sometimes', 'nullable', 'in:true,false,1,0'],
            'search' => ['sometimes', 'string', 'max:255'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['sometimes', 'string', Rule::in(ImmunizationFilterDTO::ALL_SORT_COLUMNS)],
            'sort_dir' => ['sometimes', 'in:asc,desc'],
            'earliest_date_from' => ['sometimes', 'date_format:' . config('vbc.date_format')],
            'earliest_date_to' => ['sometimes', 'date_format:' . config('vbc.date_format'), 'after_or_equal:earliest_date_from'],
        ];
    }

    public function toDto(): ImmunizationFilterDTO
    {
        return new ImmunizationFilterDTO(
            state: $this->filled('state') ? $this->input('state') : null,
            branchIds: $this->filled('branch_ids') ? array_map('intval', $this->input('branch_ids')) : [],
            vaccineCvxCodes: $this->filled('vaccine_cvx_codes') ? array_map('intval', $this->input('vaccine_cvx_codes')) : [],
            statuses: $this->filled('statuses') ? $this->input('statuses') : [],
            payers: $this->filled('payers') ? $this->input('payers') : [],
            search: $this->filled('search') ? $this->input('search') : null,
            page: $this->filled('page') ? (int) $this->input('page') : 1,
            perPage: $this->filled('per_page') ? (int) $this->input('per_page') : 10,
            sortBy: $this->filled('sort_by') ? $this->input('sort_by') : ImmunizationFilterDTO::SORT_CREATED_AT,
            sortDir: $this->filled('sort_dir') ? $this->input('sort_dir') : 'desc',
            earliestDateFrom: $this->filled('earliest_date_from') ? $this->toDateString($this->input('earliest_date_from')) : null,
            earliestDateTo: $this->filled('earliest_date_to') ? $this->toDateString($this->input('earliest_date_to')) : null,
            meMode: $this->boolean('me_mode'),
            outcomes: $this->filled('outcomes') ? $this->input('outcomes') : [],
        );
    }

    private function toDateString(string $value): string
    {
        return Carbon::createFromFormat(config('vbc.date_format'), $value)->toDateString();
    }
}
