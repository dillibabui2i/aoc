<?php

namespace App\VBC\Http\Resources\SickPlan;

use App\VBC\Models\SickPlan\SickPlanModel;
use Illuminate\Http\Resources\Json\JsonResource;

class SickPlanResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var SickPlanModel $plan */
        $plan = $this->resource;

        return [
            'id' => $plan->id,
            'status' => $plan->status->label(),
            'items' => SickPlanItemResource::collection($plan->items),
        ];
    }
}
