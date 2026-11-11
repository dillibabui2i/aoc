<?php

namespace App\Criteria;

use App\Enums\MssqlEmploymentStatus;
use App\Helpers\LD;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ActivityPrnCriteria implements CriteriaInterface
{
    private const PRN_NOT_CONFIRMED = 0;
    private const RECRUIT_NURSE_STATUS = 2;
    private const PRN_PATIENT = 1;
    private const RECRUIT_TRACKER_ACTIVE_STATUS = 1;

    private const READY_TO_WORK_IPEA_STATUS = 'Ready to work';
    private const CANDIDATE_INACTIVE_STATUS = 'Inactive';

    public function apply(Builder $query): Builder
    {
        if (LD::checkFeatureFlag('clean-active-coverage-process')) {
            return $this->activeCoverageUpdated($query);
        } else {
            return $this->activeCoverage($query);
        }
    }

    private function activeCoverage(Builder $query): Builder
    {
        // Narrow the payload to only the fields the PRN bucket actually renders/uses,
        // instead of the default mssql_recruitertracker.* (100+ columns).
        $query->select(
            'mssql_recruitertracker.id',
            'mssql_recruitertracker.Recruiter',
            'mssql_recruitertracker.Candidate',
            'mssql_recruitertracker.Candidate_Current_Status',
            'mssql_recruitertracker.Candidate_Start_Date',
            'mssql_recruitertracker.IPEA_Date',
            'mssql_recruitertracker.Hours_Worked',
            'mssql_recruitertracker.Bonus_Paid_Date',
            'mssql_recruitertracker.Total_Hours',
            'mssql_recruitertracker.created_at',
            'msqlfac.FacilityName',
            DB::raw(
                "(select concat(DATE_FORMAT(notes.created_at,'%m/%d'),' ',users.name,' commented: ',notes.notes)
                    from notes left join users on users.id = notes.created_by
                    where entity = 'tracker' and entity_id = mssql_recruitertracker.id
                    order by notes.id desc limit 1) as Candidate_Notes"
            )
        );

        $path1 = DB::table('mssql_recruitertracker as r')
            ->select('r.aoc_provider_id', DB::raw('MAX(r.IPEA_Date) as latest_ipea_date'), 'p2.EmploymentStatusID')
            ->leftJoin('mssql_providers as p2', 'r.aoc_provider_id', '=', 'p2.id')
            ->where('r.is_prn_confirmed', self::PRN_NOT_CONFIRMED)
            ->where('p2.EmploymentStatusID', MssqlEmploymentStatus::ACTIVE_COVERAGE->value)
            ->groupBy('r.aoc_provider_id');

        $path2 = DB::table('mssql_recruitertracker as r')
            ->select('r.aoc_provider_id', DB::raw('MAX(r.IPEA_Date) as latest_ipea_date'), 'p2.EmploymentStatusID')
            ->leftJoin('mssql_providers as p2', 'r.aoc_provider_id', '=', 'p2.id')
            ->leftJoin('mssql_patients as pat2', 'pat2.id', '=', 'r.aoc_patient_id')
            ->where('r.is_prn_confirmed', self::PRN_NOT_CONFIRMED)
            ->where('r.nurse_status', self::RECRUIT_NURSE_STATUS)
            ->where('pat2.isPRN', self::PRN_PATIENT)
            ->where('r.live_ipea_status', self::READY_TO_WORK_IPEA_STATUS)
            ->where('r.is_active', self::RECRUIT_TRACKER_ACTIVE_STATUS)
            ->where('r.Candidate_Current_Status', '!=', self::CANDIDATE_INACTIVE_STATUS)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('mssql_recruitertracker as sub')
                    ->join('mssql_providers as sp', 'sub.aoc_provider_id', '=', 'sp.id')
                    ->where('sp.EmploymentStatusID', MssqlEmploymentStatus::ACTIVE_COVERAGE->value)
                    ->whereRaw('sub.aoc_provider_id = r.aoc_provider_id');
            })
            ->groupBy('r.aoc_provider_id');

        $subquery = $path1->unionAll($path2);
        $query->where('mssql_recruitertracker.is_prn_confirmed', self::PRN_NOT_CONFIRMED);
        $query->joinSub($subquery, 'latest', function ($join) {
            $join->on('mssql_recruitertracker.aoc_provider_id', '=', 'latest.aoc_provider_id')
                ->on('mssql_recruitertracker.IPEA_Date', '=', 'latest.latest_ipea_date');
        });

        return $query;
    }

    private function activeCoverageUpdated(Builder $query): Builder
    {
        $query->select(
            'mssql_recruitertracker.id',
            'mssql_recruitertracker.Recruiter',
            'mssql_recruitertracker.Candidate',
            'mssql_recruitertracker.Candidate_Current_Status',
            'mssql_recruitertracker.Candidate_Start_Date',
            'mssql_recruitertracker.IPEA_Date',
            'mssql_recruitertracker.Hours_Worked',
            'mssql_recruitertracker.Bonus_Paid_Date',
            'mssql_recruitertracker.Total_Hours',
            'mssql_recruitertracker.created_at',
            'msqlfac.FacilityName',
            DB::raw(
                "(select concat(DATE_FORMAT(notes.created_at,'%m/%d'),' ',users.name,' commented: ',notes.notes)
                    from notes left join users on users.id = notes.created_by
                    where entity = 'tracker' and entity_id = mssql_recruitertracker.id
                    order by notes.id desc limit 1) as Candidate_Notes"
            ))
                ->leftJoin('mssql_patients as pat2', 'pat2.id', '=', 'mssql_recruitertracker.aoc_patient_id')
                ->leftJoin('mssql_providers as p2', 'mssql_recruitertracker.aoc_provider_id', '=', 'p2.id')
                ->where('pat2.isPRN', self::PRN_PATIENT)
                ->where('mssql_recruitertracker.live_ipea_status', self::READY_TO_WORK_IPEA_STATUS)
                ->where('mssql_recruitertracker.is_active', self::RECRUIT_TRACKER_ACTIVE_STATUS)
                ->where('mssql_recruitertracker.is_deleted', 0)
                ->where(DB::raw('COALESCE(mssql_recruitertracker.Hours_Worked, 0)'), '<', 40)
        ;

        return $query;
    }
}
