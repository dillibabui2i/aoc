<?php

namespace App\Listeners\Adjustment;

use App\Enums\Adjustments\AdjustmentTypeEnum;
use App\Events\Adjustment\AdjustmentChanged;
use App\Models\Adjustment;
use App\Notes;
use App\Roles;
use App\User;
use Illuminate\Database\Eloquent\Relations\Relation;

class LogAdjustmentChanged
{
    public function handle(AdjustmentChanged $event): void
    {
        $logTemplate = '%s updated "%s" from "%s" to "%s"';
        $user = User::findOrFail($event->userId);
        $oldData = $event->oldData;
        $newData = $event->newData;
        $arrayOfChanges = [];

        for ($i = 1; $i <= 3; $i++) {
            $oldType = $oldData["level_{$i}_type"];
            $oldId   = $oldData["level_{$i}_id"];

            $newType = $newData["level_{$i}_type"];
            $newId   = $newData["level_{$i}_id"];

            if ($newType === $oldType && (int)$newId === (int)$oldId) {
                continue;
            }

            $oldName = $this->getOldDisplayName($oldData, $i);
            $newName = $this->getNewDisplayName($newType, $newId);

            $arrayOfChanges[] = sprintf($logTemplate,$user->name,"Approval Level {$i}",$oldName,$newName);
        }

        $oldRequires = boolval($oldData['requires_ops_review'] ?? false);
        $newRequires = boolval($newData['requires_ops_review'] ?? false);
        if ($oldRequires !== $newRequires) {
            $arrayOfChanges[] = sprintf(
                $logTemplate,
                $user->name,
                'Requires Ops Review?',
                $oldRequires ? 'Yes' : 'No',
                $newRequires ? 'Yes' : 'No'
            );
        }

        foreach ($arrayOfChanges as $message) {
            Notes::query()->create([
                'type'        => 'edit',
                'entity'      => 'adjustment-change',
                'entity_id'   => $oldData['id'],
                'notes'       => $message,
                'created_by'  => $user->id,
                'completed'   => 1,
            ]);
        }
    }

    private function getNewDisplayName(?string $type, ?int $id): string
    {
        if (empty($type) || empty($id)) {
            return 'None';
        }

        if ($type === AdjustmentTypeEnum::ROLE->value && $id === Adjustment::REGION_AR_DIRECTOR_ROLE_ID) {
            return 'Region AR Director';
        }

        if ($type === AdjustmentTypeEnum::ROLE->value) {
            $record = Roles::select('name')->find($id);
        } else {
            $record = User::select('name')->find($id);
        }

        return $record->name ?? 'None';
    }

    private function getOldDisplayName(array $oldData, int $level): string
    {
        $type = $oldData["level_{$level}_type"] ?? null;
        $id   = $oldData["level_{$level}_id"]   ?? null;

        if (empty($type) || empty($id)) {
            return 'None';
        }

        if ($type === AdjustmentTypeEnum::ROLE->value && (int)$id === Adjustment::REGION_AR_DIRECTOR_ROLE_ID) {
            return 'Region AR Director';
        }

        $relationKey = "level_{$level}";
        return $oldData[$relationKey]['name'] ?? 'None';
    }
}
