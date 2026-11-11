<?php

namespace App\Repositories\Reports\NurseChurn;

use App\Enums\MssqlScheduleMasterStatus;

class NurseChurnSelectRepository
{
    public function candidatesSql(bool $isFeatureFlag, string $categoriesSql, string $whereCandidates): string
    {
        return "
        with cte as (
            SELECT min(id) as appId
            FROM candidate_applications
            WHERE candidate_applications.application_date >= ?
              and candidate_applications.application_date <= ?
            group by candidate_id,
            DATE_FORMAT(
                date_add(candidate_applications.application_date, interval - WEEKDAY(candidate_applications.application_date) day),
                '%Y-%m-%d'
            )
        )
        SELECT
            candidate_applications.application_date as 'Application Date',
            candidates.fullName as Candidate,
            candidates.appWorkflowState as 'Work Flow State',
            jobs.branch as Branch,
            jobs.category as 'Job Category',
            jobs.title as Title
            " . ($isFeatureFlag ? ",
                COALESCE(NULLIF(candidate_applications.sourceType, ''), 'N/A') AS 'Application Source',
                COALESCE(NULLIF(candidate_applications.source, ''), 'N/A')     AS 'Source Type'
            " : "") . ",
            users.name as Recruiter
        FROM cte
        left join candidate_applications on cte.appId = candidate_applications.id
        left join candidates on candidate_applications.candidate_id = candidates.id
        left join jobs on candidate_applications.job_id = jobs.id
        left join users on candidates.user_id = users.id
        WHERE candidate_applications.application_date >= ?
          and candidate_applications.application_date <= ?
        " . ($categoriesSql ? " and jobs.category in ($categoriesSql)" : '') . "
        " . $whereCandidates;
    }

    public function hiredNursesSql(bool $isFeatureFlag, string $whereSql): string
    {
        return "
        SELECT
            mssql_providers.HiredDate as 'Hired Date',
            mssql_providers.ProviderName as 'Name',
            mssql_providers.ClinicianID
            " . ($isFeatureFlag ? ",
                mssql_providers.DisciplineName as 'Clinician Discipline'
            " : "") . ",
            mssql_facilities.FacilityName as Branch,
            mssql_employmentstatus.EmploymentStatus as 'Status'
            " . ($isFeatureFlag ? ",
                COALESCE(recruiter.Recruiter, 'N/A') as 'Recruiter',  
                COALESCE(({$this->billableShiftSql('ASC')}),'N/A') AS 'First Billable Shift',
                COALESCE(({$this->billableShiftSql('DESC')}),'N/A') AS 'Last Billable Shift'
            " : "") . "
        FROM mssql_providers
        left join mssql_facilities
            on mssql_providers.FacilityID = mssql_facilities.FacilityID
        left join mssql_employmentstatus
            on mssql_providers.EmploymentStatusID = mssql_employmentstatus.EmploymentStatusID
        " . ($isFeatureFlag ? "
           {$this->joinRecruiterTracker()}
        " : "") . "
        WHERE IsClinician = 1
          AND mssql_providers.HiredDate >= ?
          AND mssql_providers.HiredDate <= ?
        $whereSql
        ";
    }

    public function billableShiftsSql(bool $isFeatureFlag, string $whereSql): string
    {
        return "
        SELECT
            mssql_providers.ProviderName as 'Name',
            mssql_providers.ClinicianID
            " . ($isFeatureFlag ? ",
                mssql_providers.DisciplineName as 'Clinician Discipline'
            " : "") . ",
            mssql_facilities.FacilityName as Branch,
            mssql_providers.HiredDate as 'Hired Date',
            mssql_employmentstatus.EmploymentStatus as 'Status'
            " . ($isFeatureFlag ? ",
                COALESCE(recruiter.Recruiter, 'N/A') as 'Recruiter'  
            " : "") . ",
            " . ($isFeatureFlag ? "
                COALESCE(({$this->billableShiftSql('ASC')}),'N/A') AS 'First Billable Shift',
                COALESCE(({$this->billableShiftSql('DESC')}),'N/A') AS 'Last Billable Shift'
                " : "
                    (
                    select ScheduleDate
                    from mssql_schedulemaster
                    where mssql_schedulemaster.ProviderID = mssql_providers.ProviderID
                      and mssql_schedulemaster.ScheduleStatusID in (1,2,3)
                    order by ScheduleDate asc
                    limit 1
                    ) as 'First Shift' "
            ) . "
        FROM mssql_providers
        left join mssql_facilities
            on mssql_providers.FacilityID = mssql_facilities.FacilityID
        left join mssql_employmentstatus
            on mssql_providers.EmploymentStatusID = mssql_employmentstatus.EmploymentStatusID
        " . ($isFeatureFlag ? "
                {$this->joinRecruiterTracker()}
        " : "") . "   
        WHERE IsClinician = 1
          AND mssql_providers.has_billable_visit = 1
          AND mssql_providers.HiredDate >= ?
          AND mssql_providers.HiredDate <= ?
        $whereSql
        ";
    }

    public function noBillableShiftsSql(bool $isFeatureFlag, string $whereSql): string
    {
        return "
        SELECT
            mssql_providers.ProviderName as 'Name',
            mssql_providers.ClinicianID
            " . ($isFeatureFlag ? ",
                mssql_providers.DisciplineName as 'Clinician Discipline'
            " : "") . ",
            mssql_facilities.FacilityName as Branch,
            mssql_providers.HiredDate as 'Hired Date',
            mssql_employmentstatus.EmploymentStatus as 'Status'
            " . ($isFeatureFlag ? ",
                COALESCE(recruiter.Recruiter, 'N/A') as 'Recruiter'  
            " : "") . "
        FROM mssql_providers
        left join mssql_facilities
            on mssql_providers.FacilityID = mssql_facilities.FacilityID
        left join mssql_employmentstatus
            on mssql_providers.EmploymentStatusID = mssql_employmentstatus.EmploymentStatusID
        " . ($isFeatureFlag ? "
                {$this->joinRecruiterTracker()}
        " : "") . "   
        WHERE IsClinician = 1
          AND ifnull(mssql_providers.has_billable_visit, 0) = 0
          AND mssql_providers.HiredDate >= ?
          AND mssql_providers.HiredDate <= ?
        $whereSql
        ";
    }

    public function workingAfterDaysSql(int $days, string $whereSql, bool $isFeatureFlag = false): string
    {
        return "
            SELECT
            mssql_providers.ProviderName as 'Name',
            mssql_providers.ClinicianID
            " . ($isFeatureFlag ? ",
                 mssql_providers.DisciplineName as 'Clinician Discipline'
            " : "") . ",
            mssql_facilities.FacilityName as Branch,
            mssql_providers.HiredDate as 'Hired Date',
            mssql_employmentstatus.EmploymentStatus as 'Status'
            " . ($isFeatureFlag ? ",
                COALESCE(recruiter.Recruiter, 'N/A') as 'Recruiter'  
            " : "") . ",
            " . ($isFeatureFlag ? "
                COALESCE(({$this->billableShiftSql('ASC')}),'N/A') AS 'First Billable Shift',
                COALESCE(({$this->billableShiftSql('DESC')}),'N/A') AS 'Last Billable Shift'
                " : "
                    (
                        select ScheduleDate
                        from mssql_schedulemaster
                        where mssql_schedulemaster.ProviderID = mssql_providers.ProviderID
                          and mssql_schedulemaster.ScheduleStatusID in (1,2,3)
                        order by ScheduleDate asc
                        limit 1
                    ) as 'First Shift',
                    mssql_providers.latest_schedule as 'Last Shift'
                    "
                ) . "
            FROM mssql_providers
            left join mssql_facilities
            on mssql_providers.FacilityID = mssql_facilities.FacilityID
            left join mssql_employmentstatus
            on mssql_providers.EmploymentStatusID = mssql_employmentstatus.EmploymentStatusID
            " . ($isFeatureFlag ? "
               {$this->joinRecruiterTracker()}
            " : "") . "
            WHERE IsClinician = 1
            AND ifnull(mssql_providers.has_billable_visit, 0) = 1
            AND DATEDIFF(mssql_providers.latest_schedule, mssql_providers.HiredDate) >= {$days}
            AND mssql_providers.HiredDate >= ?
            AND mssql_providers.HiredDate <= ?
            $whereSql
        ";
    }

    private function billableShiftSql(string $direction): string
    {
        $statuses = implode(',', [
            MssqlScheduleMasterStatus::APPROVED->value,
            MssqlScheduleMasterStatus::COMPLETED->value,
            MssqlScheduleMasterStatus::IN_PROGRESS->value,
        ]);

        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        return "
            SELECT DATE_FORMAT(sm.PlannedStart, '%m/%d/%Y %h:%i %p')
            FROM mssql_schedulemaster sm
            WHERE sm.ProviderID = mssql_providers.ProviderID
              AND sm.ScheduleStatusID IN ({$statuses})
              AND sm.IsBillable = 1
              AND sm.isMiscVisit = 0
            ORDER BY sm.PlannedStart {$direction}
            LIMIT 1
        ";
    }

    private function joinRecruiterTracker(): string
    {
        return "
            LEFT JOIN mssql_recruitertracker recruiter
                ON recruiter.aoc_provider_id = mssql_providers.id
               AND recruiter.id = (
                    SELECT MAX(rt2.id)
                    FROM mssql_recruitertracker rt2
                    WHERE rt2.aoc_provider_id = mssql_providers.id
                )
        ";
    }
}
