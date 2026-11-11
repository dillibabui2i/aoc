<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Common\BigQueryConnection;
use App\Kantime\DataMappers\ScheduleMasterDataMapper;
use App\Kantime\Domain\DataItem;
use App\Kantime\Domain\ScheduleMasterItem;
use App\Kantime\Repository\KantimeRepositoryInterface;

final class ScheduleMasterRepository implements KantimeRepositoryInterface
{
    private const TABLE_NAME = 'daas_dw_schedulemaster_vw';

    private const DEFAULT_LIMIT = 1000;

    // Lower bound (exclusive) of SCHEDULE_DATE for everything synced from Kantime
    private const MIN_SCHEDULE_DATE = '2021-12-31';

    private string $tableRef;

    public function __construct(
        private readonly BigQueryConnection $connection,
        private readonly ScheduleMasterDataMapper $mapper,
    ) {
        $this->tableRef = $this->connection->tableRef(self::TABLE_NAME);
    }

    /**
     * @return \Generator|ScheduleMasterItem[]
     */
    public function getRecords(int $limit, int|string $offset, ?string $lastUpdated = null, ?string $order = null, ?string $groupBy = null): \Generator
    {
        $columns = $this->selectColumns();
        $sql = <<<SQL
            SELECT
            {$columns}
        FROM `$this->tableRef`
        WHERE DATE(SCHEDULE_DATE) > DATE(@after)
          AND (
              ETL_UPDATED_TIMESTAMP > DATETIME(@last_updated)
              OR (
                  ETL_UPDATED_TIMESTAMP = DATETIME(@last_updated)
                  AND SCHEDULE_ID > @last_id
              )
          )
        ORDER BY ETL_UPDATED_TIMESTAMP, SCHEDULE_ID
        LIMIT @limit
        SQL;

        $params = [
            'limit' => $limit,
            'after' => self::MIN_SCHEDULE_DATE,
            'last_updated' => (string) $lastUpdated,
            'last_id' => (int) $offset,
        ];

        foreach ($this->connection->cursor($sql, $params) as $row) {
            yield $this->mapper->fromDaasRow($row);
        }
    }

    /**
     * Fetch specific schedules by SCHEDULE_ID (CGTaskID) in one BigQuery query.
     *
     * @return \Generator|ScheduleMasterItem[]
     */
    public function getRecordsByIds(array $ids): \Generator
    {
        $columns = $this->selectColumns();
        $sql = <<<SQL
            SELECT
            {$columns}
        FROM `$this->tableRef`
        WHERE DATE(SCHEDULE_DATE) > DATE(@after)
          AND SCHEDULE_ID IN UNNEST(@ids)
        SQL;

        $params = [
            'after' => self::MIN_SCHEDULE_DATE,
            'ids' => array_values(array_map('intval', $ids)),
        ];

        foreach ($this->connection->cursor($sql, $params) as $row) {
            yield $this->mapper->fromDaasRow($row);
        }
    }

    private function selectColumns(): string
    {
        return <<<SQL
            FORMAT_TIMESTAMP('%F %H:%M:%E6S', ETL_UPDATED_TIMESTAMP) AS LASTMODIFIED,
            CONCAT(
                FORMAT_TIMESTAMP('%F %H:%M:%E6S', ETL_UPDATED_TIMESTAMP),
                CAST(SCHEDULE_ID AS STRING)
            ) AS CombinedString,
            ETL_UPDATED_TIMESTAMP,
            AGENCY_ID AS HHA,
            S_SCEDULE_CREATOR_ID AS CreatedBy,
            S_HHA_BRANCH_ID AS HHABranchID,
            SCHEDULE_ID AS CGTaskID,
            S_CLIENT_ID AS ClientID,
            S_CAREGIVER_ID AS Caregiver,
            S_PAYER_SOURCE_ID AS PayerID,
            S_SERVICE_CODE_ID AS ServiceCodeID,
            CAST(S_PLANNED_START AS STRING) AS PlannedStart,
            CAST(S_PLANNED_END AS STRING) AS PlannedEnd,
            CAST(S_ACTUAL_START AS STRING) AS ActualStart,
            CAST(S_ACTUAL_END AS STRING) AS ActualEnd,
            ROUND(S_PLANNED_HOURS, 2) AS PlannedHours,
            ROUND(S_ACTUAL_HOURS, 2) AS ActualHours,
            ROUND(S_HOURS, 2) AS Hours,
            CAST(S_SCHEDULE_STATUS AS STRING) AS ScheduleStatus,
            CAST(S_ECHART_STATUS AS STRING) AS EChartStatus,
            S_MILES AS Miles,
            S_IS_AUTH_REQUIRED AS IsAuthRequired,
            CAST(S_AUTHORIZATIONS AS STRING) AS Authorizations,
            CAST(S_PAYBLE_HOURS AS STRING) AS PaybleHours,
            CAST(REASON_FOR_MISSED_VISIT AS STRING) AS MissedVisitReason,
            CAST(S_WEEKSTART AS STRING) AS WeekStart,
            EPISODE_ID AS EpisodeID,
            S_IS_PRN_VISIT AS IsPRNVisit,
            CAST(S_REVENUE_CODE AS STRING) AS RevenueCode,
            CAST(S_GCODE AS STRING) AS GCode,
            CAST(MODIFIER_1 AS STRING) AS Modifier1,
            CAST(MODIFIER_2 AS STRING) AS Modifier2,
            CAST(MODIFIER_3 AS STRING) AS Modifier3,
            CAST(S_UNITS AS STRING) AS Units,
            S_HAS_CHILD AS HasChild,
            IS_CHILD_VISIT AS IsChild,
            PARENT_SCHEDULE_ID AS ParentCgTaskID,
            IS_SCHEDULE_BILLABLE AS IsBillable,
            IS_SCHEDULE_PAYABLE AS IsPayable,
            IS_SCHEDULE_BILLED AS IsBilled,
            IS_SCHEDULE_PAID AS IsPaid,
            S_BILL_RATE AS BillRate,
            S_PAY_RATE AS PayRate,
            CAST(S_BILL_MODE AS STRING) AS BillMode,
            CAST(S_PAY_MODE AS STRING) AS PayMode,
            S_BILLED_AMOUNT AS BilledAmount,
            S_PAYROLLED_AMOUNT AS PayrolledAmount,
            S_REGULARAMOUNT AS RegularAmount,
            S_OT_AMOUNT AS OTAmount,
            S_BONUS_AMOUNT AS BonusAmount,
            S_BASE_RATE AS BaseRate,
            CAST(REASON_FOR_CANELLATION AS STRING) AS CancellationReason,
            S_CONTRACTUAL_RATE AS ContractualRate,
            CAST(S_CONTRACTUAL_MODE AS STRING) AS ContractualMode,
            S_CONTRACTUAL_AMOUNT AS ContractualAmount,
            CAST(NON_BILLABLE_REASON AS STRING) AS NonBillableReason,
            ROUND(S_REGULAR_HOURS, 2) AS RegularHours,
            ROUND(S_OT_HOURS, 2) AS OTHours,
            IS_MISCVISIT AS isMiscVisit,
            S_COST AS Cost,
            S_REVENUE AS Revenue,
            S_CONTRACTUAL_REVENUE AS ContractualRevenue,
            CAST(SCHEDULE_DATE AS STRING) AS ScheduleDate,
            S_PROFIT AS Profit,
            S_PROFIT_CONTRACTUAL AS ProfitContractual,
            S_MARGIN AS Margin,
            S_MARGIN_CONTRACTUAL AS MarginContractual,
            S_BILLED_HOURS AS BilledHours,
            S_SCHEDULE_PRODUCTIVITY_POINTS AS ScheduleProductivityPoints,
            CAST(S_PAYROLL_WEEK_STARTDATE AS STRING) AS PayrollWeekStartDate,
            S_WEEK_NO AS WeekNo,
            S_CLINICIAN_PRODUCTIVITY_PER_WEEK AS ClinicianProductivityPerWeek,
            S_AUTHORIZATION_ID AS AuthorizationID,
            MISC_VISIT_ID AS ScheduleMiscVisitID,
            CAST(SUBMITTEDON_DATE AS STRING) AS SubmittedOn,
            CAST(APPROVED_DATE AS STRING) AS ApprovedOn,
            CAST(SCHEDULE_INVOICEEDDATE AS STRING) AS InvoicedOn,
            CAST(S_PAYMENT_RECIEVED_ON AS STRING) AS PaymentRecievedOn,
            S_HOLIDAY_HOURS AS HolidayHours,
            S_HOLIDAY_PAYROLL_AMOUNT AS HolidayPayrollAmount,
            S_HOLIDAY_PAYROLL_RATE AS HolidayPayrollRate,
            S_OT_PAYROLL_RATE AS OTPayrollRate,
            CAST(SCHEDULE_ADDED_ON AS STRING) AS AddedOn,
            CAST(SECONDARY_PAYERSOURCE AS STRING) AS SecondaryPayerSourceNames,
            CAST(TERTIARY_PAYERSOURCE AS STRING) AS TertiaryPayerSourceNames,
            CAST(SUBSTR(CAST(S_MISC_VISITREASON AS STRING), 1, 250) AS STRING) AS MiscVisitReason,
            S_IS_EVV_SCHEDULE AS IsEvvSchedule,
            CAST(S_ECC_AGGREGATORSTATUS AS STRING) AS EVVAggregatorStatus,
            SCHEDULE_UNIQUE_IDENTIFIER,
            CAST(ISVALIDRECORD AS STRING) AS ISVALIDRECORD
        SQL;
    }

    //TODO not used, kept for KantimeRepositoryInterface; the query is broken (duplicate WHERE, no @after), use getRecordsByIds()
    public function getRecord($identifier) : ScheduleMasterItem | DataItem | array | null
    {
        $sql = <<<SQL
            SELECT
                FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS LASTMODIFIED,
                CONCAT(
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP),
                    CAST(SCHEDULE_ID AS STRING)
                ) AS CombinedString,
                ETL_UPDATED_TIMESTAMP,
                AGENCY_ID AS HHA,
                S_SCEDULE_CREATOR_ID AS CreatedBy,
                S_HHA_BRANCH_ID AS HHABranchID,
                SCHEDULE_ID AS CGTaskID,
                S_CLIENT_ID AS ClientID,
                S_CAREGIVER_ID AS Caregiver,
                S_PAYER_SOURCE_ID AS PayerID,
                S_SERVICE_CODE_ID AS ServiceCodeID,
                CAST(S_PLANNED_START AS STRING) AS PlannedStart,
                CAST(S_PLANNED_END AS STRING) AS PlannedEnd,
                CAST(S_ACTUAL_START AS STRING) AS ActualStart,
                CAST(S_ACTUAL_END AS STRING) AS ActualEnd,
                ROUND(S_PLANNED_HOURS, 2) AS PlannedHours,
                ROUND(S_ACTUAL_HOURS, 2) AS ActualHours,
                ROUND(S_HOURS, 2) AS Hours,
                CAST(S_SCHEDULE_STATUS AS STRING) AS ScheduleStatus,
                CAST(S_ECHART_STATUS AS STRING) AS EChartStatus,
                S_MILES AS Miles,
                S_IS_AUTH_REQUIRED AS IsAuthRequired,
                CAST(S_AUTHORIZATIONS AS STRING) AS Authorizations,
                CAST(S_PAYBLE_HOURS AS STRING) AS PaybleHours,
                CAST(REASON_FOR_MISSED_VISIT AS STRING) AS MissedVisitReason,
                CAST(S_WEEKSTART AS STRING) AS WeekStart,
                EPISODE_ID AS EpisodeID,
                S_IS_PRN_VISIT AS IsPRNVisit,
                CAST(S_REVENUE_CODE AS STRING) AS RevenueCode,
                CAST(S_GCODE AS STRING) AS GCode,
                CAST(MODIFIER_1 AS STRING) AS Modifier1,
                CAST(MODIFIER_2 AS STRING) AS Modifier2,
                CAST(MODIFIER_3 AS STRING) AS Modifier3,
                CAST(S_UNITS AS STRING) AS Units,
                S_HAS_CHILD AS HasChild,
                IS_CHILD_VISIT AS IsChild,
                PARENT_SCHEDULE_ID AS ParentCgTaskID,
                IS_SCHEDULE_BILLABLE AS IsBillable,
                IS_SCHEDULE_PAYABLE AS IsPayable,
                IS_SCHEDULE_BILLED AS IsBilled,
                IS_SCHEDULE_PAID AS IsPaid,
                S_BILL_RATE AS BillRate,
                S_PAY_RATE AS PayRate,
                CAST(S_BILL_MODE AS STRING) AS BillMode,
                CAST(S_PAY_MODE AS STRING) AS PayMode,
                S_BILLED_AMOUNT AS BilledAmount,
                S_PAYROLLED_AMOUNT AS PayrolledAmount,
                S_REGULARAMOUNT AS RegularAmount,
                S_OT_AMOUNT AS OTAmount,
                S_BONUS_AMOUNT AS BonusAmount,
                S_BASE_RATE AS BaseRate,
                CAST(REASON_FOR_CANELLATION AS STRING) AS CancellationReason,
                S_CONTRACTUAL_RATE AS ContractualRate,
                CAST(S_CONTRACTUAL_MODE AS STRING) AS ContractualMode,
                S_CONTRACTUAL_AMOUNT AS ContractualAmount,
                CAST(NON_BILLABLE_REASON AS STRING) AS NonBillableReason,
                ROUND(S_REGULAR_HOURS, 2) AS RegularHours,
                ROUND(S_OT_HOURS, 2) AS OTHours,
                IS_MISCVISIT AS isMiscVisit,
                S_COST AS Cost,
                S_REVENUE AS Revenue,
                S_CONTRACTUAL_REVENUE AS ContractualRevenue,
                CAST(SCHEDULE_DATE AS STRING) AS ScheduleDate,
                S_PROFIT AS Profit,
                S_PROFIT_CONTRACTUAL AS ProfitContractual,
                S_MARGIN AS Margin,
                S_MARGIN_CONTRACTUAL AS MarginContractual,
                S_BILLED_HOURS AS BilledHours,
                S_SCHEDULE_PRODUCTIVITY_POINTS AS ScheduleProductivityPoints,
                CAST(S_PAYROLL_WEEK_STARTDATE AS STRING) AS PayrollWeekStartDate,
                S_WEEK_NO AS WeekNo,
                S_CLINICIAN_PRODUCTIVITY_PER_WEEK AS ClinicianProductivityPerWeek,
                S_AUTHORIZATION_ID AS AuthorizationID,
                MISC_VISIT_ID AS ScheduleMiscVisitID,
                CAST(SUBMITTEDON_DATE AS STRING) AS SubmittedOn,
                CAST(APPROVED_DATE AS STRING) AS ApprovedOn,
                CAST(SCHEDULE_INVOICEEDDATE AS STRING) AS InvoicedOn,
                CAST(S_PAYMENT_RECIEVED_ON AS STRING) AS PaymentRecievedOn,
                S_HOLIDAY_HOURS AS HolidayHours,
                S_HOLIDAY_PAYROLL_AMOUNT AS HolidayPayrollAmount,
                S_HOLIDAY_PAYROLL_RATE AS HolidayPayrollRate,
                S_OT_PAYROLL_RATE AS OTPayrollRate,
                CAST(SCHEDULE_ADDED_ON AS STRING) AS AddedOn,
                CAST(SECONDARY_PAYERSOURCE AS STRING) AS SecondaryPayerSourceNames,
                CAST(TERTIARY_PAYERSOURCE AS STRING) AS TertiaryPayerSourceNames,
                CAST(SUBSTR(CAST(S_MISC_VISITREASON AS STRING), 1, 250) AS STRING) AS MiscVisitReason,
                S_IS_EVV_SCHEDULE AS IsEvvSchedule,
                CAST(S_ECC_AGGREGATORSTATUS AS STRING) AS EVVAggregatorStatus,
                SCHEDULE_UNIQUE_IDENTIFIER,
                CAST(ISVALIDRECORD AS STRING) AS ISVALIDRECORD
            FROM `$this->tableRef`
            WHERE DATE(SCHEDULE_DATE) > DATE(@after)
        WHERE CGTaskID = @identifier
        LIMIT @limit
        SQL;

        $params = [
            'limit' => 1,
            'identifier' => (int) $identifier,
        ];

        foreach ($this->connection->cursor($sql, $params) as $row) {
            return $this->mapper->fromDaasRow($row);
        }

        return null;
    }
}
