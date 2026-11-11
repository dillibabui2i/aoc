<?php

namespace App\VBC\Http\Resources\Followup;

use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One "Hospitalized Client Name" autocomplete suggestion (AC4/AC5): the patient's most recent
 * hospitalization tracker that is not Closed, with everything the Follow-up modal auto-populates once
 * selected.
 */
class FollowupClientSearchResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var HospitalizationTrackerModel $tracker */
        $tracker = $this->resource;
        $patient = $tracker->encounter->patient;

        return [
            'patient_id' => $patient->id,
            'patient_account' => $patient->PatientAccount,
            'patient_name' => $patient->PatientName,
            'hospitalization_tracker_id' => $tracker->id,
            'facility_name' => $tracker->encounter->facility_name,
            'admission_date_time' => $tracker->encounter->admission_at?->format(config('vbc.date_time_format')),
            'level' => $tracker->icm_level?->value,
            'level_label' => $tracker->icm_level?->label(),
        ];
    }
}
