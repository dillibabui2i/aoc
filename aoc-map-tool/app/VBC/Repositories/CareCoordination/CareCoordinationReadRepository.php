<?php

namespace App\VBC\Repositories\CareCoordination;

use App\VBC\DTO\CareCoordination\CareCoordinationFilterDTO;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Models\CareCoordination\CareCoordinationModel;
use App\VBC\Models\CareCoordination\CareCoordinationTypeModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;

class CareCoordinationReadRepository
{
    /** Relations every caller that needs to resolve "the" tracker of an encounter loads — latest first, see below. */
    private const CARE_COORDINATION_RELATIONS = [
        'encounter.patient.facility',
        'type',
        'assignedTo',
    ];

    public function __construct(
        private CareCoordinationModel $careCoordinationModel,
        private CareCoordinationTypeModel $careCoordinationTypeModel,
        private HospitalizationTrackerModel $hospitalizationTrackerModel,
    ) {
    }

    public function getPaginated(CareCoordinationFilterDTO $dto): LengthAwarePaginator
    {
        // The bucket list doesn't need the tracker (no read-only/editable icon distinction there
        // anymore) — only findById() does, for the modal's read-only check.
        $query = $this->careCoordinationModel->newQuery()->with(self::CARE_COORDINATION_RELATIONS);
        $this->applyFilters($query, $dto);
        $this->applySearch($query, $dto->search);
        $this->applySorting($query, $dto->sortBy, $dto->sortDir);

        return $query->paginate($dto->perPage, [$this->careCoordinationModel->getTable() . '.*'], 'page', $dto->page);
    }

    public function findById(int $id): ?CareCoordinationModel
    {
        /** @var CareCoordinationModel|null $careCoordination */
        $careCoordination = $this->careCoordinationModel->newQuery()
            ->with($this->relationsWithLatestTracker())
            ->find($id);

        return $careCoordination;
    }

    /**
     * `encounter.hospitalizationTrackers`, ordered newest first — so
     * `$careCoordination->encounter->hospitalizationTrackers->first()` is "the" tracker for that
     * encounter (see latestTrackerForEncounter() below), without every resource re-sorting the
     * collection itself. Not `limit(1)`-constrained: that would cap the *combined* eager-load query at
     * one row total across every parent encounter, not one per encounter.
     *
     * @return array<int|string, mixed>
     */
    private function relationsWithLatestTracker(): array
    {
        return [
            ...self::CARE_COORDINATION_RELATIONS,
            'encounter.hospitalizationTrackers' => fn (HasMany $q) => $q->orderByDesc('id'),
        ];
    }

    /** Active (non-archived) options for the "Coordination Type" dropdown. */
    public function getActiveTypeOptions(): Collection
    {
        return $this->careCoordinationTypeModel->newQuery()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function findTrackerById(int $trackerId): ?HospitalizationTrackerModel
    {
        /** @var HospitalizationTrackerModel|null $tracker */
        $tracker = $this->hospitalizationTrackerModel->newQuery()->find($trackerId);

        return $tracker;
    }

    /**
     * The most recently created tracker on this encounter — an encounter may in principle carry more
     * than one (EncounterModel::hospitalizationTrackers()), so this is "the" tracker only in the sense
     * that every other Care Coordination status check in this codebase already picks the latest one
     * (see HospitalizationReadRepository::findActiveTrackerForPatient()).
     */
    public function latestTrackerForEncounter(int $encounterId): ?HospitalizationTrackerModel
    {
        /** @var HospitalizationTrackerModel|null $tracker */
        $tracker = $this->hospitalizationTrackerModel->newQuery()
            ->where('encounter_id', $encounterId)
            ->orderByDesc('id')
            ->first();

        return $tracker;
    }

    /**
     * Patients eligible for the Care Coordination "Patient" field (AC5): those whose latest
     * hospitalization tracker is in one of HospitalizationTrackerStatusEnum::activeCareCoordinationStatuses().
     * Whether the tracker is specifically "In Progress" (AC6) is checked separately at creation time by
     * CareCoordinationTrackerIsInProgress, not filtered out of this list.
     *
     * Joined from the tracker side (MssqlPatients has no `encounters()` relation to whereHas() through)
     * and de-duplicated to one row per patient — the most recent matching tracker — the same "one in
     * flight at a time" simplification HospitalizationReadRepository::findActiveTrackerForPatient() makes.
     */
    public function searchEligibleClients(string $search, ?EncounterTypeEnum $encounterType = null): SupportCollection
    {
        $query = $this->hospitalizationTrackerModel->newQuery()
            ->select([
                'hospitalization_trackers.id as hospitalization_tracker_id',
                'hospitalization_trackers.status as tracker_status',
                'encounters.facility_name',
                'encounters.admission_at as admission_date_time',
                'mssql_patients.id as patient_id',
                'mssql_patients.PatientName as full_name',
                'mssql_patients.PatientAccount as patient_account',
            ])
            ->join('encounters', 'encounters.id', '=', 'hospitalization_trackers.encounter_id')
            ->join('mssql_patients', 'mssql_patients.id', '=', 'encounters.patient_id')
            ->where('mssql_patients.IsLatestAdmit', true)
            ->where('mssql_patients.is_deleted', false)
            ->where('mssql_patients.PatientName', 'like', '%' . $search . '%');

        $this->scopeEligibleTrackersByType($query, $encounterType);

        return $query
            ->orderByDesc('hospitalization_trackers.id')
            ->limit(50)
            ->get()
            ->unique('patient_id')
            ->take(20)
            ->values();
    }

    /**
     * Whether this tracker passes the same eligibility scope the patient search applies for the type
     * (scopeEligibleTrackersByType) — for ED that includes "the patient has no active IP tracker".
     * Used by the create validation so a hand-built request can't bypass what the search hides.
     */
    public function isTrackerEligibleForCreation(int $trackerId, EncounterTypeEnum $encounterType): bool
    {
        $query = $this->hospitalizationTrackerModel->newQuery()
            ->join('encounters', 'encounters.id', '=', 'hospitalization_trackers.encounter_id')
            ->where('hospitalization_trackers.id', $trackerId);

        $this->scopeEligibleTrackersByType($query, $encounterType);

        return $query->exists();
    }

    /**
     * PD-3969. ED: only In Progress ED trackers, and never for a patient who has an active IP tracker
     * (IP takes precedence). IP: IP trackers only, so ED trackers are not offered in the ICM flow.
     * No type: the legacy behavior (any encounter type, "active" statuses).
     */
    private function scopeEligibleTrackersByType(Builder $query, ?EncounterTypeEnum $encounterType): void
    {
        $activeStatuses = HospitalizationTrackerStatusEnum::activeCareCoordinationStatuses();

        if ($encounterType === EncounterTypeEnum::ED) {
            $query->where('encounters.type', EncounterTypeEnum::ED->value)
                ->where('hospitalization_trackers.status', HospitalizationTrackerStatusEnum::IN_PROGRESS)
                ->whereNotExists(function ($sub) use ($activeStatuses) {
                    $sub->selectRaw('1')
                        ->from('hospitalization_trackers as ip_trackers')
                        ->join('encounters as ip_encounters', 'ip_encounters.id', '=', 'ip_trackers.encounter_id')
                        ->whereColumn('ip_encounters.patient_id', 'encounters.patient_id')
                        ->where('ip_encounters.type', EncounterTypeEnum::IP->value)
                        ->whereIn('ip_trackers.status', $activeStatuses);
                });

            return;
        }

        $query->whereIn('hospitalization_trackers.status', $activeStatuses);
        if ($encounterType === EncounterTypeEnum::IP) {
            $query->where('encounters.type', EncounterTypeEnum::IP->value);
        }
    }

    private function applyFilters(Builder $query, CareCoordinationFilterDTO $dto): void
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
            $query->where('assigned_to_id', auth()->id());
        } elseif (!empty($dto->assigneeIds)) {
            $query->whereIn('assigned_to_id', $dto->assigneeIds);
        }

        if ($dto->encounterType !== null) {
            $query->whereHas(
                'encounter',
                fn (Builder $q) => $q->where('type', $dto->encounterType->value)
            );
        }

        if ($dto->isCompleted !== null) {
            $dto->isCompleted
                ? $query->whereNotNull('completed_at')
                : $query->whereNull('completed_at');
        }

        if ($dto->hospitalizationTrackerId !== null) {
            // care_coordinations links via encounter_id, not tracker_id (see the encounter-vs-tracker
            // decision in the plan) — resolve the tracker's encounter once and filter on that, rather
            // than a per-row join. An unknown tracker id filters to no rows instead of every row.
            $encounterId = $this->hospitalizationTrackerModel->newQuery()
                ->where('id', $dto->hospitalizationTrackerId)
                ->value('encounter_id');
            $query->where($this->careCoordinationModel->getTable() . '.encounter_id', $encounterId ?? 0);
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
            )->orWhereHas(
                'encounter',
                fn (Builder $q) => $q->where('facility_name', 'like', $term)
            );
        });
    }

    private function applySorting(Builder $query, string $sortBy, string $sortDir): void
    {
        $direction = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';
        match ($sortBy) {
            CareCoordinationFilterDTO::SORT_CREATED_AT => $query->orderBy('care_coordinations.created_at', $direction),
            CareCoordinationFilterDTO::SORT_DUE_DATE => $query->orderBy('care_coordinations.due_at', $direction),
            CareCoordinationFilterDTO::SORT_COORDINATION_TYPE => $query
                ->join('care_coordination_types as sort_type', 'sort_type.id', '=', 'care_coordinations.care_coordination_type_id')
                ->orderBy('sort_type.name', $direction),
            CareCoordinationFilterDTO::SORT_CLIENT_NAME => $query
                ->join('encounters as sort_enc', 'sort_enc.id', '=', 'care_coordinations.encounter_id')
                ->join('mssql_patients as sort_pat', 'sort_pat.id', '=', 'sort_enc.patient_id')
                ->orderBy('sort_pat.PatientName', $direction),
            CareCoordinationFilterDTO::SORT_CLIENT_ID => $query
                ->join('encounters as sort_enc', 'sort_enc.id', '=', 'care_coordinations.encounter_id')
                ->join('mssql_patients as sort_pat', 'sort_pat.id', '=', 'sort_enc.patient_id')
                ->orderBy('sort_pat.PatientAccount', $direction),
            CareCoordinationFilterDTO::SORT_HOSPITAL_NAME => $query
                ->join('encounters as sort_enc', 'sort_enc.id', '=', 'care_coordinations.encounter_id')
                ->orderBy('sort_enc.facility_name', $direction),
            CareCoordinationFilterDTO::SORT_ASSIGNEE_NAME => $query
                ->join('users as sort_usr', 'sort_usr.id', '=', 'care_coordinations.assigned_to_id')
                ->orderBy('sort_usr.name', $direction),
            default => $query->orderBy('care_coordinations.created_at', $direction),
        };
    }
}
