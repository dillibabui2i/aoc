<?php

namespace App\VBC\Http\Requests\Hospitalization;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\Hospitalization\HospitalizationFilterDTO;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationExportTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerIcmLevelEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class GetHospitalizationsRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'state' => ['sometimes', 'array'],
            'branch_ids' => ['sometimes', 'array'],
            'branch_ids.*' => ['integer'],
            'region_ids' => ['sometimes', 'array'],
            'region_ids.*' => ['integer'],
            'assignee_ids' => ['sometimes', 'array'],
            'assignee_ids.*' => ['integer'],
            'statuses' => ['sometimes', 'array'],
            'statuses.*' => ['string', Rule::enum(HospitalizationTrackerStatusEnum::class)],
            'levels' => ['sometimes', 'array'],
            'levels.*' => ['string', Rule::enum(HospitalizationTrackerIcmLevelEnum::class)],
            'facility_names' => ['sometimes', 'array'],
            'facility_names.*' => ['string'],
            'export_type' => ['sometimes', 'string', Rule::enum(HospitalizationExportTypeEnum::class)],
            'search' => ['sometimes', 'string', 'max:255'],
            'me_mode' => ['sometimes', 'nullable', 'in:true,false,1,0'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['sometimes', 'string', Rule::in(HospitalizationFilterDTO::ALL_SORT_COLUMNS)],
            'sort_dir' => ['sometimes', 'in:asc,desc'],
            'admission_date_from' => ['sometimes', 'date_format:' . config('vbc.date_format')],
            'admission_date_to' => ['sometimes', 'date_format:' . config('vbc.date_format'), 'after_or_equal:admission_date_from'],
            'encounter_types' => ['sometimes', 'array'],
            'encounter_types.*' => ['string', Rule::enum(EncounterTypeEnum::class)],
        ];
    }

    public function toDto(): HospitalizationFilterDTO
    {
        return new HospitalizationFilterDTO(
            state: $this->filled('state') ? $this->input('state') : null,
            branchIds: $this->filled('branch_ids') ? array_map('intval', $this->input('branch_ids')) : [],
            regionIds: $this->filled('region_ids') ? array_map('intval', $this->input('region_ids')) : [],
            assigneeIds: $this->filled('assignee_ids') ? array_map('intval', $this->input('assignee_ids')) : [],
            statuses: $this->filled('statuses') ? $this->input('statuses') : [],
            levels: $this->filled('levels') ? $this->input('levels') : [],
            facilityNames: $this->filled('facility_names') ? $this->input('facility_names') : [],
            exportType: $this->filled('export_type') ? $this->input('export_type') : null,
            search: $this->filled('search') ? $this->input('search') : null,
            meMode: $this->boolean('me_mode'),
            page: $this->filled('page') ? (int) $this->input('page') : 1,
            perPage: $this->filled('per_page') ? (int) $this->input('per_page') : 10,
            sortBy: $this->filled('sort_by') ? $this->input('sort_by') : 'admission_date',
            sortDir: $this->filled('sort_dir') ? $this->input('sort_dir') : 'desc',
            admissionDateFrom: $this->filled('admission_date_from') ? $this->toDateString($this->input('admission_date_from')) : null,
            admissionDateTo: $this->filled('admission_date_to') ? $this->toDateString($this->input('admission_date_to')) : null,
            // Explicit backend default so behavior never depends solely on the frontend sending this:
            // every encounter today is IP, so an absent filter still means IP, not "any type".
            encounterTypes: $this->filled('encounter_types') ? $this->input('encounter_types') : [EncounterTypeEnum::IP->value],
        );
    }

    private function toDateString(string $value): string
    {
        return Carbon::createFromFormat(config('vbc.date_format'), $value)->toDateString();
    }
}
