<?php

namespace App\VBC\Repositories\Immunization;

use App\VBC\DTO\Immunization\ImmunizationScheduleFilterDTO;
use App\VBC\Enum\CdcVaccine\CdcVaccineStatusEnum;
use App\VBC\Models\CdcVaccine\CdcVaccineModel;
use App\VBC\Models\ImmunizationRecommendation\ImmunizationRecommendationModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;

class ImmunizationScheduleReadRepository
{
    public function __construct(
        private ImmunizationRecommendationModel $model,
        private CdcVaccineModel $vaccineModel,
    ) {
    }

    public function findForEdit(int $id): ?ImmunizationRecommendationModel
    {
        /** @var ImmunizationRecommendationModel|null $schedule */
        $schedule = $this->model->newQuery()->with(['vaccine', 'doseAges', 'recommendedFor'])->find($id);

        return $schedule;
    }

    /** Soft-deleted templates are ignored; inactive ones are not. */
    public function existsForVaccineAndState(int $cvxCode, string $state, ?int $ignoreId = null): bool
    {
        return $this->model->newQuery()
            ->where('vaccine_cvx_code', $cvxCode)
            ->where('originating_state', $state)
            ->when($ignoreId !== null, fn (Builder $query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    /**
     * Vaccines a template can be created for: Active ones (per the CDC) that belong to a category (the modal
     * picks category first).
     */
    public function getVaccineOptions(): SupportCollection
    {
        return $this->vaccineModel->newQuery()
            ->where('cdc_status', CdcVaccineStatusEnum::ACTIVE->value)
            ->whereNotNull('cvx_group_code')
            ->orderBy('cdc_full_name')
            ->get(['cvx_code', 'cdc_full_name', 'cvx_group_code']);
    }

    public function getPaginated(ImmunizationScheduleFilterDTO $dto): LengthAwarePaginator
    {
        return $this
            ->buildQuery($dto)
            ->paginate($dto->perPage, [$this->model->getTable() . '.*'], 'page', $dto->page);
    }

    private function buildQuery(ImmunizationScheduleFilterDTO $dto): Builder
    {
        // whereHas: a template whose vaccine is soft-deleted has no name to show and must not reach the resource.
        $query = $this->model->newQuery()
            ->whereHas('vaccine')
            ->with(['vaccine.group', 'doseAges']);
        $this->applySorting($query, $dto->sortBy, $dto->sortDir);
        $this->applyFilters($query, $dto);
        $this->applySearch($query, $dto->search);

        return $query;
    }

    /** Runs after applySorting: the group filter and the search use its `sort_vac` / `sort_grp` joins. */
    private function applyFilters(Builder $query, ImmunizationScheduleFilterDTO $dto): void
    {
        $table = $this->model->getTable();

        $query
            ->when(
                $dto->vaccineGroupCode !== null,
                fn (Builder $q) => $q->where('sort_vac.cvx_group_code', $dto->vaccineGroupCode)
            )
            ->when(
                $dto->vaccineCvxCode !== null,
                fn (Builder $q) => $q->where("{$table}.vaccine_cvx_code", $dto->vaccineCvxCode)
            )
            ->when(
                $dto->states !== [],
                fn (Builder $q) => $q->whereIn("{$table}.originating_state", $dto->states)
            )
            ->when(
                $dto->status !== null,
                fn (Builder $q) => $q->where("{$table}.status", $dto->status->value)
            );
    }

    private function applySearch(Builder $query, ?string $search): void
    {
        if ($search === null) {
            return;
        }

        $term = '%' . $search . '%';
        $query->where(function (Builder $q) use ($term) {
            $q->whereRaw('COALESCE(sort_grp.aoc_name, sort_grp.cdc_name) like ?', [$term])
                ->orWhere('sort_vac.cdc_full_name', 'like', $term);
        });
    }

    /**
     * Both sortable columns live on joined tables; the group is a LEFT JOIN (vaccine.cvx_group_code is nullable)
     * and rows without a category go last. Vaccine name is always the secondary key, `id` the final tiebreaker.
     */
    private function applySorting(Builder $query, string $sortBy, string $direction): void
    {
        $table = $this->model->getTable();
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        $category = 'COALESCE(sort_grp.aoc_name, sort_grp.cdc_name)';

        $query
            ->join('cdc_vaccines as sort_vac', 'sort_vac.cvx_code', '=', "{$table}.vaccine_cvx_code")
            ->leftJoin('cdc_vaccine_groups as sort_grp', function ($join) {
                $join->on('sort_grp.cvx_group_code', '=', 'sort_vac.cvx_group_code')
                    ->whereNull('sort_grp.deleted_at');
            });

        match ($sortBy) {
            ImmunizationScheduleFilterDTO::SORT_VACCINE_NAME => $query
                ->orderBy('sort_vac.cdc_full_name', $direction),
            default => $query
                ->orderByRaw("{$category} IS NULL")
                ->orderByRaw("{$category} {$direction}")
                ->orderBy('sort_vac.cdc_full_name', 'asc'),
        };

        $query->orderBy("{$table}.id");
    }
}
