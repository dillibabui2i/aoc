<?php

namespace App\Repositories\TrachVentClass;

use App\BranchLocations;
use App\TrachVentClassOccurrence;
use App\TrachVentClassRequest;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TrachVentClassOccurrenceReadRepository
{
    public function __construct(
        private TrachVentClassOccurrence $trachVentClassOccurrence,
        private TrachVentClassRequest $trachVentClassRequest,
        private BranchLocations $branchLocations,
    ) {
    }

    public function findBranchLocationIdsByState(string $state): array
    {
        return $this->branchLocations->newQuery()
            ->select('id')
            ->where('state', $state)
            ->pluck('id')
            ->toArray();
    }

    public function findBranchLocation(int $branchId): ?BranchLocations
    {
        return $this->branchLocations->newQuery()->find($branchId);
    }

    public function findOrFail(string $id): TrachVentClassOccurrence
    {
        return $this->trachVentClassOccurrence->newQuery()->with('trachVentClass')->findOrFail($id);
    }

    public function findWithDetails(int $id): ?TrachVentClassOccurrence
    {
        return $this->trachVentClassOccurrence->newQuery()
            ->with('trachVentClass.classCategory', 'trachVentClass.branchLocation')
            ->where('trach_vent_class_occurrences.id', $id)
            ->first();
    }

    public function findWithDetailsOrFirstForClass(int $id): ?TrachVentClassOccurrence
    {
        $occurrence = $this->findWithDetails($id);

        if ($occurrence !== null) {
            return $occurrence;
        }

        $occurrence = $this->firstUpcomingOrEarliest($id);
        $occurrence?->load('trachVentClass.classCategory', 'trachVentClass.branchLocation');

        return $occurrence;
    }

    public function firstUpcomingOrEarliest(int $classId): ?TrachVentClassOccurrence
    {
        $query = $this->trachVentClassOccurrence->newQuery()->where('class_id', $classId);

        $upcoming = (clone $query)
            ->where('class_date', '>=', date('Y-m-d'))
            ->orderBy('class_date')
            ->first();

        return $upcoming ?? $query->orderBy('class_date')->first();
    }

    public function availableForSchedulingForm(
        int $locationId,
        int $classType,
        ?string $classTypeLabel,
        bool $sortByDate,
    ): Collection
    {
        return $this->buildAvailableForSchedulingFormQuery($locationId, $classType, $classTypeLabel, $sortByDate)->get();
    }

    private function buildAvailableForSchedulingFormQuery(
        int $locationId,
        int $classType,
        ?string $classTypeLabel,
        bool $sortByDate,
    ): Builder {
        $query = $this->joinClass($this->trachVentClassOccurrence->newQuery())
            ->select(
                'trach_vent_class_occurrences.id',
                DB::raw("DATE_FORMAT(trach_vent_class_occurrences.class_date,'%m/%d/%Y') AS class_date"),
                DB::raw("TIME_FORMAT(COALESCE(trach_vent_class_occurrences.class_time, trach_vent_classes.class_time),'%h:%i %p') AS class_time"),
                DB::raw("TIME_FORMAT(COALESCE(trach_vent_class_occurrences.end_time, trach_vent_classes.end_time),'%h:%i %p') AS end_time"),
                DB::raw('COALESCE(trach_vent_class_occurrences.capacity, trach_vent_classes.capacity) as capacity'),
            )
            ->withCount('enrollments')
            ->where('trach_vent_class_occurrences.class_date', '>=', date('Y-m-d'))
            ->where('trach_vent_classes.type', $classType)
            ->havingRaw('enrollments_count < capacity');
        $this->filterByLocation($query, [$locationId]);

        if ($classTypeLabel !== null && $classTypeLabel !== '') {
            $query->where(
                DB::raw('COALESCE(trach_vent_class_occurrences.class_type, trach_vent_classes.class_type)'),
                $classTypeLabel,
            );
        }

        if ($sortByDate) {
            $query->orderBy('trach_vent_class_occurrences.class_date', 'asc')
                ->orderBy('class_time', 'asc');
        }

        return $query;
    }

    /**
     * Upcoming occurrences of the same class type at the same branch, excluding the given one.
     *
     * @return Collection<int, TrachVentClassOccurrence>
     */
    public function findRescheduleTargets(TrachVentClassOccurrence $occurrence): Collection
    {
        $class = $occurrence->trachVentClass;
        $classTimeSql = 'COALESCE(trach_vent_class_occurrences.class_time, trach_vent_classes.class_time)';

        $query = $this->joinClass($this->trachVentClassOccurrence->newQuery())
            ->with('trachVentClass')
            ->select('trach_vent_class_occurrences.*')
            ->withCount('enrollments')
            ->where('trach_vent_class_occurrences.id', '!=', $occurrence->id)
            ->where('trach_vent_classes.location_id', $class->location_id)
            ->where('trach_vent_classes.type', $class->type)
            ->whereRaw("CONCAT(trach_vent_class_occurrences.class_date, ' ', {$classTimeSql}) >= ?", [now()->format('Y-m-d H:i:s')])
            ->orderBy('trach_vent_class_occurrences.class_date')
            ->orderByRaw($classTimeSql);

        $classType = $occurrence->class_type;

        if ($classType === null || $classType === '') {
            $query->whereRaw("COALESCE(trach_vent_class_occurrences.class_type, trach_vent_classes.class_type, '') = ''");
        } else {
            $query->whereRaw('COALESCE(trach_vent_class_occurrences.class_type, trach_vent_classes.class_type) = ?', [$classType]);
        }

        return $query->get();
    }

    public function forWeek(string $start, string $end, array $locationIds = [], ?int $branchId = null): Collection
    {
        return $this->buildForWeekQuery($start, $end, $locationIds, $branchId)->get();
    }

    private function buildForWeekQuery(string $start, string $end, array $locationIds = [], ?int $branchId = null): Builder
    {
        $query = $this->joinClass($this->trachVentClassOccurrence->newQuery())
            ->with(['trachVentClass' => fn ($query) => $query->with('classCategory')->withCount('occurrences')])
            ->withCount('enrollments')
            ->whereBetween('trach_vent_class_occurrences.class_date', [$start, $end])
            ->select('trach_vent_class_occurrences.*');

        $this->filterByLocation($query, $locationIds);

        if ($branchId !== null) {
            $query->where('trach_vent_classes.location_id', '=', $branchId);
        }

        return $query;
    }

    private function joinClass(Builder $query): Builder
    {
        return $query->join('trach_vent_classes', 'trach_vent_classes.id', '=', 'trach_vent_class_occurrences.class_id');
    }

    private function filterByLocation(Builder $query, array $locationIds): void
    {
        if ($locationIds !== []) {
            $query->whereIn('trach_vent_classes.location_id', $locationIds);
        }
    }

    /**
     * @return Collection<int, TrachVentClassOccurrence>
     */
    public function findSeriesFrom(TrachVentClassOccurrence $occurrence): Collection
    {
        return $this->seriesQuery($occurrence)
            ->where('class_date', '>=', $occurrence->class_date)
            ->get();
    }

    /**
     * @return Collection<int, TrachVentClassOccurrence>
     */
    public function findSeries(TrachVentClassOccurrence $occurrence): Collection
    {
        return $this->seriesQuery($occurrence)->get();
    }

    /**
     * Users who enrolled clinicians into the given occurrences, keyed by occurrence id.
     *
     * @param  array<int, int>  $occurrenceIds
     * @return Collection<int, Collection<int, int>>
     */
    public function findEnrollerIdsByOccurrence(array $occurrenceIds): Collection
    {
        if ($occurrenceIds === []) {
            return collect();
        }

        return $this->buildEnrollerIdsQuery($occurrenceIds)
            ->get()
            ->groupBy('trach_vent_class_occurrence_id')
            ->map(fn (Collection $rows) => $rows->pluck('created_by')->map(fn ($id) => (int) $id)->unique()->values());
    }

    private function buildEnrollerIdsQuery(array $occurrenceIds): Builder
    {
        return $this->trachVentClassRequest->newQuery()
            ->select('trach_vent_class_occurrence_id', 'created_by')
            ->whereIn('trach_vent_class_occurrence_id', $occurrenceIds)
            ->whereNotNull('created_by')
            ->active();
    }

    private function seriesQuery(TrachVentClassOccurrence $occurrence): Builder
    {
        return $this->trachVentClassOccurrence->newQuery()
            ->with('trachVentClass.classCategory')
            ->where('class_id', $occurrence->class_id);
    }

    public function findEnrollmentRequests(int $occurrenceId): Collection
    {
        return $this->trachVentClassRequest->newQuery()
            ->select(
                'trach_vent_class_request.*',
                'selected_client.PatientName as selected_client_name',
                DB::raw("CONCAT(mssql_providers.FirstName, ' ', mssql_providers.LastName) as provider_full_name"),
                'candidates.fullName as candidate_full_name'
            )
            ->leftJoin('mssql_providers', 'mssql_providers.id', '=', 'trach_vent_class_request.provider_id')
            ->leftJoin('candidates', 'candidates.id', '=', 'trach_vent_class_request.candidate_id')
            ->leftJoin('mssql_patients as selected_client', 'selected_client.id', '=', 'trach_vent_class_request.client_id')
            ->where('trach_vent_class_request.trach_vent_class_occurrence_id', $occurrenceId)
            ->active('trach_vent_class_request')
            ->get();
    }

    public function searchCalendarRequests(string $term): Collection
    {
        return $this->trachVentClassRequest->newQuery()
            ->leftJoin('trach_vent_class_occurrences', 'trach_vent_class_request.trach_vent_class_occurrence_id', '=', 'trach_vent_class_occurrences.id')
            ->leftJoin('trach_vent_classes', 'trach_vent_class_occurrences.class_id', '=', 'trach_vent_classes.id')
            ->leftJoin('mssql_providers', 'mssql_providers.id', '=', 'trach_vent_class_request.provider_id')
            ->leftJoin('candidates', 'candidates.id', '=', 'trach_vent_class_request.candidate_id')
            ->leftJoin('branch_locations', 'trach_vent_classes.location_id', '=', 'branch_locations.id')
            ->select(
                'trach_vent_class_request.id',
                DB::raw("COALESCE(CONCAT(mssql_providers.FirstName, ' ', mssql_providers.LastName), candidates.fullName) AS Candidate"),
                'branch_locations.branch',
                DB::raw("DATE_FORMAT(trach_vent_class_occurrences.class_date,'%m/%d/%Y') AS class_date"),
                'trach_vent_classes.location_id as branchId'
            )
            ->where(function ($query) use ($term) {
                $query->where(DB::raw("CONCAT(mssql_providers.FirstName, ' ', mssql_providers.LastName)"), 'LIKE', "%{$term}%")
                    ->orWhere('candidates.fullName', 'LIKE', "%{$term}%");
            })
            ->active('trach_vent_class_request')
            ->get();
    }
}
