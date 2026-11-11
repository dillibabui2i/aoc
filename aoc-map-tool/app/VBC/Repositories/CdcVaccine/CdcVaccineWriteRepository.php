<?php

namespace App\VBC\Repositories\CdcVaccine;

use App\VBC\DTO\CdcVaccine\CdcVaccineDTO;
use App\VBC\Models\CdcVaccine\CdcVaccineModel;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CdcVaccineWriteRepository
{
    public function __construct(private CdcVaccineModel $vaccineModel)
    {
    }

    /**
     * Upsert by cvx_code, restoring soft-deleted rows. Nothing is ever deleted.
     *
     * @param array<int, CdcVaccineDTO> $vaccines
     */
    public function upsertMany(array $vaccines): void
    {
        $this->vaccineModel->getConnection()->transaction(function () use ($vaccines): void {
            foreach (array_chunk($vaccines, 200) as $chunk) {
                $existing = $this->vaccineModel->newQuery()->withoutGlobalScope(SoftDeletingScope::class)
                    ->whereIn('cvx_code', array_map(fn (CdcVaccineDTO $v) => $v->cvxCode, $chunk))
                    ->get()
                    ->keyBy('cvx_code');

                foreach ($chunk as $dto) {
                    /** @var CdcVaccineModel $model */
                    $model = $existing->get($dto->cvxCode)
                        ?? $this->vaccineModel->newInstance(['cvx_code' => $dto->cvxCode]);

                    $model->fill([
                        'cdc_full_name' => $dto->fullName,
                        'cdc_short_description' => $dto->shortDescription,
                        'cdc_status' => $dto->status,
                        'cdc_last_updated' => $dto->lastUpdated,
                        'cvx_group_code' => $dto->cvxGroupCode,
                    ]);

                    if ($model->trashed()) {
                        $model->restore();
                    }
                    $model->save();
                }
            }
        });
    }
}
