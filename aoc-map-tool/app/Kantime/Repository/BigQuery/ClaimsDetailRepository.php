<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Domain\DataItem;

final class ClaimsDetailRepository extends AbstractCommonRepository
{
    const TABLE_NAME = 'daas_dw_claimdetails_vw';

    public function getQuery(): string
    {
        return <<<SQL
                SELECT
                    CD_CLAIM_DETAIL_ID AS claim_detail_id,
                    AGENCY_ID AS agency_id,
                    CD_CLAIM_ID AS claim_id,
                    CD_CONTRACTUAL_AMOUNT AS contractual_amount,
                    CD_CONTRACTUAL_RATE AS contractual_rate,
                    CD_PAIDAMOUNT AS paidamount,
                    CAST(CD_IS_DENIED AS INT64) AS is_denied,
                    CAST(CD_IS_LOCKED AS INT64) AS is_locked,
                    CAST(CD_GL_LOCKED_MONTH AS STRING) AS gl_locked_month,
                    CAST(CD_CLAIM_START_DATE AS STRING) AS start_date,
                    CD_SCHEDULE_ID AS schedule_id,
                    CD_CHILD_SCHEDULE_ID AS child_schedule_id,
                    CD_MISC_VISIT_ID AS misc_visit_id,
                    CD_SERVICE_CODE_ID AS service_code_id,
                    CAST(CD_ITEM_TYPE AS STRING) AS item_type,
                    FORMAT_TIMESTAMP('%F %T', CD_SCHEDULE_DATE) AS schedule_date,
                    CD_UNITS AS units,
                    CD_BILLED_AMOUNT AS billed_amount,
                    CD_BILLRATE AS billrate,
                    CAST(CD_IS_BILLRATE_HOURLY AS INT64) AS is_billrate_hourly,
                    CAST(CD_IS_UNITBASED_BILLING AS INT64) AS is_unitbased_billing,
                    CAST(CD_IS_CONTRACTUAL_UNITBASED_BILLING AS INT64) AS is_contractual_unitbased_billing,
                    CAST(CD_BILLRATE_HOURLY_CONTRACTUAL AS INT64) AS billrate_hourly_contractual,
                    CAST(CD_START_TIME AS STRING) AS start_time,
                    CAST(CD_END_TIME AS STRING) AS end_time,
                    CD_TOTAL_HOURS AS total_hours,
                    CD_REQUIFEE_AMOUNT AS requifee_amount,
                    CAST(CD_IS_MERGED AS INT64) AS is_merged,
                    CD_MERGEDCLAIM_DETAIL_ID AS mergedclaim_detail_id,
                    CAST(CD_HCPCS AS STRING) AS hcpcs,
                    CAST(CD_IS_ADDITIONAL_LINEITEM AS INT64) AS is_additional_lineitem,
                    FORMAT_TIMESTAMP('%F %T', CD_SYSTEM_DATE) AS system_date,
                    CAST(CD_DESCRIPTION AS STRING) AS description,
                    CAST(CD_CDS_CLAIM_LINEITEM_TYPE AS STRING) AS cds_claim_lineitem_type,
                    CD_CDS_CLIENT_BUDGET_LINEITEM_ID AS cds_client_budget_lineitem_id,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(CD_CLAIM_DETAIL_ID AS STRING)) AS combinedstring
                FROM `$this->tableRef`
                WHERE CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(CD_CLAIM_DETAIL_ID AS STRING)) > ?
                ORDER BY combinedstring
                LIMIT ?
                SQL;
    }

    public function getTableName(): string
    {
        return self::TABLE_NAME;
    }

    public function getRecord($identifier): array|DataItem
    {
        $query = <<<SQL
                SELECT
                    CD_CLAIM_DETAIL_ID AS claim_detail_id,
                    AGENCY_ID AS agency_id,
                    CD_CLAIM_ID AS claim_id,
                    CD_CONTRACTUAL_AMOUNT AS contractual_amount,
                    CD_CONTRACTUAL_RATE AS contractual_rate,
                    CD_PAIDAMOUNT AS paidamount,
                    CAST(CD_IS_DENIED AS INT64) AS is_denied,
                    CAST(CD_IS_LOCKED AS INT64) AS is_locked,
                    CAST(CD_GL_LOCKED_MONTH AS STRING) AS gl_locked_month,
                    CAST(CD_CLAIM_START_DATE AS STRING) AS start_date,
                    CD_SCHEDULE_ID AS schedule_id,
                    CD_CHILD_SCHEDULE_ID AS child_schedule_id,
                    CD_MISC_VISIT_ID AS misc_visit_id,
                    CD_SERVICE_CODE_ID AS service_code_id,
                    CAST(CD_ITEM_TYPE AS STRING) AS item_type,
                    FORMAT_TIMESTAMP('%F %T', CD_SCHEDULE_DATE) AS schedule_date,
                    CD_UNITS AS units,
                    CD_BILLED_AMOUNT AS billed_amount,
                    CD_BILLRATE AS billrate,
                    CAST(CD_IS_BILLRATE_HOURLY AS INT64) AS is_billrate_hourly,
                    CAST(CD_IS_UNITBASED_BILLING AS INT64) AS is_unitbased_billing,
                    CAST(CD_IS_CONTRACTUAL_UNITBASED_BILLING AS INT64) AS is_contractual_unitbased_billing,
                    CAST(CD_BILLRATE_HOURLY_CONTRACTUAL AS INT64) AS billrate_hourly_contractual,
                    CAST(CD_START_TIME AS STRING) AS start_time,
                    CAST(CD_END_TIME AS STRING) AS end_time,
                    CD_TOTAL_HOURS AS total_hours,
                    CD_REQUIFEE_AMOUNT AS requifee_amount,
                    CAST(CD_IS_MERGED AS INT64) AS is_merged,
                    CD_MERGEDCLAIM_DETAIL_ID AS mergedclaim_detail_id,
                    CAST(CD_HCPCS AS STRING) AS hcpcs,
                    CAST(CD_IS_ADDITIONAL_LINEITEM AS INT64) AS is_additional_lineitem,
                    FORMAT_TIMESTAMP('%F %T', CD_SYSTEM_DATE) AS system_date,
                    CAST(CD_DESCRIPTION AS STRING) AS description,
                    CAST(CD_CDS_CLAIM_LINEITEM_TYPE AS STRING) AS cds_claim_lineitem_type,
                    CD_CDS_CLIENT_BUDGET_LINEITEM_ID AS cds_client_budget_lineitem_id,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(CD_CLAIM_DETAIL_ID AS STRING)) AS combinedstring
                FROM `$this->tableRef`
                WHERE CD_CLAIM_DETAIL_ID = ?
                LIMIT 1
                SQL;

        $rows = iterator_to_array($this->connection->cursor($query, [$identifier]), false);

        if (empty($rows)) {
            return [];
        }

        return (array) $rows[0];
    }
}
