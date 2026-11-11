<?php

namespace App\VBC\Http\Resources\Hospitalization;

use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use Illuminate\Http\Resources\Json\JsonResource;

/** One row of the Triage "ED Visits" bucket (PD-4057). */
class EdVisitCollectionItemResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var HospitalizationTrackerModel $tracker */
        $tracker = $this->resource;
        $encounter = $tracker->encounter;

        return [
            'id' => $tracker->id,
            'patient_id' => $encounter->patient?->id,
            'patient_name' => $encounter->patient?->PatientName,
            'patient_account' => $encounter->patient?->PatientAccount,
            'level' => $tracker->icm_level?->label(),
            'tracker_id' => $tracker->id,
            'admission_date' => $encounter->admission_at?->format(config('vbc.date_time_format')),
            'follow_up_status' => $tracker->currentFollowup?->status->label(),
            'status' => $tracker->status->label(),
            'assignee_name' => $tracker->assignedTo?->name,
            'terminal_payer' => $encounter->patient?->CF_TerminalPayer,
            'branch' => $encounter->patient?->facility?->FacilityName,
            'is_overdue' => $this->isOverDue($tracker),
        ];
    }

    /**
     * AC18: admitted more than 24 hours ago. Deliberately independent of the ICM Hospitalization bucket's
     * rule (HospitalizationCollectionItemResource::isOverDue()), which uses expected discharge date for
     * In Progress trackers.
     */
    private function isOverDue(HospitalizationTrackerModel $tracker): bool
    {
        return $tracker->encounter->admission_at?->copy()->addHours(24)->isPast() ?? false;
    }
}
