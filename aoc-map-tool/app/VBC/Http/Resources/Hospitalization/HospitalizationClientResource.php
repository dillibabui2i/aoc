<?php

namespace App\VBC\Http\Resources\Hospitalization;

use App\MssqlPatients;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One autocomplete suggestion for the "Client Name" field. patient_id is the internal id the create
 * endpoint expects; client_id is the account number the user actually recognises, and is shown next to
 * the name so identically-named clients stay distinguishable.
 */
class HospitalizationClientResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var MssqlPatients $patient */
        $patient = $this->resource;

        return [
            'patient_id' => $patient->id,
            'patient_name' => $patient->PatientName,
            'client_id' => $patient->PatientAccount,
        ];
    }
}
