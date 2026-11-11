<?php

namespace App\Http\Requests\TrachVentClass;

use App\DTO\TrachVentClass\ClinicianEnrollmentDTO;
use App\Http\Requests\CoreRequest;

class ClinicianEnrollmentRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'clinician_type' => ['required', 'in:' . ClinicianEnrollmentDTO::CLINICIAN_TYPE_CURRENT . ',' . ClinicianEnrollmentDTO::CLINICIAN_TYPE_NEW],
            'provider_id' => ['required_if:clinician_type,' . ClinicianEnrollmentDTO::CLINICIAN_TYPE_CURRENT, 'integer'],
            'candidate_id' => ['required_if:clinician_type,' . ClinicianEnrollmentDTO::CLINICIAN_TYPE_NEW, 'integer'],
            'discipline' => ['nullable', 'string', 'max:100'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'start_date' => ['nullable', 'date'],
            'client_id' => ['nullable', 'integer'],
        ];
    }

    public function toDTO(): ClinicianEnrollmentDTO
    {
        $data = $this->validated();

        return new ClinicianEnrollmentDTO(
            clinicianType: $data['clinician_type'],
            providerId: isset($data['provider_id']) ? (int) $data['provider_id'] : null,
            candidateId: isset($data['candidate_id']) ? (int) $data['candidate_id'] : null,
            clientId: isset($data['client_id']) ? (int) $data['client_id'] : null,
            discipline: $data['discipline'] ?? null,
            phoneNumber: $data['phone_number'] ?? null,
            startDate: formatDateForDb($data['start_date'] ?? null),
        );
    }
}
