<?php

namespace App\Repositories\NationalRecruitingReport\Tabs;

use App\Enums\NRR\NRRTypes;
use App\Enums\RolesEnum;
use App\Helpers\LD;
use Generator;
use Illuminate\Support\Facades\DB;

class CandidateStartsRepository
{
    /**
     * @param array $params
     * @param bool $hasIntake
     * @return mixed
     * @throws \Exception
     */
    public function getReportByRecruiter(array $params, string $type = 'recruiter'): Generator
    {
        $baseSelect = "
            rt.whiteboard_row,
            rt.user_id,
            rt.Recruiter,
            w.territory,
            w.hire_date,
            w." . $params['report']['goalField'] . ",
            LEFT(rt.whiteboard_row,2) as state,
            reg.region_name,
            CONCAT(reg.region_name, ' | ', reg.team_leader) as region,
            " . $params['periodQuery'] . " as Monday,
            sum(rt.Total_Hours) as totalHours,
                COUNT(rs.id) AS schedulesCount
        ";

        [$selectExtra, $joinsExtra, $groupField] = match ($type) {
            NRRTypes::RECRUITER->value => [
                ", rt.Recruiter as Recruiter",
                "JOIN users_has_roles uhr ON uhr.user_id = rt.user_id AND uhr.role_id = " . RolesEnum::RECRUITER->value,
                "Recruiter"
            ],
            NRRTypes::INTAKE_COORDINATOR->value => [
                ", ui.name as intakeCoordinator",
                "JOIN intake i ON rt.aoc_intake_id = i.id
             JOIN users ui ON ui.id = i.ic_id AND ui.title = 'Intake Coordinator'",
                "intakeCoordinator"
            ],
            default => ['', '', ''],
        };

        $scheduleQuery = $this->scheduleQuery();

        $whereExtra = '';
        if (count($params['where']) > 0) {
            $whereExtra = ' AND ' . implode(' AND ', $params['where']);
        }

        if ($type === NRRTypes::INTAKE_COORDINATOR->value) {
            $whereExtra .= " AND ui.title = 'Intake Coordinator'";
        }

        $goalField = "w." . $params['report']['goalField'];

        $sql = "
            SELECT
                $baseSelect
                $selectExtra
            FROM `recruiting_schedule` rs
                LEFT JOIN mssql_recruitertracker rt ON rs.tracker_id = rt.id
                LEFT JOIN whiteboard w ON rt.whiteboard_row = w.row_id AND rt.user_id = w.user_id
                LEFT JOIN regions reg ON w.region = reg.id
                LEFT JOIN mssql_patients p1 ON rs.aoc_patient_id = p1.id
                LEFT JOIN mssql_providers p2 ON rt.aoc_provider_id = p2.id
                $joinsExtra
            WHERE rs.type_id = " . $params['schedTypeId'] . "
                AND IFNULL(p2.is_deleted, 0) = 0
                AND rs.appointment_date >= ? AND rs.appointment_date <= ?
                AND " . $scheduleQuery . "
                $whereExtra
                AND rs.is_deleted <> 1
            GROUP BY
                rt.whiteboard_row,
                region,
                $groupField,
                rt.user_id,
                w.territory,
                w.hire_date,
                {$goalField},
                LEFT(rt.whiteboard_row,2),
                " . $params['periodQuery'] . "
            ORDER BY
                rt.whiteboard_row,
                region,
                $groupField,
                rt.user_id,
                w.territory,
                w.hire_date,
                {$goalField},
                LEFT(rt.whiteboard_row,2),
                " . $params['periodQuery'] . "
        ";

        return DB::stream($sql, $params['filterParams']);
    }

    /**
     * @param array $params
     * @return Generator
     * @throws \Exception
     */
    public function getReportByBranch(array $params): Generator
    {
        $baseSelect = "
            left(rt.whiteboard_row,2) as state,
            rt.whiteboard_row,
            mf.FacilityName as Branch_Location,
            reg.region_name,
            CONCAT(reg.region_name, ' | ', reg.team_leader) as region,
            " . $params['periodQuery'] . " as Monday,
            sum(rt.Total_Hours) as myCount
        ";

        $scheduleQuery = $this->scheduleQuery();

        $sql = "
            SELECT
                $baseSelect
            FROM `recruiting_schedule` rs
                LEFT JOIN mssql_recruitertracker rt on tracker_id = rt.id
                LEFT JOIN users u on rt.user_id = u.id
                LEFT JOIN whiteboard w on rt.whiteboard_row = w.row_id and rt.user_id = w.user_id
                LEFT JOIN mssql_patients p1 on rs.aoc_patient_id = p1.id
                LEFT JOIN mssql_providers p2 on rt.aoc_provider_id = p2.id
                LEFT JOIN mssql_facilities mf on mf.id = p1.aoc_facility_id
                LEFT JOIN regions reg on mf.region = reg.id
            WHERE rs.type_id = " . $params['schedTypeId'] . " AND p2.is_deleted = 0 AND rs.appointment_date >= ? AND
                rs.appointment_date <= ? and
                " . $scheduleQuery . "
                " . (count($params['where']) > 0 ? " AND " . implode(" AND ", $params['where']) : '') . '
                AND rs.is_deleted <> 1 ' . "
            GROUP BY
                left(rt.whiteboard_row,2),
                 rt.whiteboard_row,
                 region,
                 p1.aoc_facility_id, " . $params['periodQuery'] . "
            ORDER BY
                left(rt.whiteboard_row,2),
                rt.whiteboard_row,
                region,
                p1.aoc_facility_id, " . $params['periodQuery'];

        return DB::stream($sql, $params['filterParams']);
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function scheduleQuery(): string
    {
        $scheduleQuery = '';
        if (LD::checkFeatureFlag('fixed-nrr-candidate-start-tab-prn-client-issue')) {
            if (LD::checkFeatureFlag('include-client-not-in-kantime-in-NRR-candidate-start-report')) {
                $scheduleQuery = '((p1.isPRN <> 1 AND rs.has_billable_visit = 1 and p1.not_in_kantime = 0) OR p1.isPRN = 1 OR p1.not_in_kantime = 1)';
            } else {
                $scheduleQuery = '((p1.isPRN <> 1 AND isnull((select sm.ScheduleDate from mssql_schedulemaster sm where
                sm.ProviderID = p2.ProviderID and sm.PatientID = p1.PatientID and sm.ScheduleStatusID in (1,2,3) and
                sm.ScheduleDate = rs.appointment_date limit 1)) = false) OR p1.isPRN = 1)';
            }
        } else {
            if (LD::checkFeatureFlag('include-client-not-in-kantime-in-NRR-candidate-start-report')) {
                $scheduleQuery = 'rs.has_billable_visit = 1';
            } else {
                $scheduleQuery = 'isnull((select sm.ScheduleDate from mssql_schedulemaster sm where
                sm.ProviderID = p2.ProviderID and sm.PatientID = p1.PatientID and sm.ScheduleStatusID in (1,2,3) and
                sm.ScheduleDate = rs.appointment_date limit 1)) = false';
            }
        }

        $scheduleQuery .= ' AND rt.Candidate_Current_Status <> \'Inactive\' ';

        return $scheduleQuery;
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function getWhiteBoardData(array $params): Generator
    {
        $role = $params['type'] === NRRTypes::RECRUITER->value ?
            RolesEnum::INTAKE_COORDINATOR->value :
            RolesEnum::RECRUITER->value;

        $recruiterRoleFilter = $params['type'] === NRRTypes::RECRUITER->value
            ? " AND EXISTS (SELECT 1 FROM users_has_roles uhr WHERE uhr.user_id = w.user_id AND uhr.role_id = " . RolesEnum::RECRUITER->value . ")"
            : "";

        $sql = "SELECT left(row_id,2) as state
                        ,row_id
                        ,CONCAT(reg.region_name, ' | ', reg.team_leader) as region
                        ,reg.region_name
                        ,user_name
                        ,w.user_id
                        ,territory
                        ,hire_date
                        ," . $params['report']['goalField'] . "
                    FROM `whiteboard` w
                    left join regions reg on w.region = reg.id
                    left join mssql_recruitertracker rt on w.row_id = rt.whiteboard_row and rt.user_id = w.user_id
                    left join mssql_patients p1 on p1.id = rt.aoc_patient_id
                    WHERE
                    rt.user_id NOT IN (SELECT id FROM users WHERE role = $role) AND
                    w.is_deleted = 0
                    $recruiterRoleFilter " . (count($params['where']) > 0 ? "
                    AND " . implode(" AND ", $params['where']) : '') . "
                    order by w.row_id, region, w.user_id, user_name, territory, hire_date";

        return DB::stream($sql, $params['filterParams']);
    }
}
