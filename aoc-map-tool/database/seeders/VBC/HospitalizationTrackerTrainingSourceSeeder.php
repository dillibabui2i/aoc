<?php

namespace Database\Seeders\VBC;

use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerTrainingSourceModel;
use Illuminate\Database\Seeder;

/** List confirmed by the team lead for PD-???? (Education Source dropdown). */
class HospitalizationTrackerTrainingSourceSeeder extends Seeder
{
    private const SOURCES = [
        'AOC Clinical Excellence',
        'AHRQ – Agency for Healthcare Research and Quality',
        'CMS – Centers for Medicare & Medicaid Services',
        'MedlinePlus / NIH',
        'CDC',
        'FDA',
        'Clinical specialty societies',
        'Evidence-based guidelines',
        'Hospital/Health System materials',
    ];

    public function run(): void
    {
        foreach (self::SOURCES as $name) {
            HospitalizationTrackerTrainingSourceModel::create(['name' => $name]);
        }
    }
}
