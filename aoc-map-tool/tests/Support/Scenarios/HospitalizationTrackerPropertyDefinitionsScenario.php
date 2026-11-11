<?php

namespace Tests\Support\Scenarios;

use Database\Seeders\VBC\HospitalizationTrackerCustomPropertyDefinitionSeeder;

/**
 * The hospitalization tracker EAV property definitions the VBC write path depends on.
 *
 * Backs App\VBC\Repositories\Hospitalization\HospitalizationWriteRepository::updateTrackerProperty(),
 * which looks a definition up by key and throws when it is missing (definitions are reference data,
 * never created on the fly). aoc_test is a schema-only clone, so none exist until a test asks for them.
 * Runs the same seeder the dev database uses, so the key list has a single source of truth:
 *
 * - every key declared by HospitalizationTrackerCustomPropertyDefinitionSeeder exists, active
 * - the seeder's default values are applied (booleans false, everything else null)
 *
 * Call once per test, inside the DatabaseTransactions rollback, so nothing leaks between tests.
 */
class HospitalizationTrackerPropertyDefinitionsScenario
{
    public function create(): void
    {
        (new HospitalizationTrackerCustomPropertyDefinitionSeeder())->run();
    }
}
