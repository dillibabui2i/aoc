<?php

namespace App\Repositories\NationalRecruitingReport\Tabs;

use App\Enums\NRR\NRRTypes;
use App\Enums\RolesEnum;
use Generator;
use Illuminate\Support\Facades\DB;

class RecruitingScheduleRepository
{
    /**
     * @param array $params
     * @return Generator
     */
    public function getReport(array $params): Generator
    {
         $baseSelect = "
            rt.whiteboard_row,
            CASE WHEN isnull(w.territory) = true THEN 0 ELSE rt.user_id END as user_id,
            w.territory,
            w.hire_date,
            w." . $params['report']['goalField'] . ",
            LEFT(rt.whiteboard_row,2) as state,
            reg.region_name,
            CONCAT(reg.region_name, ' | ', reg.team_leader) as region,
            " . $params['periodQuery'] . " as Monday,
            count(rs.id) as myCount,
            SUM(rt.Total_Hours) AS totalHours";

        [$selectExtra, $joinsExtra, $groupField, $roleFilter] = match ($params['type']) {
            NRRTypes::RECRUITER->value => [
                ", rt.Recruiter as Recruiter",
                "LEFT JOIN users u ON rt.user_id = u.id",
                "Recruiter",
                "u.title = 'Recruiter'"
            ],
            default => [
                ", ui.name as intakeCoordinator",
                "JOIN intake i ON rt.aoc_intake_id = i.id
                JOIN users ui ON ui.id = i.ic_id",
                "intakeCoordinator",
                "ui.title = 'Intake Coordinator'"
            ],
        };

        $sql = "
            SELECT
                $baseSelect
                $selectExtra
            FROM recruiting_schedule rs
                LEFT JOIN mssql_recruitertracker rt ON tracker_id = rt.id
                LEFT JOIN mssql_patients mp ON rs.aoc_patient_id = mp.id
                LEFT JOIN whiteboard w ON rt.whiteboard_row = w.row_id AND rt.user_id = w.user_id
                LEFT JOIN regions reg ON w.region = reg.id
                $joinsExtra
            WHERE rs.type_id = " . $params['schedTypeId'] . "
                AND rs.scheduled_date >= ?
                AND rs.scheduled_date <= ?
                " . (count($params['where']) > 0 ? " AND " . implode(" AND ", $params['where']) : "") . "
                AND rs.is_deleted <> 1
                AND $roleFilter
            GROUP BY LEFT(rt.whiteboard_row,2),
                rt.whiteboard_row,
                region,
                $groupField,
                w.territory,
                w.hire_date,
                w." . $params['report']['goalField'] . ",
                " . $params['periodQuery'] . "
            ORDER BY LEFT(rt.whiteboard_row,2),
                rt.whiteboard_row,
                region,
                $groupField,
                w.territory,
                w.hire_date,
                w." . $params['report']['goalField'] . ",
                " . $params['periodQuery'] . "
        ";

        return DB::stream($sql, $params['filterParams']);
    }
}
