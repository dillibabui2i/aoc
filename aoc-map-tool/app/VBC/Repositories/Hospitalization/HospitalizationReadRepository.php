<?php

namespace App\VBC\Repositories\Hospitalization;

use App\Enums\UserStatus;
use App\Helpers\UserPermissionResolver;
use App\MssqlPatients;
use App\User;
use App\VBC\DTO\Hospitalization\HospitalizationClientSearchDTO;
use App\VBC\DTO\Hospitalization\HospitalizationFilterDTO;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationExportTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Models\Encounter\EncounterModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class HospitalizationReadRepository
{
    /** Route the "Intensive Case Management" permission is registered under in config/permissions.php. */
    private const ICM_PERMISSION_ROUTE = 'hospitalization.index';

    /** Route the "Manage ED Visits" permission is registered under in config/permissions.php. */
    private const ED_PERMISSION_ROUTE = 'ed-visits.manage';

    /** Relations the single-tracker "Hospitalization Record" form needs to render without N+1 queries. */
    private const TRACKER_FORM_RELATIONS = [
        'encounter.patient.facility',
        'encounter.patient.customProperties.definition',
        'properties.definition',
        'author',
        'assignedTo',
        'followups.assignedTo',
        'followups.completedBy',
        'followups.attemptedBy',
        'followups.patient.facility',
        'contacts.type',
        'contacts.receivedBy',
        'diagnoses.createdBy',
        'trainings.type',
        'trainings.source',
    ];

    public function __construct(
        private HospitalizationTrackerModel $hospitalizationTrackerModel,
        private MssqlPatients $mssqlPatients,
        private User $user,
        private UserPermissionResolver $permissionResolver,
    ) {}

    public function getPaginated(HospitalizationFilterDTO $dto): LengthAwarePaginator
    {
        return $this
            ->buildQuery($dto)
            ->paginate($dto->perPage, [$this->hospitalizationTrackerModel->getTable() . '.*'], 'page', $dto->page);
    }

    /** All rows matching the current filters/search, unpaginated, for CSV export. Cursor over the result rather
     * than ->get() so exporting a large dataset doesn't load it all into memory at once. */
    public function getAllForExport(HospitalizationFilterDTO $dto): LazyCollection
    {
        return $this
            ->buildQuery($dto)
            ->select($this->hospitalizationTrackerModel->getTable() . '.*')
            ->cursor();
    }

    public function getBucketPaginated(HospitalizationFilterDTO $dto): LengthAwarePaginator
    {
        $query = $this
            ->buildQuery($dto)
            ->whereIn($this->hospitalizationTrackerModel->getTable() . '.status', HospitalizationTrackerStatusEnum::bucketCases());

        return $query->paginate($dto->perPage, [$this->hospitalizationTrackerModel->getTable() . '.*'], 'page', $dto->page);
    }

    /**
     * Triage "ED Visits" bucket. The encounter type itself comes in through the DTO's `encounterTypes`
     * filter (the bucket always sends ED) — only the status scope and the extra relation the bucket's
     * "Follow-up Status" column reads are specific to this method.
     */
    public function getEdVisitsBucketPaginated(HospitalizationFilterDTO $dto): LengthAwarePaginator
    {
        $query = $this
            ->buildQuery($dto)
            ->with('currentFollowup')
            ->whereIn(
                $this->hospitalizationTrackerModel->getTable() . '.status',
                HospitalizationTrackerStatusEnum::edVisitsBucketCases()
            );

        return $query->paginate($dto->perPage, [$this->hospitalizationTrackerModel->getTable() . '.*'], 'page', $dto->page);
    }

    private function buildQuery(HospitalizationFilterDTO $dto): Builder
    {
        $query = $this->hospitalizationTrackerModel->newQuery()
            ->with(['encounter.patient.facility', 'assignedTo', 'latestFollowup']);
        $this->applyFilters($query, $dto);
        $this->applySearch($query, $dto->search);
        $this->applySorting($query, $dto->sortBy, $dto->sortDir);

        return $query;
    }

    public function findForForm(int $trackerId): ?HospitalizationTrackerModel
    {
        /** @var HospitalizationTrackerModel|null $tracker */
        $tracker = $this->hospitalizationTrackerModel->newQuery()
            ->with(self::TRACKER_FORM_RELATIONS)
            ->find($trackerId);

        return $tracker;
    }

    /**
     * The tracker's encounter type only (IP/ED) — used by UpdateHospitalizationTrackerOutcomeRequest to
     * pick the accepted closing reasons for the right lifecycle without loading every relation
     * findForForm() does.
     */
    public function findEncounterType(int $trackerId): ?EncounterTypeEnum
    {
        /** @var HospitalizationTrackerModel|null $tracker */
        $tracker = $this->hospitalizationTrackerModel->newQuery()
            ->with('encounter:id,type')
            ->find($trackerId, ['id', 'encounter_id']);

        return $tracker?->encounter?->type;
    }

    /**
     * The trackers of the transfers that led to this one, nearest first, each with its encounter loaded —
     * the "Hospitalization History" table.
     *
     * @return SupportCollection<int, HospitalizationTrackerModel>
     */
    public function findTransferHistory(HospitalizationTrackerModel $tracker): SupportCollection
    {
        return $tracker->encounter->previousEncounters()
            ->map(fn (EncounterModel $encounter) => $encounter->hospitalizationTrackers()
                ->with('encounter')
                ->orderByDesc('id')
                ->first())
            ->filter()
            ->values();
    }

    /**
     * The clients the Hospitalization Record form may work with. IsLatestAdmit + is_deleted = 0 is the
     * pairing the rest of the app uses when listing clients (see ClientController::clientListing) —
     * without it we would offer superseded admission rows and clients the DaaS sync has marked as
     * deleted. Every entry point (autocomplete, pre-fill, create validation) goes through here, so an
     * id that never appears in search results can't be fetched or submitted either.
     */
    private function selectableClients(): Builder
    {
        return $this->mssqlPatients->newQuery()
            ->where('IsLatestAdmit', true)
            ->where('is_deleted', false);
    }

    /** Client autocomplete for the "Client Name" field. */
    public function searchClients(HospitalizationClientSearchDTO $dto): Collection
    {
        return $this->selectableClients()
            ->where('PatientName', 'like', '%' . $dto->search . '%')
            ->orderBy('PatientName')
            ->limit($dto->limit)
            ->get(['id', 'PatientName', 'PatientAccount']);
    }

    /** One client with the relations the form pre-fill reads. */
    public function findClient(int $patientId): ?MssqlPatients
    {
        /** @var MssqlPatients|null $patient */
        $patient = $this->selectableClients()
            ->with(['facility', 'customProperties.definition'])
            ->find($patientId);

        return $patient;
    }

    /** Backs the ActiveClientExists validation rule — same condition as the autocomplete, no relations loaded. */
    public function existsSelectableClient(int $patientId): bool
    {
        return $this->selectableClients()->whereKey($patientId)->exists();
    }

    /**
     * A client may only have one tracker in flight at a time: anything not Completed/Closed counts as
     * active and blocks creating another one.
     * By default an active tracker of ANY kind (IP or ED) counts, which is what both creation flows and the
     * client lookup need (PD-3983 AC5/AC18, PD-5687); pass an encounter type to scope it to that kind only.
     */
    public function findActiveTrackerForPatient(int $patientId, ?EncounterTypeEnum $encounterType = null): ?HospitalizationTrackerModel
    {
        /** @var HospitalizationTrackerModel|null $tracker */
        $tracker = $this->hospitalizationTrackerModel->newQuery()
            ->whereNotIn($this->hospitalizationTrackerModel->getTable() . '.status', [
                HospitalizationTrackerStatusEnum::COMPLETED,
                HospitalizationTrackerStatusEnum::CLOSED,
            ])
            ->whereHas('encounter', fn (Builder $q) => $q->where('patient_id', $patientId)
                ->when($encounterType !== null, fn (Builder $q) => $q->where('type', $encounterType)))
            ->orderByDesc($this->hospitalizationTrackerModel->getTable() . '.id')
            ->first();

        return $tracker;
    }

    /**
     * Options for the "Assigned To" dropdown: everyone who holds the ICM permission — not just users
     * already assigned to some tracker (which is what getAssigneeOptions() below is for, as a filter).
     */
    public function getIcmAssigneeOptions(): Collection
    {
        return $this->getPermissionAssigneeOptions(self::ICM_PERMISSION_ROUTE);
    }

    /** Options for the ED Visit "Assigned To" dropdowns: everyone who holds the "Manage ED Visits" permission. */
    public function getEdAssigneeOptions(): Collection
    {
        return $this->getPermissionAssigneeOptions(self::ED_PERMISSION_ROUTE);
    }

    private function getPermissionAssigneeOptions(string $permissionRoute): Collection
    {
        return $this->user->newQuery()
            ->whereIn('id', $this->permissionResolver->getUserIdsWithPermission($permissionRoute))
            ->where('status', UserStatus::ACTIVE->value)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getAssigneeOptions(): Collection
    {
        return $this->user->newQuery()
            ->whereIn(
                'id',
                $this->hospitalizationTrackerModel->newQuery()->whereNotNull('assigned_to_id')->distinct()->pluck('assigned_to_id')
            )
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getFacilityNameOptions(): SupportCollection
    {
        return $this->hospitalizationTrackerModel->newQuery()
            ->join('encounters', 'encounters.id', '=', 'hospitalization_trackers.encounter_id')
            ->whereNotNull('encounters.facility_name')
            ->distinct()
            ->orderBy('encounters.facility_name')
            ->pluck('encounters.facility_name');
    }

    /**
     * Each region's state, derived from its facilities, so the FE can narrow Branch/Region options by
     * selected State. The `regions` table also holds whole-state placeholder rows (e.g. "Arizona",
     * "Colorado") for states with no sub-region breakdown, plus unrelated rows (e.g. "Social Media") —
     * only rows actually named "Region ..." are real regions, the rest just duplicate the State filter.
     */
    public function getRegionOptions(): SupportCollection
    {
        return DB::table('regions')
            ->select(
                'regions.id',
                'regions.region_name',
                DB::raw('(select State from mssql_facilities f where f.region = regions.id limit 1) as state')
            )
            ->where('regions.region_name', 'like', 'Region %')
            ->orderBy('regions.region_name')
            ->get();
    }

    private function applyFilters(Builder $query, HospitalizationFilterDTO $dto): void
    {
        if (!empty($dto->state)) {
            $query->whereHas(
                'encounter.patient',
                fn (Builder $q) => $q->whereIn('Patient_State', $dto->state)
            );
        }

        if (!empty($dto->branchIds)) {
            $query->whereHas(
                'encounter.patient.facility',
                fn (Builder $q) => $q->whereIn('id', $dto->branchIds)
            );
        }

        if (!empty($dto->regionIds)) {
            $query->whereHas(
                'encounter.patient.facility',
                fn (Builder $q) => $q->whereIn('region', $dto->regionIds)
            );
        }

        if ($dto->meMode) {
            $query->where('assigned_to_id', auth()->user()->id);
        } elseif (!empty($dto->assigneeIds)) {
            $query->whereIn('assigned_to_id', $dto->assigneeIds);
        }

        if (!empty($dto->statuses)) {
            $query->whereIn($this->hospitalizationTrackerModel->getTable() . '.status', $dto->statuses);
        }

        if (!empty($dto->levels)) {
            $query->whereIn($this->hospitalizationTrackerModel->getTable() . '.icm_level', $dto->levels);
        }

        if (!empty($dto->facilityNames)) {
            $query->whereHas(
                'encounter',
                fn (Builder $q) => $q->whereIn('facility_name', $dto->facilityNames)
            );
        }

        if ($dto->exportType === HospitalizationExportTypeEnum::HIGH_UTILIZERS->value) {
            $query->whereHas(
                'encounter.patient.customProperties',
                fn (Builder $q) => $q
                    ->whereHas('definition', fn (Builder $d) => $d->where('key', 'high_utilizer'))
                    ->whereJsonContains('value', true)
            );
        }

        if (!empty($dto->encounterTypes)) {
            $query->whereHas(
                'encounter',
                fn (Builder $q) => $q->whereIn('type', $dto->encounterTypes)
            );
        }

        if ($dto->admissionDateFrom) {
            $query->whereHas(
                'encounter',
                fn (Builder $q) => $q->whereDate('admission_at', '>=', $dto->admissionDateFrom)
            );
        }

        if ($dto->admissionDateTo) {
            $query->whereHas(
                'encounter',
                fn (Builder $q) => $q->whereDate('admission_at', '<=', $dto->admissionDateTo)
            );
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
                'encounter.patient',
                fn (Builder $q) => $q->where('PatientName', 'like', $term)
                    ->orWhere('PatientAccount', 'like', $term)
            )->orWhereRaw('CAST(hospitalization_trackers.id AS CHAR) LIKE ?', [$term]);
        });
    }

    private function applySorting(Builder $query, string $sortBy, string $sortDir): void
    {
        $direction = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';
        match ($sortBy) {
            HospitalizationFilterDTO::SORT_STATUS => $query->orderBy('hospitalization_trackers.status', $direction),
            HospitalizationFilterDTO::SORT_LEVEL => $query->orderBy('hospitalization_trackers.icm_level', $direction),
            HospitalizationFilterDTO::SORT_TRACKER_ID => $query->orderBy('hospitalization_trackers.id', $direction),
            HospitalizationFilterDTO::SORT_ADMISSION_DATE => $query
                ->join('encounters as sort_enc', 'sort_enc.id', '=', 'hospitalization_trackers.encounter_id')
                ->orderBy('sort_enc.admission_at', $direction),
            HospitalizationFilterDTO::SORT_DISCHARGE_DATE => $query
                ->join('encounters as sort_enc', 'sort_enc.id', '=', 'hospitalization_trackers.encounter_id')
                ->orderBy('sort_enc.discharged_at', $direction),
            HospitalizationFilterDTO::SORT_TYPE => $query
                ->join('encounters as sort_enc', 'sort_enc.id', '=', 'hospitalization_trackers.encounter_id')
                ->orderBy('sort_enc.admission_type', $direction),
            HospitalizationFilterDTO::SORT_PATIENT_NAME => $query
                ->join('encounters as sort_enc', 'sort_enc.id', '=', 'hospitalization_trackers.encounter_id')
                ->join('mssql_patients as sort_pat', 'sort_pat.id', '=', 'sort_enc.patient_id')
                ->orderBy('sort_pat.PatientName', $direction),
            HospitalizationFilterDTO::SORT_PATIENT_ACCOUNT => $query
                ->join('encounters as sort_enc', 'sort_enc.id', '=', 'hospitalization_trackers.encounter_id')
                ->join('mssql_patients as sort_pat', 'sort_pat.id', '=', 'sort_enc.patient_id')
                ->orderBy('sort_pat.PatientAccount', $direction),
            HospitalizationFilterDTO::SORT_ASSIGNEE_NAME => $query
                ->join('users as sort_usr', 'sort_usr.id', '=', 'hospitalization_trackers.assigned_to_id')
                ->orderBy('sort_usr.name', $direction),
            HospitalizationFilterDTO::SORT_BRANCH => $query
                ->join('encounters as sort_enc', 'sort_enc.id', '=', 'hospitalization_trackers.encounter_id')
                ->join('mssql_patients as sort_pat', 'sort_pat.id', '=', 'sort_enc.patient_id')
                ->join('mssql_facilities as sort_fac', 'sort_fac.id', '=', 'sort_pat.aoc_facility_id')
                ->orderBy('sort_fac.FacilityName', $direction),
            HospitalizationFilterDTO::SORT_LAST_FOLLOW_UP => $query
                ->leftJoinSub(
                    DB::table('hospitalization_tracker_followups')
                        ->select('hospitalization_tracker_id', DB::raw('MAX(completed_at) as last_follow_up_at'))
                        ->whereNotNull('completed_at')
                        ->groupBy('hospitalization_tracker_id'),
                    'sort_fu',
                    'sort_fu.hospitalization_tracker_id',
                    '=',
                    'hospitalization_trackers.id'
                )
                ->orderBy('sort_fu.last_follow_up_at', $direction),
            default => $query
                ->join('encounters as sort_enc', 'sort_enc.id', '=', 'hospitalization_trackers.encounter_id')
                ->orderBy('sort_enc.admission_at', $direction),
        };
    }
}
