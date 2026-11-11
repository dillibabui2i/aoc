<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The six Sick Plan Type categories, seeded as reference data the same way care_coordination_types is
 * (see 2026_07_28_100005_create_care_coordination_tables.php) — no spreadsheet was ever attached to the
 * story, so these come from the Figma reference table (only "Hydration"'s description is legible there,
 * the rest are illustrative placeholders pending the real source).
 */
return new class extends Migration {
    private const CATALOG = [
        'Hydration' => 'Encourage increased fluid intake',
        'Pain' => 'Administer prescribed pain management',
        'Seizure' => 'Follow seizure precaution protocol',
        'Respiratory' => 'Monitor respiratory status and support as needed',
        'Allergy' => 'Monitor for allergic reaction',
        'GI' => 'Monitor gastrointestinal symptoms',
    ];

    public function up(): void
    {
        DB::table('sick_plan_catalog')->insert(
            collect(self::CATALOG)->map(fn (string $description, string $domain) => [
                'domain' => $domain,
                'activity_code' => $domain,
                'description' => $description,
            ])->values()->all()
        );
    }

    public function down(): void
    {
        DB::table('sick_plan_catalog')->whereIn('domain', array_keys(self::CATALOG))->delete();
    }
};
