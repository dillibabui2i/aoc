<?php

namespace App\VBC\Http\Resources\Hospitalization;

use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use Illuminate\Http\Resources\Json\JsonResource;

/** One row of the "Hospitalization History" table: a previous hospitalization of a transfer chain. */
class HospitalizationTrackerHistoryResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var HospitalizationTrackerModel $tracker */
        $tracker = $this->resource;
        $encounter = $tracker->encounter;
        $dateFormat = config('vbc.date_format');

        return [
            'tracker_id' => $tracker->id,
            'encounter_type' => $encounter->type->value,
            'date' => $encounter->admission_at?->format($dateFormat),
            'event' => $encounter->type->eventLabel(),
            'facility_name' => $encounter->facility_name,
            'discharged_at' => $encounter->discharged_at?->format($dateFormat),
        ];
    }
}
