<?php

namespace App\Services\Adjustment;

use App\Events\Adjustment\AdjustmentChanged;
use App\Events\Adjustment\AdjustmentCreated;
use App\Helpers\H;
use App\Models\Adjustment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Notes;

class AdjustmentService
{
    public function __construct()
    {
    }

    public function save(array $newData): string
    {
        $user = H::getLoggedInUser();

        for ($i = 1; $i <= 3; $i++) {
            $temp = explode(':', $newData["approval_level_{$i}"]);

            $newData["level_{$i}_type"] = $temp[0] ?: null;
            $newData["level_{$i}_id"] = $temp[1] ?? null;
            unset($newData["approval_level_{$i}"]);
        }

        if (!empty($newData['id'])) {
            $adjustment = Adjustment::with(['level_1:id,name', 'level_2:id,name', 'level_3:id,name'])
                ->findOrFail($newData['id']);
            $oldData = $adjustment->toArray();

            $adjustment->update($newData);


            event(
                new AdjustmentChanged(
                    oldData: $oldData,
                    newData: $newData,
                    userId: $user['id'],
                )
            );

            return 'Adjustment updated!';
        }

        Adjustment::query()->create($newData);

        return 'Adjustment added!';
    }

    public function getAdjustmentActivityNotes(...$arguments)
    {
        $arg = $arguments;
        $notes = Notes::select('notes.*', 'users.name')->where(function ($query) use ($arg) {
            foreach ($arg as $argument) {
                $query->orWhere(function ($query) use ($argument) {
                    foreach ($argument as $key => $value) {
                        $query->where($key, $value);
                    }
                });
            }
        })->leftJoin('users', function ($join) {
            $join->on('notes.created_by', '=', 'users.id');
        })->orderBy('created_at', 'DESC')->get();

        return $notes->reverse();
    }

    public function getAvailableCategories(): array
    {
        return Adjustment::query()
            ->orderBy('category')
            ->pluck('category', 'id')
            ->toArray();
    }

    public function applySearch(Builder $query, ?string $searchValue): Builder
    {
        if (isset($searchValue) && $searchValue != '') {
            $searchValue = '%' . $searchValue . '%';

            $query->where(function ($q) use ($searchValue) {
                $q->where('category', 'like', $searchValue)
                    ->orWhereRaw($this->getLevelDisplayNameExpression(1) . " LIKE ?", [$searchValue]
                    )   // 1st level of approval
                    ->orWhereRaw($this->getLevelDisplayNameExpression(2) . " LIKE ?", [$searchValue]
                    )   // 2st level of approval
                    ->orWhereRaw($this->getLevelDisplayNameExpression(3) . " LIKE ?", [$searchValue]
                    );  // 3st level of approval
            });
        }

        return $query;
    }

    public function applyOrderBy(Builder $query, ?array $orderData, array $columns): Builder
    {
        if (isset($orderData) && $orderData != '') {
            $columnName = $columns[intval($orderData['column'])];
            $colIndex = intval($orderData['column']);

            $direction = strtoupper($orderData['dir'] ?? 'ASC');

            if (in_array($columnName, ['level_1_name', 'level_2_name', 'level_3_name'])) {
                $expression = $this->getLevelDisplayNameExpression($colIndex);
                $query->orderByRaw("{$expression} {$direction}");
            } else {
                $query->orderBy($columnName, $direction);
            }
        }

        return $query;
    }

    private function getLevelDisplayNameExpression(int $levelNum): string
    {
        $morph = "level_{$levelNum}";
        $specialId = Adjustment::REGION_AR_DIRECTOR_ROLE_ID;

        return "
            CASE
                WHEN {$morph}_type = 'role' AND {$morph}_id = {$specialId}
                    THEN 'Region AR Director'
                WHEN {$morph}_type = 'role'
                    THEN (SELECT `name` FROM `roles` WHERE `id` = {$morph}_id)
                WHEN {$morph}_type = 'user'
                    THEN (SELECT `name` FROM `users` WHERE `id` = {$morph}_id)
                ELSE ''
            END
        ";
    }

    public function getUsersForDropdowns(): array
    {
        return collect(H::getHelper('ConstantHelper')->getUsersByRoles([
            'Director of Billing Compliance',
            'Director of Revenue Cycle Management',
            'VP of Revenue Cycle Management',
        ]))->mapWithKeys(function ($user) {
            return ["user:{$user['value']}" => $user['text']];
        })->toArray();
    }
}
