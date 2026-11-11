<?php

namespace App\VBC\Repositories\CdcVaccine;

use App\VBC\DTO\CdcVaccine\CdcVaccineGroupDTO;
use App\VBC\Models\CdcVaccine\CdcVaccineGroupModel;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CdcVaccineGroupWriteRepository
{
    public function __construct(private CdcVaccineGroupModel $groupModel)
    {
    }

    /**
     * Upsert by cvx_group_code, restoring soft-deleted rows. Only cdc_name is written:
     * aoc_name is maintained manually and is never touched (not even on insert).
     *
     * @param array<int, CdcVaccineGroupDTO> $groups
     */
    public function upsertMany(array $groups): void
    {
        $this->groupModel->getConnection()->transaction(function () use ($groups): void {
            foreach (array_chunk($groups, 200) as $chunk) {
                $existing = $this->groupModel->newQuery()->withoutGlobalScope(SoftDeletingScope::class)
                    ->whereIn('cvx_group_code', array_map(fn (CdcVaccineGroupDTO $g) => $g->cvxGroupCode, $chunk))
                    ->get()
                    ->keyBy('cvx_group_code');

                foreach ($chunk as $dto) {
                    /** @var CdcVaccineGroupModel $model */
                    $model = $existing->get($dto->cvxGroupCode)
                        ?? $this->groupModel->newInstance(['cvx_group_code' => $dto->cvxGroupCode]);

                    $model->cdc_name = $dto->cdcName;

                    if ($model->trashed()) {
                        $model->restore();
                    }
                    $model->save();
                }
            }
        });
    }
}
