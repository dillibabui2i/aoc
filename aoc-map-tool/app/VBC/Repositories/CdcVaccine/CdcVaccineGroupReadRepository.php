<?php

namespace App\VBC\Repositories\CdcVaccine;

use App\VBC\Enum\CdcVaccine\CdcVaccineStatusEnum;
use App\VBC\Models\CdcVaccine\CdcVaccineGroupModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class CdcVaccineGroupReadRepository
{
    public function __construct(private CdcVaccineGroupModel $groupModel)
    {
    }

    /** Categories for the schedule modal and filters: only those that have at least one Active vaccine to pick. */
    public function getOptions(): SupportCollection
    {
        return $this->groupModel->newQuery()
            ->whereHas('vaccines', fn (Builder $query) => $query->where('cdc_status', CdcVaccineStatusEnum::ACTIVE->value))
            ->orderByRaw('COALESCE(aoc_name, cdc_name)')
            ->get(['cvx_group_code', 'aoc_name', 'cdc_name']);
    }

    /**
     * @return array<int, int> non-deleted cvx_group_code values
     */
    public function existingGroupCodes(): array
    {
        return $this->groupModel->newQuery()->pluck('cvx_group_code')->map(fn ($code) => (int) $code)->all();
    }

    /**
     * Non-deleted groups whose aoc_name is NULL, empty or whitespace-only.
     *
     * @return Collection
     */
    public function getWithoutAocName(): Collection
    {
        return $this->groupModel->newQuery()
            ->where(function (Builder $query): void {
                $query->whereNull('aoc_name')->orWhereRaw("TRIM(aoc_name) = ''");
            })
            ->orderBy('cvx_group_code')
            ->get();
    }
}
