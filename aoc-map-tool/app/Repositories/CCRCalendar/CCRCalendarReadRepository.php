<?php

namespace App\Repositories\CCRCalendar;

use App\DTO\PrcCalendarFilterDTO;
use App\Enums\PrcShiftType;
use App\Enums\ScheduleTypes;
use App\Helpers\H;
use App\Helpers\LD;
use App\RecruitingSchedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CCRCalendarReadRepository
{
    public function __construct(
        private RecruitingSchedule $recruitingSchedule,
    ) {
    }

    public function getWeekEvents(string $weekStart, string $weekEnd, PrcCalendarFilterDTO $filter): Collection
    {
        $query = $this->recruitingSchedule->newQuery()
            ->select(
                'recruiting_schedule.*',
                $this->candidateSelect(),
                'mssql_patients.PatientName as client',
                'mssql_recruitertracker.Recruiter as recruiter',
                DB::raw('users.name as ccm_name'),
                DB::raw('mssql_facilities.FacilityName as branch_name')
            )
            ->whereBetween('recruiting_schedule.appointment_date', [$weekStart, $weekEnd])
            ->where('recruiting_schedule.type_id', ScheduleTypes::CCR->value)
            ->where('recruiting_schedule.is_deleted', '<>', '1');

        $this->joinRelatedTables($query);
        $this->applyShiftType($query, $filter->shiftTypes);
        $this->applyFilters($query, $filter);

        return $query->orderBy('recruiting_schedule.type_id', 'desc')->get();
    }

    public function getScheduleDetailsForDate(string $appointmentDate, PrcCalendarFilterDTO $filter): Collection
    {
        $query = $this->recruitingSchedule->newQuery()
            ->select(
                'recruiting_schedule.*',
                'recruiting_schedule.appointment_time as appointment_time_24',
                DB::raw('LOWER(TIME_FORMAT(recruiting_schedule.appointment_time, "%h:%i%p")) as appointment_time'),
                DB::raw('users.name as ccm_name'),
                DB::raw('mssql_facilities.FacilityName as branch_name')
            )
            ->where('recruiting_schedule.type_id', ScheduleTypes::CCR->value)
            ->where('recruiting_schedule.appointment_date', $appointmentDate);

        $this->joinRelatedTables($query);
        $this->applyShiftType($query, $filter->shiftTypes);
        $this->applyFilters($query, $filter);

        return $query->get();
    }

    public function candidateSelect(): Expression
    {
        return DB::raw(
            'CASE
                WHEN ifnull(recruiting_schedule.aoc_provider_id,0) = 0
                    AND ifnull(recruiting_schedule.aoc_candidate_id,0) = 0
                    AND ifnull(recruiting_schedule.coverage_id,0) != 0
                THEN coverage_tracker.new_clinician_name
                WHEN ifnull(recruiting_schedule.aoc_provider_id,0) != 0
                    AND ifnull(recruiting_schedule.aoc_candidate_id,0) = 0
                THEN mssql_providers.fullName
                ELSE mssql_recruitertracker.Candidate
            END as candidate'
        );
    }

    private function joinRelatedTables(Builder $query): void
    {
        $query->leftJoin('mssql_recruitertracker', 'recruiting_schedule.tracker_id', '=', 'mssql_recruitertracker.id')
            ->leftJoin('mssql_patients', 'recruiting_schedule.aoc_patient_id', '=', 'mssql_patients.id')
            ->leftJoin('coverage_tracker', 'coverage_tracker.id', '=', 'recruiting_schedule.coverage_id')
            ->leftJoin('mssql_providers', 'mssql_providers.id', '=', 'recruiting_schedule.aoc_provider_id')
            ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id');

        $this->joinResolvedAssignee($query);
    }

    /**
     * @param array<PrcShiftType> $shiftTypes Empty array applies no filter (all Shift Types shown).
     */
    private function applyShiftType(Builder $query, array $shiftTypes): void
    {
        if (empty($shiftTypes)) {
            return;
        }

        $query->where(function (Builder $outer) use ($shiftTypes) {
            foreach ($shiftTypes as $shiftType) {
                $outer->orWhere(function (Builder $inner) use ($shiftType) {
                    match ($shiftType) {
                        PrcShiftType::Training => $inner->whereNotNull('recruiting_schedule.tracker_id'),
                        PrcShiftType::Coverage => $inner->whereRaw('ifnull(recruiting_schedule.coverage_id,0) != 0'),
                        PrcShiftType::CCCRequest => $inner
                            ->whereNull('recruiting_schedule.tracker_id')
                            ->whereRaw('ifnull(recruiting_schedule.coverage_id,0) = 0'),
//                            ->where('recruiting_schedule.source', 'ccc'), //will need to uncomment when #5252 is deployed
                    };
                });
            }
        });
    }

    private function joinResolvedAssignee(Builder $query): void
    {
        if (LD::checkFeatureFlag('assign_ccm_to_coverage_tracker')) {
            $query->leftJoin('users', function ($join) {
                $join->on('users.id', '=', DB::raw("
                    CASE
                        WHEN mssql_recruitertracker.assigned_ccr <> '' THEN mssql_recruitertracker.assigned_ccr
                        WHEN recruiting_schedule.ccm_id IS NOT NULL AND recruiting_schedule.ccm_id <> 0 THEN recruiting_schedule.ccm_id
                        ELSE mssql_patients.ccm_id
                    END
                "));
            });
        } else {
            $query->leftJoin('users', function ($join) {
                $join->on('users.id', '=', DB::raw("IF(mssql_recruitertracker.assigned_ccr <> '', mssql_recruitertracker.assigned_ccr, mssql_patients.ccm_id)"));
            });
        }
    }

    private function applyFilters(Builder $query, PrcCalendarFilterDTO $filter): void
    {
        if ($filter->state !== '') {
            $facilities = H::getHelper('ConstantHelper')->getFacilitiesByState($filter->state);
            $query->where(function ($q) use ($facilities) {
                foreach ($facilities as $facility) {
                    $q->orWhere('mssql_patients.aoc_facility_id', '=', $facility->id);
                }
            });
        }
        if (!empty($filter->branch)) {
            $query->whereIn('mssql_patients.aoc_facility_id', $filter->branch);
        }
    }
}
