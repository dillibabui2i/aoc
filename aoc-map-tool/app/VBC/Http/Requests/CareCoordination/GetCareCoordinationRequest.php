<?php

namespace App\VBC\Http\Requests\CareCoordination;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\CareCoordination\CareCoordinationFilterDTO;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use Illuminate\Validation\Rule;

class GetCareCoordinationRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'state' => ['sometimes', 'array'],
            'state.*' => ['string', 'max:50'],
            'branch_ids' => ['sometimes', 'array'],
            'branch_ids.*' => ['integer'],
            'region_ids' => ['sometimes', 'array'],
            'region_ids.*' => ['integer'],
            'assignee_ids' => ['sometimes', 'array'],
            'assignee_ids.*' => ['integer'],
            'status' => ['sometimes', 'nullable', 'in:true,false,1,0'],
            'search' => ['sometimes', 'string', 'max:255'],
            'me_mode' => ['sometimes', 'nullable', 'in:true,false,1,0'],
            'hospitalization_tracker_id' => ['sometimes', 'integer'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['sometimes', 'string', Rule::in(CareCoordinationFilterDTO::ALL_SORT_COLUMNS)],
            'sort_dir' => ['sometimes', 'in:asc,desc'],
            'encounter_type' => ['sometimes', 'nullable', Rule::enum(EncounterTypeEnum::class)],
        ];
    }

    public function toDto(): CareCoordinationFilterDTO
    {
        return new CareCoordinationFilterDTO(
            state: $this->filled('state') ? $this->input('state') : [],
            branchIds: $this->filled('branch_ids') ? array_map('intval', $this->input('branch_ids')) : [],
            regionIds: $this->filled('region_ids') ? array_map('intval', $this->input('region_ids')) : [],
            assigneeIds: $this->filled('assignee_ids') ? array_map('intval', $this->input('assignee_ids')) : [],
            isCompleted: $this->filled('status') ? $this->boolean('status') : null,
            search: $this->filled('search') ? $this->input('search') : null,
            meMode: $this->boolean('me_mode'),
            hospitalizationTrackerId: $this->filled('hospitalization_tracker_id') ? (int) $this->input('hospitalization_tracker_id') : null,
            page: $this->filled('page') ? (int) $this->input('page') : 1,
            perPage: $this->filled('per_page') ? (int) $this->input('per_page') : 10,
            sortBy: $this->filled('sort_by') ? $this->input('sort_by') : 'created_at',
            sortDir: $this->filled('sort_dir') ? $this->input('sort_dir') : 'desc',
            encounterType: $this->filled('encounter_type') ? EncounterTypeEnum::from($this->input('encounter_type')) : null,
        );
    }
}
