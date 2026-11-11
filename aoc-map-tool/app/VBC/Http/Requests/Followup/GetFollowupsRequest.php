<?php

namespace App\VBC\Http\Requests\Followup;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\Followup\FollowupFilterDTO;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFollowupStatusEnum;
use Illuminate\Validation\Rule;

class GetFollowupsRequest extends CoreRequest
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
            'statuses' => ['sometimes', 'array'],
            'statuses.*' => ['string', Rule::enum(HospitalizationTrackerFollowupStatusEnum::class)],
            'encounter_types' => ['sometimes', 'array'],
            'encounter_types.*' => ['string', Rule::enum(EncounterTypeEnum::class)],
            'search' => ['sometimes', 'string', 'max:255'],
            'me_mode' => ['sometimes', 'nullable', 'in:true,false,1,0'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['sometimes', 'string', Rule::in(FollowupFilterDTO::ALL_SORT_COLUMNS)],
            'sort_dir' => ['sometimes', 'in:asc,desc'],
        ];
    }

    public function toDto(): FollowupFilterDTO
    {
        return new FollowupFilterDTO(
            state: $this->filled('state') ? $this->input('state') : [],
            branchIds: $this->filled('branch_ids') ? array_map('intval', $this->input('branch_ids')) : [],
            regionIds: $this->filled('region_ids') ? array_map('intval', $this->input('region_ids')) : [],
            assigneeIds: $this->filled('assignee_ids') ? array_map('intval', $this->input('assignee_ids')) : [],
            statuses: $this->filled('statuses') ? $this->input('statuses') : [],
            // Explicit backend default: followups attach to trackers which are IP-only today, so an
            // absent filter still means IP, not "any type" (ED followups are a future flow).
            encounterTypes: $this->filled('encounter_types') ? $this->input('encounter_types') : [EncounterTypeEnum::IP->value],
            search: $this->filled('search') ? $this->input('search') : null,
            meMode: $this->boolean('me_mode'),
            page: $this->filled('page') ? (int) $this->input('page') : 1,
            perPage: $this->filled('per_page') ? (int) $this->input('per_page') : 10,
            sortBy: $this->filled('sort_by') ? $this->input('sort_by') : 'created_at',
            sortDir: $this->filled('sort_dir') ? $this->input('sort_dir') : 'desc',
        );
    }
}
