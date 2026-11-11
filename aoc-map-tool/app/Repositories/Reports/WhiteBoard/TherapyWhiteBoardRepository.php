<?php

namespace App\Repositories\Reports\WhiteBoard;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TherapyWhiteBoardRepository
{
    /**
     * @param string $date
     * @return array
     */
    public function getWhiteboardRowsByDate(string $date): array
    {
        $sqlCalculatedField = $this->buildSql($date);

        $sql = '
        SELECT
            whiteboard.*,
            CONCAT(regions.region_name, " | ", regions.team_leader) AS regionname,
            ' . implode(",\n", $sqlCalculatedField['calculatedFields']) . '
        FROM whiteboard
        LEFT JOIN regions
            ON whiteboard.region = regions.id
        LEFT JOIN candidates
            ON candidates.user_id = whiteboard.user_id
            AND EXISTS (
                SELECT 1 FROM candidate_applications ca
                JOIN jobs j ON j.id = ca.job_id
                WHERE ca.candidate_id = candidates.id
                  AND j.category LIKE \'%therap%\'
            )
        LEFT JOIN candidate_screenings
            ON candidate_screenings.candidate_id = candidates.id
        WHERE whiteboard.is_therapy = 1
          AND whiteboard.row_id <> "FL - PPEC"
        GROUP BY whiteboard.id, row_id, regions.region_name, user_name
        ORDER BY row_id,
            CASE
                WHEN regions.region_name = "Out of Territory" THEN "zz"
                ELSE regions.region_name
            END,
            user_name
    ';

        return DB::select($sql, $sqlCalculatedField['bindings']);
    }

    /**
     * @param $date
     * @return array[]
     */
    private function buildSql($date): array
    {
        $weekStart = Carbon::parse($date)->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekToDate = Carbon::parse($date)->toDateString();

        $workflowStates = [
            'PhoneScreen' => 'Recruiter Phone Screen',
            'clinicalInterviewsScheduled' => 'Scheduled Clinical Interview',
            'OfferExtended' => 'Offer Extended',
            'OfferAccepted' => 'Offer Accepted',
            'OfferRejected' => 'Offer Rejected',
        ];

        $calculatedFields = [];
        $bindings = [];

        foreach ($workflowStates as $alias => $state) {
            $calculatedFields[] = '
            COUNT(DISTINCT CASE
                WHEN DATE(candidates.workFlowStateChangeDate) = ?
                 AND TRIM(candidates.appWorkFlowState) = ?
                THEN candidates.id
            END) AS ' . $alias;

            $bindings[] = $date;
            $bindings[] = $state;
        }

        $calculatedFields[] = '
            COUNT(DISTINCT CASE
                WHEN DATE(candidates.workFlowStateChangeDate) = ?
                 AND candidate_screenings.qualified = 1
                THEN candidates.id
            END) AS qualified_candidates
        ';
        $bindings[] = $date;

        $calculatedFields[] = '
            COUNT(DISTINCT CASE
                WHEN DATE(candidates.workFlowStateChangeDate) = ?
                 AND candidate_screenings.qualified = 0
                THEN candidates.id
            END) AS unqualified_candidates
        ';
        $bindings[] = $date;

        $calculatedFields[] = '0 AS percent_to_goal';

        $calculatedFields[] = '0 AS onboarded_week_to_date';

        return [
            'calculatedFields' => $calculatedFields,
            'bindings' => $bindings
        ];
    }
}
