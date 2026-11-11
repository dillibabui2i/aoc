<?php

namespace App\VBC\Http\Resources\CareCoordination;

use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Models\CareCoordination\CareCoordinationModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The full editable payload for the "Care Coordination" modal — returned by show/store/update, so the
 * frontend can populate the form and decide read-only-ness from one call.
 */
class CareCoordinationResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var CareCoordinationModel $careCoordination */
        $careCoordination = $this->resource;
        $encounter = $careCoordination->encounter;
        $patient = $encounter->patient;
        // Already ordered newest-first by the eager load constraint in
        // CareCoordinationReadRepository::relationsWithLatestTracker().
        /** @var HospitalizationTrackerModel|null $tracker */
        $tracker = $encounter->hospitalizationTrackers->first();

        return [
            'id' => $careCoordination->id,
            'created_at' => $careCoordination->created_at?->format(config('vbc.date_time_format')),
            'hospitalization_tracker_id' => $tracker?->id,
            'tracker_status' => $tracker?->status?->label(),
            'is_editable' => $tracker?->status === HospitalizationTrackerStatusEnum::IN_PROGRESS,
            'patient_name' => $patient->PatientName ?? null,
            'patient_account' => $patient->PatientAccount ?? null,
            'facility_name' => $encounter->facility_name,
            'admission_date_time' => $encounter->admission_at?->format(config('vbc.date_time_format')),
            'care_coordination_type_id' => $careCoordination->care_coordination_type_id,
            'assigned_to_id' => $careCoordination->assigned_to_id,
            'due_at' => $careCoordination->due_at?->format(config('vbc.date_time_format')),
            'completed_at' => $careCoordination->completed_at?->format(config('vbc.date_time_format')),
            'notes' => $careCoordination->notes,
        ];
    }
}
