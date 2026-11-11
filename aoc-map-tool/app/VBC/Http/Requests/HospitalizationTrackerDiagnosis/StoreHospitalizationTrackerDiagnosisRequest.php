<?php

namespace App\VBC\Http\Requests\HospitalizationTrackerDiagnosis;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\Hospitalization\HospitalizationTrackerDiagnosisDTO;
use App\VBC\Enum\Diagnosis\DiagnosisClinicalStatusEnum;
use App\VBC\Enum\Diagnosis\DiagnosisRankEnum;
use App\VBC\Enum\Diagnosis\DiagnosisSystemEnum;
use App\VBC\Enum\Diagnosis\DiagnosisVerificationStatusEnum;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use Illuminate\Validation\Rule;

class StoreHospitalizationTrackerDiagnosisRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'icd_10_code' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'system' => ['required', Rule::enum(DiagnosisSystemEnum::class)],
            'rank' => ['required', Rule::enum(DiagnosisRankEnum::class)],
            'clinical_status' => ['required', Rule::enum(DiagnosisClinicalStatusEnum::class)],
            'verification_status' => ['required', Rule::enum(DiagnosisVerificationStatusEnum::class)],
            'notes' => ['nullable', 'string'],
            // Which viewer is asking (IP when omitted) — see HospitalizationTrackerDiagnosisController::findTracker().
            'encounter_type' => ['sometimes', 'string', Rule::enum(EncounterTypeEnum::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'icd_10_code' => 'Diagnosis Code',
            'description' => 'Diagnosis Code',
            'system' => 'System',
            'rank' => 'Rank',
            'clinical_status' => 'Clinical Status',
            'verification_status' => 'Verification Status',
            'notes' => 'Diagnosis Notes',
        ];
    }

    // "created_by_id" isn't a field on this request: always the current user, set by
    // HospitalizationTrackerDiagnosisService::create() — never client input.
    public function toDto(): HospitalizationTrackerDiagnosisDTO
    {
        return new HospitalizationTrackerDiagnosisDTO(
            icd10Code: $this->input('icd_10_code'),
            description: $this->input('description'),
            system: DiagnosisSystemEnum::from($this->input('system')),
            rank: DiagnosisRankEnum::from($this->input('rank')),
            clinicalStatus: DiagnosisClinicalStatusEnum::from($this->input('clinical_status')),
            verificationStatus: DiagnosisVerificationStatusEnum::from($this->input('verification_status')),
            notes: $this->filled('notes') ? $this->input('notes') : null,
        );
    }
}
