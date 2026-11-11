<?php

namespace Database\Seeders\VBC;

use App\MssqlPatients;
use App\VBC\Models\PatientCustomProperty\PatientCustomPropertyDefinitionModel;
use App\VBC\Models\PatientCustomProperty\PatientCustomPropertyModel;
use Illuminate\Database\Seeder;

/**
 * The client-profile toggles the Hospitalization Record header form shows: High Utilizer (which the
 * All Hospitalizations filter also matches on) and the two Backup ones. Only the first seeded patient
 * (see EncounterSeeder) has them on, so both states are represented — and since the form shows each
 * Backup toggle's "last updated" from the property row's own updated_at, seeded rows give it something
 * to display.
 */
class PatientCustomPropertySeeder extends Seeder
{
    /** Definition key => which of the seeded patients has it turned on, by index. */
    private const TOGGLES = [
        'high_utilizer' => [0],
        'backup_g_tube' => [0, 2],
        'backup_trach' => [0],
    ];

    public function run(): void
    {
        $definitionIds = PatientCustomPropertyDefinitionModel::pluck('id', 'key');
        $patientIds = MssqlPatients::query()->orderBy('id', 'desc')->limit(3)->pluck('id');

        foreach ($patientIds as $index => $patientId) {
            foreach (self::TOGGLES as $key => $enabledFor) {
                PatientCustomPropertyModel::create([
                    'patient_id' => $patientId,
                    'definition_id' => $definitionIds[$key],
                    'value' => in_array($index, $enabledFor, true),
                ]);
            }
        }
    }
}
