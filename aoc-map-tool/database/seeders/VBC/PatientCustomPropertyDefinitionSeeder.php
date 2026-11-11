<?php

namespace Database\Seeders\VBC;

use App\VBC\Models\PatientCustomProperty\PatientCustomPropertyDefinitionModel;
use Illuminate\Database\Seeder;

class PatientCustomPropertyDefinitionSeeder extends Seeder
{
    private const DEFINITIONS = [
        ['key' => 'backup_g_tube', 'name' => 'Backup G-tube?', 'default_value' => false],
        ['key' => 'backup_trach', 'name' => 'Backup trach?', 'default_value' => false],
        ['key' => 'high_utilizer', 'name' => 'High Utilizer?', 'default_value' => false],
        ['key' => 'annual_flu_vaccine', 'name' => 'Annual Flu Vaccine', 'default_value' => false],
        ['key' => 'covid_vaccine', 'name' => 'COVID Vaccine', 'default_value' => false],
        ['key' => 'rsv_vaccine', 'name' => 'RSV Vaccine', 'default_value' => false],
    ];

    public function run(): void
    {
        foreach (self::DEFINITIONS as $definition) {
            PatientCustomPropertyDefinitionModel::create($definition);
        }
    }
}
