<?php

namespace Database\Seeders\VBC;

use App\MssqlPatients;
use App\VBC\Enum\Encounter\EncounterAdmissionTypeEnum;
use App\VBC\Enum\Encounter\EncounterHospitalizationTypeEnum;
use App\VBC\Enum\Encounter\EncounterStatusEnum;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Models\Encounter\EncounterModel;
use Illuminate\Database\Seeder;

class EncounterSeeder extends Seeder
{
    private const PER_PATIENT = 5;

    /** Rotated rather than fixed, so both values of the field are represented in seeded data. */
    private const HOSPITALIZATION_TYPES = [
        EncounterHospitalizationTypeEnum::ADMISSION,
        EncounterHospitalizationTypeEnum::READMISSION,
    ];

    public function run(): void
    {
        $patientIds = MssqlPatients::query()->orderBy('id', 'desc')->limit(3)->pluck('id');
        foreach ($patientIds as $patientId) {
            foreach (range(1, self::PER_PATIENT) as $i) {
                $admissionAt = now()->subDays(rand(1, 30));
                EncounterModel::create([
                    'patient_id' => $patientId,
                    'type' => EncounterTypeEnum::IP,
                    'hospitalization_type' => self::HOSPITALIZATION_TYPES[$i % count(self::HOSPITALIZATION_TYPES)],
                    'status' => EncounterStatusEnum::ADMITTED,
                    'facility_name' => "Seed Hospital #{$i}",
                    'admission_at' => $admissionAt,
                    'admission_type' => EncounterAdmissionTypeEnum::AVOIDABLE,
                    'admission_reason' => "Seed reason #{$i} for patient {$patientId}",
                    'expected_discharge_at' => $admissionAt->copy()->addDays(rand(3, 14)),
                ]);
            }
        }
    }
}
