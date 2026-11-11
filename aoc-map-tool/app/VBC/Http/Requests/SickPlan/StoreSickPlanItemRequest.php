<?php

namespace App\VBC\Http\Requests\SickPlan;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\SickPlan\SickPlanItemDTO;
use Illuminate\Validation\Rule;

class StoreSickPlanItemRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'sickPlanType' => ['required', 'string', Rule::exists('sick_plan_catalog', 'domain')],
            'completed' => ['sometimes', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function toDto(): SickPlanItemDTO
    {
        return new SickPlanItemDTO(
            sickPlanType: $this->input('sickPlanType'),
            completed: $this->boolean('completed'),
            notes: $this->filled('notes') ? $this->input('notes') : null,
        );
    }
}
