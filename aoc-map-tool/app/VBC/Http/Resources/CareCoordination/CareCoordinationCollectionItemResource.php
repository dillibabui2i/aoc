<?php

namespace App\VBC\Http\Resources\CareCoordination;

use App\VBC\Models\CareCoordination\CareCoordinationModel;
use Illuminate\Http\Resources\Json\JsonResource;

class CareCoordinationCollectionItemResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var CareCoordinationModel $careCoordination */
        $careCoordination = $this->resource;
        $encounter = $careCoordination->encounter;

        return [
            'id' => $careCoordination->id,
            'created_at' => $careCoordination->created_at?->format(config('vbc.date_time_format')),
            'patient_id' => $encounter->patient->id ?? null,
            'patient_name' => $encounter->patient->PatientName ?? null,
            'patient_account' => $encounter->patient->PatientAccount ?? null,
            'hospital_name' => $encounter->facility_name,
            'coordination_type' => $careCoordination->type?->name,

            //TODO need to clarify the source of that field, maybe its ReferringProviders table.
            'provider' => null,

            'assignee_name' => $careCoordination->assignedTo->name ?? null,
            'due_date' => $careCoordination->due_at?->format(config('vbc.date_time_format')),
            'is_overdue' => $careCoordination->due_at?->isPast() ?? false,
            'is_completed' => (bool) $careCoordination->completed_at,
        ];
    }
}
