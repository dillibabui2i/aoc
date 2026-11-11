<?php

namespace Database\Seeders\VBC;

use App\VBC\Services\Hospitalization\HospitalizationTrackerActivityLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VBCSeeder extends Seeder
{
    /**
     * VBC data tables populated by the sub-seeders below. Cleared before seeding so
     * the seeder is idempotent (re-running yields a clean set instead of duplicates).
     * Reference data seeded by migrations (e.g. care_coordination_types) is NOT listed.
     */
    private const TABLES = [
        'hospitalization_tracker_followups',
        'care_coordinations',
        'hospitalization_tracker_properties',
        'hospitalization_tracker_custom_property_definitions',
        'hospitalization_tracker_trainings',
        'hospitalization_tracker_training_types',
        'hospitalization_tracker_training_sources',
        'patient_custom_properties',
        'patient_custom_property_definitions',
        'hospitalization_trackers',
        'encounters',
        'vaccine_doses',
        'immunization_trackers',
    ];

    public function run(): void
    {
        $this->truncate();

        $this->call([
            PatientCustomPropertyDefinitionSeeder::class,
            HospitalizationTrackerCustomPropertyDefinitionSeeder::class,
            HospitalizationTrackerTrainingTypeSeeder::class,
            HospitalizationTrackerTrainingSourceSeeder::class,
            EncounterSeeder::class,
            HospitalizationCollectionSeeder::class,
            HospitalizationTrackerPropertySeeder::class,
            PatientCustomPropertySeeder::class,
            CareCoordinationCollectionSeeder::class,
            FollowupCollectionSeeder::class,
            ImmunizationScheduleSeeder::class,
            CdcVaccineSeeder::class,
            ImmunizationTrackerSeeder::class,
        ]);
    }

    private function truncate(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (self::TABLES as $table) {
            DB::table($table)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Trackers write their Activity Log to the shared `notes` table, which the whole application
        // uses — so it is emptied by entity rather than truncated, or re-seeding would wipe every other
        // module's notes. No entries are seeded: the log is a record of edits, and inventing history for
        // trackers nobody has touched would be a lie the UI presents as fact.
        DB::table('notes')->where('entity', HospitalizationTrackerActivityLog::ENTITY)->delete();
    }
}
