<?php

namespace App\Repositories\NationalRecruitingReport\Tabs;

use App\Enums\NRR\NRRTypes;
use App\Enums\RolesEnum;
use App\Helpers\LD;
use Generator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class GrossMarginRepository
{
    /**
     * @param array $params
     * @return mixed
     */
    public function getReportByRecruiter(array $params): Generator
    {
        $periodQuery = Arr::get($params, 'periodQuery');
        $where = Arr::get($params, 'where');
        $schedTypeIds = Arr::get($params, 'schedTypeIds');

        $sql = "SELECT rt.whiteboard_row,
                    case when isnull(w.territory) = true then 999999 else rt.user_id end as user_id,
                    case when isnull(w.territory) = true then " . '"' . "Term'd" . '"' .  " else rt.Recruiter end as Recruiter,
                     w.territory,
                     w.hire_date,
                     left(rt.whiteboard_row,2) as state,
                     reg.region_name,
                     CONCAT(reg.region_name, ' | ', reg.team_leader) as region,
            " . $periodQuery . " as Monday,
            avg(rt.GP) as myCount

            FROM `recruiting_schedule` rs
            left join mssql_recruitertracker rt on tracker_id = rt.id
            left join mssql_patients mp on rs.aoc_patient_id = mp.id
            left join users u on rt.user_id = u.id
            left join whiteboard w on rt.whiteboard_row = w.row_id and rt.user_id = w.user_id
            left join regions reg on w.region = reg.id
            where rt.GP <> 0 and rs.type_id in (" . implode(',', $schedTypeIds) . ") and rs.appointment_date >= ? and rs.appointment_date <= ? ".
            (count($where) > 0 ? " AND " . implode(" AND " , $where) : ''). ' AND rs.is_deleted <> 1 ' . "
            group by
                rt.whiteboard_row,
                region,
                case when isnull(w.territory) = true
                    then 999999 else
                    rt.user_id end,
                rt.Recruiter,
                w.territory,
                w.hire_date,
                w.ipea_goal,
                left(rt.whiteboard_row,2), " . $periodQuery . "
            order by
                rt.whiteboard_row,
                region,
                case when isnull(w.territory) = true
                then 999999
                else rt.user_id end,
                 rt.Recruiter,
                 w.territory,
                 w.hire_date,
                 w.ipea_goal,
                 left(rt.whiteboard_row,2), " . $periodQuery;

        return DB::stream($sql, Arr::get($params, 'filterParams'));
    }

    /**
     * @param array $params
     * @return Generator
     */
    public function getReportByApprover(array $params): Generator
    {
        $periodQuery = Arr::get($params, 'periodQuery');
        $where = Arr::get($params, 'where');
        $schedTypeIds = Arr::get($params, 'schedTypeIds');

        $sql = "SELECT rt.whiteboard_row,
                    case when isnull(w.territory) = true
                    then " . '"' . "Term'd" . '"' .  "
                    else rt.Approved_By
                    end as approver,
                    left(rt.whiteboard_row,2) as state,
                    reg.region_name,
                    CONCAT(reg.region_name, ' | ', reg.team_leader) as region,
            " . $periodQuery . " as Monday,
            avg(rt.GP) as myCount

            FROM `recruiting_schedule` rs
            left join mssql_recruitertracker rt on tracker_id = rt.id
            left join mssql_patients mp on rs.aoc_patient_id = mp.id
            left join users u on rt.user_id = u.id
            left join whiteboard w on rt.whiteboard_row = w.row_id AND rt.user_id = w.user_id
            left join regions reg on w.region = reg.id
            where rt.GP <> 0 AND
             rs.type_id in (" . implode(',', $schedTypeIds) . ") AND
             rs.appointment_date >= ? AND
             rs.appointment_date <= ? ".
            (count($where) > 0 ? " AND
            " . implode(" AND " , $where) : ''). ' AND
            rs.is_deleted <> 1 ' . "
            group by
            rt.whiteboard_row,
            region,
            case when isnull(w.territory) = true
            then 999999 else rt.user_id end,
            rt.Approved_By,
            w.territory,
            w.hire_date,
            w.ipea_goal,
            left(rt.whiteboard_row,2), " . $periodQuery . "
            order by
            rt.whiteboard_row,
            region,
            case when isnull(w.territory) = true
            then 999999 else rt.user_id end,
            rt.Approved_By,
            w.territory,
            w.hire_date,
            w.ipea_goal,
            left(rt.whiteboard_row,2), " . $periodQuery;

        return DB::stream($sql, Arr::get($params, 'filterParams'));
    }

    /**
     * @param array $params
     * @return Generator
     * @throws \Exception
     */
    public function getReportByBranch(array $params): Generator
    {
        $periodQuery = Arr::get($params, 'periodQuery');
        $where = Arr::get($params, 'where');
        $schedTypeIds = Arr::get($params, 'schedTypeIds');

        $sql = "SELECT left(rt.whiteboard_row,2) as state,
             rt.whiteboard_row,
             mf.FacilityName as Branch_Location,
             reg.region_name,
             CONCAT(reg.region_name, ' | ', reg.team_leader) as region,
            " . $periodQuery . " as Monday,
            avg(rt.GP) as myCount
            FROM `recruiting_schedule` rs
            left join mssql_recruitertracker rt on tracker_id = rt.id
            left join mssql_patients mp on rs.aoc_patient_id = mp.id
            left join users u on rt.user_id = u.id
            left join whiteboard w on rt.whiteboard_row = w.row_id and rt.user_id = w.user_id
            left join mssql_facilities mf on mf.id = mp.aoc_facility_id
            left join regions reg on mf.region = reg.id
            where
            rs.type_id in (" . implode(',', $schedTypeIds) . ")
            and rs.appointment_date >= ?
            and rs.appointment_date <= ? " .
            (count($where) > 0 ? "
            AND " . implode("
            AND ", $where) : '') . ' AND rs.is_deleted <> 1 ' . "
            group by left(rt.whiteboard_row,2), rt.whiteboard_row, region, mp.aoc_facility_id, " . $periodQuery . "
            order by left(rt.whiteboard_row,2), rt.whiteboard_row, region, mp.aoc_facility_id, " . $periodQuery;

        return DB::stream($sql, Arr::get($params, 'filterParams'));
    }
}
