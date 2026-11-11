<?php

namespace App\VBC\Http\Resources\SickPlan;

use App\VBC\Models\SickPlan\SickPlanItemModel;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Field names match what SickPlanTab.vue/SickPlanDetailsModal.vue already read (camelCase, not this
 * module's usual snake_case wire contract) — those components predate the real backend and were built
 * against a mock shaped this way; matching it here avoids changing already-working, reviewed components.
 */
class SickPlanItemResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var SickPlanItemModel $item */
        $item = $this->resource;

        return [
            'id' => $item->id,
            'sickPlanType' => $item->catalog->domain,
            'completed' => $item->completed,
            'completedBy' => $item->completedBy?->name,
            'notes' => $item->notes,
            'creationDate' => $item->created_at?->format(config('vbc.date_time_format')),
            'author' => $item->author?->name,
        ];
    }
}
