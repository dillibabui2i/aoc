<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SECTIONS = [
        'employment' => ['employee', 'job_title', 'direct_report', 'effective_date'],
        'compensation' => [
            'evaluation_and_intake', 're_evaluation', 'routine_visit', 'supervision_visit',
            're_evaluation_and_supervision_visit', 'meetings',
            '1_unit_visit', '2_unit_visit', '3_unit_visit', '4_unit_visit',
        ],
        'caseload' => ['visits_per_week', 'territory'],
        // Retired: superseded by the dynamic 'sign_on_bonus' compensation item. Rows are kept
        // (section = null) so historical offer_values for already-sent offers remain readable.
        null => ['bonus', '10_visits', '20_visits', '30_visits'],
    ];

    public function up(): void
    {
        Schema::table('offer_fields', function (Blueprint $table) {
            $table->string('section')->nullable()->after('label');
        });

        foreach (self::SECTIONS as $section => $keys) {
            DB::table('offer_fields')->whereIn('key', $keys)->update(['section' => $section]);
        }

        $now = now();

        DB::table('offer_fields')->updateOrInsert(
            ['key' => 'sign_on_bonus'],
            [
                'label' => 'Sign-On Bonus',
                'value_type' => 'decimal',
                'section' => 'compensation',
                'created_at' => $now,
                'updated_at' => $now,
                'last_modified_at' => $now,
            ]
        );

        DB::table('offer_fields')->updateOrInsert(
            ['key' => 'other'],
            [
                'label' => 'Other',
                'value_type' => 'string',
                'section' => 'compensation',
                'created_at' => $now,
                'updated_at' => $now,
                'last_modified_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        DB::table('offer_fields')->whereIn('key', ['sign_on_bonus', 'other'])->delete();

        Schema::table('offer_fields', function (Blueprint $table) {
            $table->dropColumn('section');
        });
    }
};
