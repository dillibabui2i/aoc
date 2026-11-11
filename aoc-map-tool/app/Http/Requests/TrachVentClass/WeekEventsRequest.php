<?php

namespace App\Http\Requests\TrachVentClass;

use App\DTO\TrachVentClass\WeekEventsFilterDTO;
use App\Http\Requests\CoreRequest;

class WeekEventsRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'state' => ['required', 'string'],
            'date' => ['required', 'date'],
            'branch' => ['nullable', 'integer'],
            'search' => ['nullable', 'json'],
        ];
    }

    public function toDto(): WeekEventsFilterDTO
    {
        $data = $this->validated();
        $branchId = $data['branch'] ?? null;

        return new WeekEventsFilterDTO(
            state: $data['state'],
            classDate: $data['date'],
            branchId: $branchId !== null ? (int) $branchId : null,
            searchLabel: $this->searchLabel($data['search'] ?? null),
        );
    }

    private function searchLabel(?string $search): ?string
    {
        if ($search === null || $search === '') {
            return null;
        }

        $searchData = json_decode($search, true);
        $label = $searchData['label'] ?? null;

        return $label !== '' ? $label : null;
    }
}
