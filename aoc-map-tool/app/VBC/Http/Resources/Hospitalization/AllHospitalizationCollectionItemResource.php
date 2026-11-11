<?php

namespace App\VBC\Http\Resources\Hospitalization;

use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use Illuminate\Http\Resources\Json\JsonResource;

class AllHospitalizationCollectionItemResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var HospitalizationTrackerModel $tracker */
        $tracker = $this->resource;
        $encounter = $tracker->encounter;

        return [
            'id' => $tracker->id,
            'patient_id' => $encounter->patient?->id,
            'patient_name' =>$encounter->patient?->PatientName,
            'patient_account' => $encounter->patient?->PatientAccount,
            'type' => $encounter->admission_type?->label(),
            'level' => $tracker->icm_level?->label(),
            'tracker_id' => $tracker->id,
            'admission_date' => $encounter->admission_at?->format(config('vbc.date_time_format')),
            'facility_name' => $encounter->facility_name,
            'discharge_date' => $encounter->discharged_at?->format(config('vbc.date_time_format')),
            'status' => $tracker->status->label(),
            'assignee_name' => $tracker->assignedTo?->name,
            'terminal_payer' => $encounter->patient?->CF_TerminalPayer,
            'branch' => $encounter->patient?->facility?->FacilityName,
            'last_follow_up' => $tracker->latestFollowup?->completed_at?->format(config('vbc.date_time_format')),
            'is_overdue' => $this->isOverDue($tracker),
        ];
    }

    private function isOverDue(HospitalizationTrackerModel $tracker): bool
    {
        return match ($tracker->status) {
            HospitalizationTrackerStatusEnum::OPEN => $tracker->encounter->admission_at?->copy()->addHours(24)->isPast() ?? false,
            HospitalizationTrackerStatusEnum::IN_PROGRESS => $tracker->encounter->expected_discharge_at !== null
                && now()->startOfDay()->gt($tracker->encounter->expected_discharge_at),
            default => false,
        };
    }
}
