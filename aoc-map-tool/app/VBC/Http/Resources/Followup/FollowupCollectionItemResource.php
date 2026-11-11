<?php

namespace App\VBC\Http\Resources\Followup;

use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerFollowupModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowupCollectionItemResource extends JsonResource
{
    private const ACTIVE_STATUSES = [
        HospitalizationTrackerStatusEnum::OPEN,
        HospitalizationTrackerStatusEnum::IN_PROGRESS,
        HospitalizationTrackerStatusEnum::READY_FOR_REVIEW,
        HospitalizationTrackerStatusEnum::READY_FOR_NEXT_STEPS,
    ];

    public function toArray($request): array
    {
        /** @var HospitalizationTrackerFollowupModel $followup */
        $followup = $this->resource;
        /** @var HospitalizationTrackerModel|null $tracker */
        $tracker = $followup->hospitalizationTracker ?? $followup->patientHospitalizationTrackers->first();
        $isActive = $tracker !== null
            && in_array($tracker->status, self::ACTIVE_STATUSES);

        return [
            'id' => $followup->id,
            'patient_id' => $followup->patient?->id,
            'patient_name' => $followup->patient?->PatientName,
            'patient_account' => $followup->patient?->PatientAccount,
            'status' => $followup->status->label(),
            'hospitalization_level' => $isActive ? $tracker->icm_level?->label() : null,
            'is_hospitalized' => $isActive,
            'hospitalization_tracker_id' => $isActive ? $tracker->id : null,
            'hospitalization_status' => $isActive ? $tracker->status->label() : null,
            'facility_name' => $followup->patient?->facility?->FacilityName,
            'assignee_name' => $followup->assignedTo?->name,
            'followup_date' => $followup->scheduled_at?->format(config('vbc.date_format')),
            'is_overdue' => $this->isOverDue($followup),
        ];
    }

    private function isOverDue(HospitalizationTrackerFollowupModel $followup): bool
    {
        return $followup->scheduled_at?->copy()->addDays(2)->isPast() ?? false;
    }
}
