<?php

namespace App\VBC\Http\Resources\Followup;

use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerFollowupModel;
use Illuminate\Http\Resources\Json\JsonResource;

/** The saved Follow-up record, as the Follow-up modal needs it (AC3-AC30, AC37-AC41). */
class FollowupResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var HospitalizationTrackerFollowupModel $followup */
        $followup = $this->resource;
        $tracker = $followup->hospitalizationTracker;
        $patient = $followup->patient;
        $encounter = $tracker?->encounter;

        return [
            'id' => $followup->id,
            'status' => $followup->status->value,
            'status_label' => $followup->status->label(),
            'hospitalization_tracker_id' => $followup->hospitalization_tracker_id,
            'patient_account' => $patient?->PatientAccount,
            'patient_name' => $patient?->PatientName,
            'facility_name' => $encounter?->facility_name,
            'admission_date_time' => $encounter?->admission_at?->format(config('vbc.date_time_format')),
            'level' => $tracker?->icm_level?->value,
            'level_label' => $tracker?->icm_level?->label(),
            'followup_date' => $followup->scheduled_at?->format(config('vbc.date_time_format')),
            'assigned_to_id' => $followup->assigned_to_id,
            'assigned_to_name' => $followup->assignedTo?->name,
            'completion_date' => $followup->completed_at?->format(config('vbc.date_time_format')),
            'completed_by_name' => $followup->completedBy?->name,
            'attempt_date' => $followup->attempted_at?->format(config('vbc.date_time_format')),
            'attempted_by_name' => $followup->attemptedBy?->name,
            'status_note' => $followup->status_note,
            'follow_ups_updates' => $followup->follow_ups_updates,
            'needs' => $followup->needs,
            'notes' => $followup->notes,
            'created_at' => $followup->created_at?->timestamp,
        ];
    }
}
