<?php

namespace App\VBC\Repositories\Immunization;

use App\MssqlPatients;
use App\VBC\DTO\Immunization\ImmunizationFilterDTO;
use App\VBC\Enum\VaccineDose\VaccineDoseOutcomeEnum;
use App\VBC\Enum\VaccineDose\VaccineDoseStatusEnum;
use App\VBC\Models\CdcVaccine\CdcVaccineModel;
use App\VBC\Models\VaccineDose\VaccineDoseModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\LazyCollection;

class ImmunizationReadRepository
{
    /** The dashboard bucket only surfaces doses that still need to be given. */
    private const BUCKET_STATUSES = [VaccineDoseStatusEnum::PLANNED];

    public function __construct(
        private VaccineDoseModel $vaccineDoseModel,
        private CdcVaccineModel $cdcVaccineModel,
        private MssqlPatients $mssqlPatients,
    ) {
    }

    public function getPaginated(ImmunizationFilterDTO $dto): LengthAwarePaginator
    {
        return $this
            ->buildQuery($dto)
            ->paginate($dto->perPage, [$this->vaccineDoseModel->getTable() . '.*'], 'page', $dto->page);
    }

    /** Dashboard bucket page: the same query plus the computed `outcome` (display status) of every dose. */
    public function getBucketPaginated(ImmunizationFilterDTO $dto): LengthAwarePaginator
    {
        $table = $this->vaccineDoseModel->getTable();
        [$outcomeSql, $outcomeBindings] = $this->outcomeExpression();

        return $this
            ->buildQuery($dto)
            ->whereIn("{$table}.status", self::BUCKET_STATUSES)
            ->with('immunizationTracker.assignee')
            ->select("{$table}.*")
            ->selectRaw("({$outcomeSql}) as outcome", $outcomeBindings)
            ->paginate($dto->perPage, ["{$table}.*"], 'page', $dto->page);
    }

    /** All rows matching the current filters/search, unpaginated, for CSV export. Chunked rather than ->get() so
     * exporting a large dataset doesn't load it all into memory at once; ->lazy() (unlike ->cursor()) still
     * eager-loads the relations, and keeps the requested sort order. */
    public function getAllForExport(ImmunizationFilterDTO $dto): LazyCollection
    {
        return $this
            ->buildQuery($dto)
            ->select($this->vaccineDoseModel->getTable() . '.*')
            ->lazy(500);
    }

    /** Every vaccine in the CDC list — the "Vaccine Name" filter options. */
    public function getVaccineOptions(): SupportCollection
    {
        return $this->cdcVaccineModel->newQuery()
            ->orderBy('cdc_full_name')
            ->get(['cvx_code', 'cdc_full_name']);
    }

    /** Every payer present on a current patient record — the "Payer" filter options. */
    public function getPayerOptions(): SupportCollection
    {
        return $this->mssqlPatients->newQuery()
            ->where('IsLatestAdmit', true)
            ->whereNotNull('CF_TerminalPayer')
            ->where('CF_TerminalPayer', '!=', '')
            ->distinct()
            ->orderBy('CF_TerminalPayer')
            ->pluck('CF_TerminalPayer');
    }

    private function buildQuery(ImmunizationFilterDTO $dto): Builder
    {
        // whereHas: a dose of a soft-deleted tracker has nothing to show (no patient, no vaccine) and must not
        // reach the resource, which reads the tracker unconditionally.
        $query = $this->vaccineDoseModel->newQuery()
            ->whereHas('immunizationTracker')
            ->with(['immunizationTracker.patient.facility', 'immunizationTracker.vaccine']);
        $this->applyFilters($query, $dto);
        $this->applySearch($query, $dto->search);
        $this->applySorting($query, $dto->sortBy, $dto->sortDir);

        return $query;
    }

    private function applyFilters(Builder $query, ImmunizationFilterDTO $dto): void
    {
        if (!empty($dto->state)) {
            $query->whereHas(
                'immunizationTracker.patient',
                fn (Builder $q) => $q->whereIn('Patient_State', $dto->state)
            );
        }

        if (!empty($dto->branchIds)) {
            $query->whereHas(
                'immunizationTracker.patient.facility',
                fn (Builder $q) => $q->whereIn('id', $dto->branchIds)
            );
        }

        if (!empty($dto->payers)) {
            $query->whereHas(
                'immunizationTracker.patient',
                fn (Builder $q) => $q->whereIn('CF_TerminalPayer', $dto->payers)
            );
        }

        if (!empty($dto->vaccineCvxCodes)) {
            $query->whereHas(
                'immunizationTracker',
                fn (Builder $q) => $q->whereIn('vaccine_cvx_code', $dto->vaccineCvxCodes)
            );
        }

        if (!empty($dto->statuses)) {
            $query->whereIn($this->vaccineDoseModel->getTable() . '.status', $dto->statuses);
        }

        if ($dto->meMode) {
            $query->whereHas(
                'immunizationTracker',
                fn (Builder $q) => $q->where('assignee_id', auth()->id())
            );
        }

        if (!empty($dto->outcomes)) {
            [$outcomeSql, $outcomeBindings] = $this->outcomeExpression();
            $placeholders = implode(',', array_fill(0, count($dto->outcomes), '?'));
            $query->whereRaw("({$outcomeSql}) IN ({$placeholders})", [...$outcomeBindings, ...$dto->outcomes]);
        }

        if ($dto->earliestDateFrom) {
            $query->whereDate($this->vaccineDoseModel->getTable() . '.earliest_date', '>=', $dto->earliestDateFrom);
        }

        if ($dto->earliestDateTo) {
            $query->whereDate($this->vaccineDoseModel->getTable() . '.earliest_date', '<=', $dto->earliestDateTo);
        }
    }

    private function applySearch(Builder $query, ?string $search): void
    {
        if (!$search) {
            return;
        }

        $term = '%' . $search . '%';
        $query->where(function (Builder $q) use ($term) {
            $q->whereHas(
                'immunizationTracker.patient',
                fn (Builder $q) => $q->where('PatientName', 'like', $term)
                    ->orWhere('PatientAccount', 'like', $term)
                    ->orWhere('CF_TerminalPayer', 'like', $term)
            )->orWhereHas(
                'immunizationTracker.vaccine',
                fn (Builder $q) => $q->where('cdc_full_name', 'like', $term)
            );
        });
    }

    private function applySorting(Builder $query, string $sortBy, string $sortDir): void
    {
        $direction = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';
        $table = $this->vaccineDoseModel->getTable();
        match ($sortBy) {
            ImmunizationFilterDTO::SORT_DOSE => $query->orderBy("{$table}.sequence_number", $direction),
            ImmunizationFilterDTO::SORT_EARLIEST_DATE => $query->orderBy("{$table}.earliest_date", $direction),
            ImmunizationFilterDTO::SORT_DUE_DATE => $query->orderBy("{$table}.due_date", $direction),
            ImmunizationFilterDTO::SORT_STATUS => $query->orderBy("{$table}.status", $direction),
            // Second key: the dose due closest to today comes first within one patient.
            ImmunizationFilterDTO::SORT_PATIENT_NAME => $query
                ->join('immunization_trackers as sort_trk', 'sort_trk.id', '=', "{$table}.immunization_tracker_id")
                ->join('mssql_patients as sort_pat', 'sort_pat.id', '=', 'sort_trk.patient_id')
                ->orderBy('sort_pat.PatientName', $direction)
                ->orderByRaw("ABS(DATEDIFF(DATE({$table}.due_date), ?)) asc", [Carbon::today()->toDateString()]),
            ImmunizationFilterDTO::SORT_PATIENT_ACCOUNT => $query
                ->join('immunization_trackers as sort_trk', 'sort_trk.id', '=', "{$table}.immunization_tracker_id")
                ->join('mssql_patients as sort_pat', 'sort_pat.id', '=', 'sort_trk.patient_id')
                ->orderBy('sort_pat.PatientAccount', $direction),
            ImmunizationFilterDTO::SORT_PAYER => $query
                ->join('immunization_trackers as sort_trk', 'sort_trk.id', '=', "{$table}.immunization_tracker_id")
                ->join('mssql_patients as sort_pat', 'sort_pat.id', '=', 'sort_trk.patient_id')
                ->orderBy('sort_pat.CF_TerminalPayer', $direction),
            ImmunizationFilterDTO::SORT_BRANCH => $query
                ->join('immunization_trackers as sort_trk', 'sort_trk.id', '=', "{$table}.immunization_tracker_id")
                ->join('mssql_patients as sort_pat', 'sort_pat.id', '=', 'sort_trk.patient_id')
                ->join('mssql_facilities as sort_fac', 'sort_fac.id', '=', 'sort_pat.aoc_facility_id')
                ->orderBy('sort_fac.FacilityName', $direction),
            ImmunizationFilterDTO::SORT_VACCINE_NAME => $query
                ->join('immunization_trackers as sort_trk', 'sort_trk.id', '=', "{$table}.immunization_tracker_id")
                ->join('cdc_vaccines as sort_vac', 'sort_vac.cvx_code', '=', 'sort_trk.vaccine_cvx_code')
                ->orderBy('sort_vac.cdc_full_name', $direction),
            ImmunizationFilterDTO::SORT_ASSIGNEE_NAME => $query
                ->join('immunization_trackers as sort_trk', 'sort_trk.id', '=', "{$table}.immunization_tracker_id")
                ->leftJoin('users as sort_usr', 'sort_usr.id', '=', 'sort_trk.assignee_id')
                ->orderBy('sort_usr.name', $direction),
            ImmunizationFilterDTO::SORT_OUTCOME => $this->orderByOutcome($query, $direction),
            default => $query->orderBy("{$table}.created_at", $direction),
        };

        // Doses created in one batch share created_at — the id keeps paging stable.
        $query->orderBy("{$table}.id", $direction);
    }

    /** Severity order: Overdue, Missing, Upcoming, Planned (reversed for desc). */
    private function orderByOutcome(Builder $query, string $direction): Builder
    {
        [$outcomeSql, $outcomeBindings] = $this->outcomeExpression();
        $order = implode(',', array_map(fn (VaccineDoseOutcomeEnum $case) => "'{$case->value}'", VaccineDoseOutcomeEnum::cases()));

        return $query->orderByRaw("FIELD(({$outcomeSql}), {$order}) {$direction}", $outcomeBindings);
    }

    /**
     * The single definition of the outcome rules (first match wins), shared by the select, the filter and the
     * sort. Today is bound from Carbon (app timezone) rather than CURDATE(); the columns are datetime, so only
     * their date part is compared.
     *
     * @return array{0: string, 1: array<int, string>}
     */
    private function outcomeExpression(): array
    {
        $table = $this->vaccineDoseModel->getTable();
        $today = Carbon::today();
        $overdue = VaccineDoseOutcomeEnum::OVERDUE->value;
        $missing = VaccineDoseOutcomeEnum::MISSING->value;
        $upcoming = VaccineDoseOutcomeEnum::UPCOMING->value;
        $planned = VaccineDoseOutcomeEnum::PLANNED->value;

        $sql = "CASE WHEN DATE({$table}.due_date) < ? THEN '{$overdue}'"
            . " WHEN DATE({$table}.earliest_date) <= ? THEN '{$missing}'"
            . " WHEN DATE({$table}.earliest_date) <= ? THEN '{$upcoming}'"
            . " ELSE '{$planned}' END";

        return [$sql, [
            $today->toDateString(),
            $today->toDateString(),
            $today->copy()->addDays(VaccineDoseOutcomeEnum::UPCOMING_WINDOW_DAYS)->toDateString(),
        ]];
    }
}
