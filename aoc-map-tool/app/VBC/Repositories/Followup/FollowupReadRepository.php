<?php

namespace App\VBC\Repositories\Followup;

use App\VBC\DTO\Followup\FollowupFilterDTO;
use App\VBC\DTO\Hospitalization\HospitalizationClientSearchDTO;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFollowupStatusEnum;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerFollowupModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FollowupReadRepository
{
    /** Relations the Follow-up modal needs to render (header + documentation fields) without N+1 queries. */
    private const FORM_RELATIONS = [
        'hospitalizationTracker.encounter.patient.facility',
        'patient.facility',
        'assignedTo',
        'requestedBy',
        'completedBy',
        'attemptedBy',
    ];

    public function __construct(
        private HospitalizationTrackerFollowupModel $followupModel,
        private HospitalizationTrackerModel $hospitalizationTrackerModel,
    ) {}

    public function findForForm(int $followupId): ?HospitalizationTrackerFollowupModel
    {
        /** @var HospitalizationTrackerFollowupModel|null $followup */
        $followup = $this->followupModel->newQuery()
            ->with(self::FORM_RELATIONS)
            ->find($followupId);

        return $followup;
    }

    /** The tracker's current ED Visit follow-up (PD-4073): its latest one in the ED workflow's statuses. */
    public function findLatestEdVisitFollowup(int $trackerId): ?HospitalizationTrackerFollowupModel
    {
        /** @var HospitalizationTrackerFollowupModel|null $followup */
        $followup = $this->followupModel->newQuery()
            ->where('hospitalization_tracker_id', $trackerId)
            ->whereIn('status', array_column(HospitalizationTrackerFollowupStatusEnum::edVisitStatuses(), 'value'))
            ->orderByDesc('id')
            ->first();

        return $followup;
    }

    /**
     * A tracker a Follow-up may be created against or attached to: it exists and its status is not
     * Closed — the same condition `searchEligibleClients` below uses (AC4/AC29). Defaults to IP since
     * every tracker today is one; explicit so a future ED follow-up flow only needs to pass a different
     * value here.
     */
    public function existsEligibleTracker(int $trackerId, EncounterTypeEnum $encounterType = EncounterTypeEnum::IP): bool
    {
        return $this->hospitalizationTrackerModel->newQuery()
            ->whereKey($trackerId)
            ->where('status', '!=', HospitalizationTrackerStatusEnum::CLOSED->value)
            ->whereHas('encounter', fn (Builder $q) => $q->where('type', $encounterType))
            ->exists();
    }

    /**
     * Autocomplete for the Follow-up modal's "Hospitalized Client Name" field (AC4): any patient with at
     * least one hospitalization tracker whose status is not Closed, resolved to that patient's most
     * recent such tracker (AC5's Client ID / Hospitalization ID / Facility Name / Admission Date
     * pre-fill all come from it). Broader than the Hospitalization Record form's own client search,
     * which only offers clients with no tracker in flight at all.
     */
    public function searchEligibleClients(HospitalizationClientSearchDTO $dto, EncounterTypeEnum $encounterType = EncounterTypeEnum::IP): Collection
    {
        $trackers = $this->hospitalizationTrackerModel->newQuery()
            ->with(['encounter.patient.facility'])
            ->whereHas(
                'encounter.patient',
                fn (Builder $q) => $q
                    ->where('IsLatestAdmit', true)
                    ->where('is_deleted', false)
                    ->where('PatientName', 'like', '%' . $dto->search . '%')
            )
            ->whereHas('encounter', fn (Builder $q) => $q->where('type', $encounterType))
            ->where('status', '!=', HospitalizationTrackerStatusEnum::CLOSED->value)
            ->orderByDesc('id')
            ->get();

        return $trackers
            ->unique(fn (HospitalizationTrackerModel $tracker) => $tracker->encounter->patient_id)
            ->take($dto->limit)
            ->values();
    }

    public function getPaginated(FollowupFilterDTO $dto): LengthAwarePaginator
    {
        $query = $this->followupModel->newQuery()
            ->with([
                'patient.facility',
                'hospitalizationTracker.encounter',
                'patientHospitalizationTrackers' => fn ($q) => $q->latest('hospitalization_trackers.created_at'),
                'assignedTo',
            ]);
        $this->applyFilters($query, $dto);
        $this->applySearch($query, $dto->search);
        $this->applySorting($query, $dto->sortBy, $dto->sortDir);

        return $query->paginate($dto->perPage, [$this->followupModel->getTable() . '.*'], 'page', $dto->page);
    }

    private function applyFilters(Builder $query, FollowupFilterDTO $dto): void
    {
        if (!empty($dto->state)) {
            $query->whereHas(
                'patient',
                fn (Builder $q) => $q->whereIn('Patient_State', $dto->state)
            );
        }

        if (!empty($dto->branchIds)) {
            $query->whereHas(
                'patient.facility',
                fn (Builder $q) => $q->whereIn('id', $dto->branchIds)
            );
        }

        if (!empty($dto->regionIds)) {
            $query->whereHas(
                'patient.facility',
                fn (Builder $q) => $q->whereIn('region', $dto->regionIds)
            );
        }

        if ($dto->meMode) {
            $query->where('assigned_to_id', auth()->id());
        } elseif (!empty($dto->assigneeIds)) {
            $query->whereIn('assigned_to_id', $dto->assigneeIds);
        }

        if (!empty($dto->statuses)) {
            $query->whereIn($this->followupModel->getTable() . '.status', $dto->statuses);
        }

        if (!empty($dto->encounterTypes)) {
            $query->whereHas(
                'hospitalizationTracker.encounter',
                fn (Builder $q) => $q->whereIn('type', $dto->encounterTypes)
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
                'patient',
                fn (Builder $q) => $q->where('PatientName', 'like', $term)
                    ->orWhere('PatientAccount', 'like', $term)
            )->orWhereRaw('CAST(hospitalization_tracker_followups.hospitalization_tracker_id AS CHAR) LIKE ?', [$term]);
        });
    }

    private function applySorting(Builder $query, string $sortBy, string $sortDir): void
    {
        $direction = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';
        match ($sortBy) {
            FollowupFilterDTO::SORT_FOLLOWUP_DATE => $query->orderBy('hospitalization_tracker_followups.scheduled_at', $direction),
            FollowupFilterDTO::SORT_PATIENT_NAME => $query
                ->join('mssql_patients as sort_pat', 'sort_pat.id', '=', 'hospitalization_tracker_followups.patient_id')
                ->orderBy('sort_pat.PatientName', $direction),
            FollowupFilterDTO::SORT_PATIENT_ID => $query
                ->join('mssql_patients as sort_pat', 'sort_pat.id', '=', 'hospitalization_tracker_followups.patient_id')
                ->orderBy('sort_pat.PatientAccount', $direction),
            FollowupFilterDTO::SORT_FACILITY_NAME => $query
                ->join('mssql_patients as sort_pat', 'sort_pat.id', '=', 'hospitalization_tracker_followups.patient_id')
                ->join('mssql_facilities as sort_fac', 'sort_fac.id', '=', 'sort_pat.aoc_facility_id')
                ->orderBy('sort_fac.FacilityName', $direction),
            FollowupFilterDTO::SORT_ASSIGNEE_NAME => $query
                ->join('users as sort_usr', 'sort_usr.id', '=', 'hospitalization_tracker_followups.assigned_to_id')
                ->orderBy('sort_usr.name', $direction),
            default => $query->orderBy('hospitalization_tracker_followups.created_at', $direction),
        };
    }
}
