<?php

namespace App\VBC\Http\Resources\Immunization;

use App\VBC\Models\VaccineDose\VaccineDoseModel;
use Illuminate\Http\Resources\Json\JsonResource;

class AllImmunizationCollectionItemResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var VaccineDoseModel $dose */
        $dose = $this->resource;
        $tracker = $dose->immunizationTracker;
        $patient = $tracker->patient;

        return [
            'id' => $dose->id,
            'patient_name' => $patient?->PatientName,
            'patient_account' => $patient?->PatientAccount,
            'branch' => $patient?->facility?->FacilityName,
            'payer' => $patient?->CF_TerminalPayer,
            'vaccine_name' => $tracker->vaccine?->cdc_full_name,
            'dose' => $dose->sequence_number,
            'earliest_date' => $dose->earliest_date->format(config('vbc.date_format')),
            'due_date' => $dose->due_date->format(config('vbc.date_format')),
            'status' => $dose->status->label(),
        ];
    }
}
