<?php

namespace App\VBC\Repositories\HospitalizationTrackerDiagnosis;

use App\VBC\DTO\Hospitalization\HospitalizationTrackerDiagnosisDTO;
use App\VBC\Models\EncounterDiagnosis\EncounterDiagnosisModel;

class HospitalizationTrackerDiagnosisWriteRepository
{
    public function __construct(private EncounterDiagnosisModel $diagnosisModel)
    {
    }

    public function create(
        int $encounterId,
        HospitalizationTrackerDiagnosisDTO $dto,
        int $createdById,
    ): EncounterDiagnosisModel {
        /** @var EncounterDiagnosisModel $diagnosis */
        $diagnosis = $this->diagnosisModel->newQuery()->create([
            'encounter_id' => $encounterId,
            // Stamped now, same as `created_by_id` — never client input, never touched on edit.
            'created_by_id' => $createdById,
            'icd_10_code' => $dto->icd10Code,
            'description' => $dto->description,
            'system' => $dto->system,
            'rank' => $dto->rank,
            'clinical_status' => $dto->clinicalStatus,
            'verification_status' => $dto->verificationStatus,
            'notes' => $dto->notes,
        ]);

        return $diagnosis;
    }

    // `created_by_id` is deliberately absent here — immutable once set at creation. `system` is
    // updatable (required, validated field — see StoreHospitalizationTrackerDiagnosisRequest), unlike
    // created_by_id.
    public function update(
        EncounterDiagnosisModel $diagnosis,
        HospitalizationTrackerDiagnosisDTO $dto,
    ): EncounterDiagnosisModel {
        $diagnosis->update([
            'icd_10_code' => $dto->icd10Code,
            'description' => $dto->description,
            'system' => $dto->system,
            'rank' => $dto->rank,
            'clinical_status' => $dto->clinicalStatus,
            'verification_status' => $dto->verificationStatus,
            'notes' => $dto->notes,
        ]);

        return $diagnosis;
    }
}
