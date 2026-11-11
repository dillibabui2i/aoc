<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Domain\DataItem;

final class ClaimsMasterRepository extends AbstractCommonRepository
{
    const TABLE_NAME = 'daas_dw_claimsmaster_vw';

    public function getQuery(): string
    {
        return <<<SQL
                SELECT
                    AGENCY_ID AS agency_id,
                    AGENCY_BRANCH_ID AS agency_branch_id,
                    CLAIM_ID AS claim_id,
                    CLIENT_ID AS client_id,
                    PAYERSOURCE_ID AS payersource_id,
                    EPISODE_ID AS episode_id,
                    CAST(CLAIM_TYPE AS STRING) AS type,
                    FORMAT_TIMESTAMP('%F %T', INVOICE_DATE) AS invoice_date,
                    CAST(INVOICE_NUMBER AS STRING) AS invoice_number,
                    FORMAT_TIMESTAMP('%F %T', CLAIM_START_DATE) AS start_date,
                    FORMAT_TIMESTAMP('%F %T', CLAIM_END_DATE) AS end_date,
                    CLAIM_TOTALAMOUNT AS totalamount,
                    CLAIM_CONTRACTUAL_AMOUNT AS contractual_amount,
                    CLAIM_BALANCE AS balance,
                    CLAIM_BALANCE_CONTRACTUAL AS balance_contractual,
                    CAST(CLAIM_IS_CANCELLED AS INT64) AS is_cancelled,
                    CAST(CLAIM_IS_FULLY_PAID AS INT64) AS is_fully_paid,
                    CAST(CLAIM_IS_PARTIALLY_PAID AS INT64) AS is_partially_paid,
                    CAST(CLAIM_IS_APPEALED AS INT64) AS is_appealed,
                    CAST(CLAIM_IS_DENIED AS INT64) AS is_denied,
                    CLAIM_CONTRACTUAL_ADJUSTMENTS AS contractual_adjustments,
                    CLAIM_OTHER_ADJUSTMENTS AS other_adjustments,
                    CLAIM_PAIDAMOUNT AS paidamount,
                    CAST(CLAIM_IS_SECONDARY AS INT64) AS is_secondary,
                    CAST(CLAIM_IS_TERTIARY AS INT64) AS is_tertiary,
                    CAST(CLAIM_IS_LUPA AS INT64) AS is_lupa,
                    CAST(CLAIM_ISOUTLIER AS INT64) AS isoutlier,
                    CAST(CLAIM_IS_PEP AS INT64) AS is_pep,
                    CAST(CLAIM_IS_TRANSFERRED_COPAY AS INT64) AS is_transferred_copay,
                    CAST(CLAIM_IS_SERVICE_COPAY AS INT64) AS is_service_copay,
                    CAST(CLAIM_IS_MANUAL AS INT64) AS is_manual,
                    CLAIM_INITIAL_HHRG AS initial_hhrg,
                    CLAIM_FINAL_HHRG AS final_hhrg,
                    CLAIM_HHRG_GOAL AS hhrg_goal,
                    CAST(CLAIM_STATUS AS STRING) AS status,
                    FORMAT_TIMESTAMP('%F %T', CLAIM_PAID_DATE) AS paid_date,
                    CAST(CLAIM_IS_EDICREATED AS INT64) AS is_edicreated,
                    FORMAT_TIMESTAMP('%F %T', CLAIM_EDI_CREATEDDATE) AS edi_createddate,
                    CAST(CLAIM_EDI_CREATEDBY_USER_ID AS STRING) AS edi_createdby_user_id,
                    CLAIM_EDI_FILE_ID AS edi_file_id,
                    CAST(CLAIM_CREATEDBY_USER_ID AS STRING) AS createdby_user_id,
                    FORMAT_TIMESTAMP('%F %T', DAAS_LAST_MODIFIED_TIMESTAMP) AS daas_last_modified_timestamp,
                    CAST(CLAIM_CANCELLATION_REASON AS STRING) AS cancellation_reason,
                    CAST(CLAIM_IS_BILLED AS INT64) AS is_billed,
                    CAST(CLAIM_INVOICE_STATUS AS STRING) AS invoice_status,
                    CLAIM_PARENT_CLAIMID AS parent_claimid,
                    CLAIM_PRIMARY_CLAIMID AS primary_claimid,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', CLAIM_ADDEDON) AS addedon,
                    CAST(CLAIM_TRANSACTION_DATE AS STRING) AS transaction_date,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS etl_updated_timestamp,
                    FORMAT_TIMESTAMP('%F %T', CLAIM_BILLED_DATE) AS billed_date,
                    CAST(CLAIM_IS_CLIENT_PAY AS INT64) AS is_client_pay,
                    CAST(CLAIM_IS_LOCKED AS INT64) AS is_locked,
                    CAST(CLAIM_GL_LOCKED_MONTH AS STRING) AS gl_locked_month,
                    FORMAT_TIMESTAMP('%F %T', CLAIM_GL_LOCKED_DATE) AS gl_locked_date,
                    CAST(CLAIM_REMIT_REMARK AS STRING) AS remit_remark,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(CLAIM_ID AS STRING)) AS combinedstring
                FROM `$this->tableRef`
                WHERE CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(CLAIM_ID AS STRING)) > ?
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
                    AGENCY_ID AS agency_id,
                    AGENCY_BRANCH_ID AS agency_branch_id,
                    CLAIM_ID AS claim_id,
                    CLIENT_ID AS client_id,
                    PAYERSOURCE_ID AS payersource_id,
                    EPISODE_ID AS episode_id,
                    CAST(CLAIM_TYPE AS STRING) AS type,
                    FORMAT_TIMESTAMP('%F %T', INVOICE_DATE) AS invoice_date,
                    CAST(INVOICE_NUMBER AS STRING) AS invoice_number,
                    FORMAT_TIMESTAMP('%F %T', CLAIM_START_DATE) AS start_date,
                    FORMAT_TIMESTAMP('%F %T', CLAIM_END_DATE) AS end_date,
                    CLAIM_TOTALAMOUNT AS totalamount,
                    CLAIM_CONTRACTUAL_AMOUNT AS contractual_amount,
                    CLAIM_BALANCE AS balance,
                    CLAIM_BALANCE_CONTRACTUAL AS balance_contractual,
                    CAST(CLAIM_IS_CANCELLED AS INT64) AS is_cancelled,
                    CAST(CLAIM_IS_FULLY_PAID AS INT64) AS is_fully_paid,
                    CAST(CLAIM_IS_PARTIALLY_PAID AS INT64) AS is_partially_paid,
                    CAST(CLAIM_IS_APPEALED AS INT64) AS is_appealed,
                    CAST(CLAIM_IS_DENIED AS INT64) AS is_denied,
                    CLAIM_CONTRACTUAL_ADJUSTMENTS AS contractual_adjustments,
                    CLAIM_OTHER_ADJUSTMENTS AS other_adjustments,
                    CLAIM_PAIDAMOUNT AS paidamount,
                    CAST(CLAIM_IS_SECONDARY AS INT64) AS is_secondary,
                    CAST(CLAIM_IS_TERTIARY AS INT64) AS is_tertiary,
                    CAST(CLAIM_IS_LUPA AS INT64) AS is_lupa,
                    CAST(CLAIM_ISOUTLIER AS INT64) AS isoutlier,
                    CAST(CLAIM_IS_PEP AS INT64) AS is_pep,
                    CAST(CLAIM_IS_TRANSFERRED_COPAY AS INT64) AS is_transferred_copay,
                    CAST(CLAIM_IS_SERVICE_COPAY AS INT64) AS is_service_copay,
                    CAST(CLAIM_IS_MANUAL AS INT64) AS is_manual,
                    CLAIM_INITIAL_HHRG AS initial_hhrg,
                    CLAIM_FINAL_HHRG AS final_hhrg,
                    CLAIM_HHRG_GOAL AS hhrg_goal,
                    CAST(CLAIM_STATUS AS STRING) AS status,
                    FORMAT_TIMESTAMP('%F %T', CLAIM_PAID_DATE) AS paid_date,
                    CAST(CLAIM_IS_EDICREATED AS INT64) AS is_edicreated,
                    FORMAT_TIMESTAMP('%F %T', CLAIM_EDI_CREATEDDATE) AS edi_createddate,
                    CAST(CLAIM_EDI_CREATEDBY_USER_ID AS STRING) AS edi_createdby_user_id,
                    CLAIM_EDI_FILE_ID AS edi_file_id,
                    CAST(CLAIM_CREATEDBY_USER_ID AS STRING) AS createdby_user_id,
                    FORMAT_TIMESTAMP('%F %T', DAAS_LAST_MODIFIED_TIMESTAMP) AS daas_last_modified_timestamp,
                    CAST(CLAIM_CANCELLATION_REASON AS STRING) AS cancellation_reason,
                    CAST(CLAIM_IS_BILLED AS INT64) AS is_billed,
                    CAST(CLAIM_INVOICE_STATUS AS STRING) AS invoice_status,
                    CLAIM_PARENT_CLAIMID AS parent_claimid,
                    CLAIM_PRIMARY_CLAIMID AS primary_claimid,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', CLAIM_ADDEDON) AS addedon,
                    CAST(CLAIM_TRANSACTION_DATE AS STRING) AS transaction_date,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS etl_updated_timestamp,
                    FORMAT_TIMESTAMP('%F %T', CLAIM_BILLED_DATE) AS billed_date,
                    CAST(CLAIM_IS_CLIENT_PAY AS INT64) AS is_client_pay,
                    CAST(CLAIM_IS_LOCKED AS INT64) AS is_locked,
                    CAST(CLAIM_GL_LOCKED_MONTH AS STRING) AS gl_locked_month,
                    FORMAT_TIMESTAMP('%F %T', CLAIM_GL_LOCKED_DATE) AS gl_locked_date,
                    CAST(CLAIM_REMIT_REMARK AS STRING) AS remit_remark,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(CLAIM_ID AS STRING)) AS combinedstring
                FROM `$this->tableRef`
                WHERE CLAIM_ID = ?
                LIMIT 1
                SQL;

        $rows = iterator_to_array($this->connection->cursor($query, [$identifier]), false);

        if (empty($rows)) {
            return [];
        }

        return (array) $rows[0];
    }
}
