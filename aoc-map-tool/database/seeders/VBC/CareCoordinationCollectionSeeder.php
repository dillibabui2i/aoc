<?php

namespace Database\Seeders\VBC;

use App\VBC\Models\CareCoordination\CareCoordinationModel;
use App\VBC\Models\CareCoordination\CareCoordinationTypeModel;
use App\VBC\Models\Encounter\EncounterModel;
use Illuminate\Database\Seeder;

class CareCoordinationCollectionSeeder extends Seeder
{
    private const PER_ENCOUNTER = 3;
    private array $assigneeIds = [1, 11];
    private array $typeKeys = ['branch_ccm', 'triage_team', 'md'];

    public function run(): void
    {
        $typeIds = CareCoordinationTypeModel::query()
            ->whereIn('key', $this->typeKeys)
            ->pluck('id')
            ->all();
        $encounters = EncounterModel::all();
        foreach ($encounters as $index => $encounter) {
            foreach (range(0, self::PER_ENCOUNTER - 1) as $i) {
                $isCompleted = $i === 0;
                CareCoordinationModel::create([
                    'encounter_id' => $encounter->id,
                    'care_coordination_type_id' => $typeIds[$i % count($typeIds)],
                    'assigned_to_id' => $this->assigneeIds[$index % count($this->assigneeIds)],
                    'due_at' => now()->addDays(rand(1, 14)),
                    'completed_at' => $isCompleted ? now()->subDays(rand(1, 10)) : null,
                    'notes' => "Seed note #{$i} for encounter {$encounter->id}",
                ]);
            }
        }
    }
}
