<?php

namespace App\VBC\Http\Resources\Immunization;

use App\VBC\Enum\VaccineDose\VaccineDoseOutcomeEnum;
use App\VBC\Models\VaccineDose\VaccineDoseModel;
use Illuminate\Http\Resources\Json\JsonResource;

class ImmunizationBucketCollectionItemResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var VaccineDoseModel $dose */
        $dose = $this->resource;
        $tracker = $dose->immunizationTracker;
        $patient = $tracker->patient;
        // `outcome` is selected by ImmunizationReadRepository::getBucketPaginated().
        $outcome = VaccineDoseOutcomeEnum::from($dose->getAttribute('outcome'));

        return [
            'id' => $dose->id,
            'patient_id' => $patient?->id,
            'patient_name' => $patient?->PatientName,
            'patient_account' => $patient?->PatientAccount,
            'branch' => $patient?->facility?->FacilityName,
            'payer' => $patient?->CF_TerminalPayer,
            'vaccine_name' => $tracker->vaccine?->cdc_full_name,
            'dose' => $dose->sequence_number,
            'earliest_date' => $dose->earliest_date->format(config('vbc.date_format')),
            'due_date' => $dose->due_date->format(config('vbc.date_format')),
            'assignee_name' => $tracker->assignee?->name,
            'outcome' => $outcome->label(),
            'is_overdue' => $outcome === VaccineDoseOutcomeEnum::OVERDUE,
        ];
    }
}
