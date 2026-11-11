<?php

namespace App\VBC\Http\Resources\CareCoordination;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One "Patient" autocomplete suggestion for the Care Coordination create flow (AC5). Backed by
 * CareCoordinationReadRepository::searchEligibleClients()'s plain (non-Eloquent) row shape, not a model.
 */
class CareCoordinationEligibleClientResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'patient_id' => $this->resource->patient_id,
            'full_name' => $this->resource->full_name,
            'patient_account' => $this->resource->patient_account,
            'hospitalization_tracker_id' => $this->resource->hospitalization_tracker_id,
            'facility_name' => $this->resource->facility_name,
            'admission_date_time' => $this->resource->admission_date_time
                ? Carbon::createFromFormat('Y-m-d H:i:s', $this->resource->admission_date_time)->format(config('vbc.date_time_format'))
                : null,
            'tracker_status' => $this->resource->tracker_status,
        ];
    }
}
