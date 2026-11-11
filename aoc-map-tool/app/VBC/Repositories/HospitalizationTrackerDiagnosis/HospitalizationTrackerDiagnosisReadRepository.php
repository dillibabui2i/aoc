<?php

namespace App\VBC\Repositories\HospitalizationTrackerDiagnosis;

use App\VBC\Models\EncounterDiagnosis\EncounterDiagnosisModel;

class HospitalizationTrackerDiagnosisReadRepository
{
    public function __construct(private EncounterDiagnosisModel $diagnosisModel)
    {
    }

    public function findById(int $id): ?EncounterDiagnosisModel
    {
        /** @var EncounterDiagnosisModel|null $diagnosis */
        $diagnosis = $this->diagnosisModel->newQuery()->with('createdBy')->find($id);

        return $diagnosis;
    }
}
