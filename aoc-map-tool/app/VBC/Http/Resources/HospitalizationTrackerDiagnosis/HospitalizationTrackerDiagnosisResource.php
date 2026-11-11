<?php

namespace App\VBC\Http\Resources\HospitalizationTrackerDiagnosis;

use App\VBC\Models\EncounterDiagnosis\EncounterDiagnosisModel;
use Illuminate\Http\Resources\Json\JsonResource;

class HospitalizationTrackerDiagnosisResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var EncounterDiagnosisModel $diagnosis */
        $diagnosis = $this->resource;

        return [
            'id' => $diagnosis->id,
            'icd_10_code' => $diagnosis->icd_10_code,
            'description' => $diagnosis->description,
            'system' => $diagnosis->system->value,
            'system_label' => $diagnosis->system->label(),
            'rank' => $diagnosis->rank->value,
            'rank_label' => $diagnosis->rank->label(),
            'clinical_status' => $diagnosis->clinical_status->value,
            'clinical_status_label' => $diagnosis->clinical_status->label(),
            'verification_status' => $diagnosis->verification_status->value,
            'verification_status_label' => $diagnosis->verification_status->label(),
            'notes' => $diagnosis->notes,
            'created_at' => $diagnosis->created_at?->format(config('vbc.date_time_format')),
            'author' => $diagnosis->createdBy?->name,
        ];
    }
}
